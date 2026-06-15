<?php

use App\Models\Branch;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use App\Services\StoreStockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function marketUser(string $role, Branch $branch): User {
    return User::factory()->create([
        'role' => $role,
        'branch_id' => $branch->id,
        'status' => 'ACTIVE',
        'active' => true,
        'password' => Hash::make('Password123'),
    ]);
}

function marketProduct(Branch $branch, float $price = 1180, bool $taxable = true): Product {
    $department = Department::firstOrCreate(
        ['code' => 'BAR'],
        [
            'name' => 'Bar',
            'active' => true,
            'sort_order' => 1,
        ]
    );

    $category = Category::firstOrCreate(
        ['code' => 'BEER'],
        [
            'name' => 'Beer',
            'department_id' => $department->id,
            'active' => true,
            'sort_order' => 1,
        ]
    );

    $store = Store::create([
        'code' => 'BAR-' . $branch->id,
        'branch_id' => $branch->id,
        'name' => 'Bar Store',
        'type' => 'BAR',
        'department_id' => $department->id,
        'active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::create([
        'product_code' => 'PRD-' . uniqid(),
        'branch_id' => $branch->id,
        'barcode' => 'BC-' . uniqid(),
        'name' => 'Test Beer',
        'category_id' => $category->id,
        'department_id' => $department->id,
        'default_store_id' => $store->id,
        'product_type' => 'FINISHED_PRODUCT',
        'buy_price' => 500,
        'selling_price' => $price,
        'track_stock' => true,
        'stock' => 10,
        'alert_stock' => 2,
        'unit' => 'Bottle',
        'active' => true,
        'status' => 'ACTIVE',
        'is_taxable' => $taxable,
        'tax_category' => $taxable ? 'VATABLE' : 'EXEMPT',
    ]);

    StoreStock::create([
        'store_id' => $store->id,
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'quantity' => 10,
        'alert_stock' => 2,
        'unit_cost' => 500,
        'total_value' => 5000,
    ]);

    return $product;
}

it('requires a reconciliation reference for non-cash POS checkout', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $cashier = marketUser('CASHIER', $branch);
    $product = marketProduct($branch);

    Shift::create([
        'user_id' => $cashier->id,
        'branch_id' => $branch->id,
        'shift_code' => 'SHIFT-TEST',
        'opening_cash' => 0,
        'expected_cash' => 0,
        'status' => 'OPEN',
        'is_open' => true,
        'opened_at' => now(),
    ]);

    $cart = [
        $product->id => [
            'id' => $product->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->selling_price,
            'quantity' => 1,
        ],
    ];

    $this->actingAs($cashier)
        ->withSession(['cart' => $cart])
        ->post(route('pos.checkout'), [
            'payment_method' => 'MOMO',
            'amount_paid' => 1180,
        ])
        ->assertSessionHas('error', 'Non-cash payments require a payment reference or transaction ID for reconciliation.');
});

it('stores unmatched non-cash payments with provider references', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $cashier = marketUser('CASHIER', $branch);
    $product = marketProduct($branch);

    Shift::create([
        'user_id' => $cashier->id,
        'branch_id' => $branch->id,
        'shift_code' => 'SHIFT-TEST',
        'opening_cash' => 0,
        'expected_cash' => 0,
        'status' => 'OPEN',
        'is_open' => true,
        'opened_at' => now(),
    ]);

    $this->actingAs($cashier)
        ->withSession(['cart' => [
            $product->id => [
                'id' => $product->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->selling_price,
                'quantity' => 1,
            ],
        ]])
        ->post(route('pos.checkout'), [
            'payment_method' => 'MOMO',
            'amount_paid' => 1180,
            'payment_reference' => 'MTN-123',
        ]);

    $payment = Payment::first();

    expect($payment)->not->toBeNull()
        ->and($payment->payment_reference)->toBe('MTN-123')
        ->and($payment->reconciliation_status)->toBe('UNMATCHED')
        ->and($payment->branch_id)->toBe($branch->id);
});

it('allows managers to reconcile only branch-owned payments', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $manager = marketUser('MANAGER', $branch);

    $sale = Sale::create([
        'receipt_no' => 'RCPT-TEST',
        'branch_id' => $branch->id,
        'user_id' => $manager->id,
        'subtotal' => 1000,
        'tax' => 0,
        'grand_total' => 1000,
        'amount_paid' => 1000,
        'payment_method' => 'MOMO',
        'payment_status' => 'PAID',
        'sale_status' => Sale::STATUS_COMPLETED,
    ]);

    $payment = Payment::create([
        'sale_id' => $sale->id,
        'branch_id' => $branch->id,
        'received_by' => $manager->id,
        'method' => 'MOMO',
        'amount' => 1000,
        'payment_reference' => 'MTN-999',
        'status' => Payment::STATUS_COMPLETED,
        'payment_status' => Payment::STATUS_COMPLETED,
        'reconciliation_status' => 'UNMATCHED',
        'paid_at' => now(),
    ]);

    app(PaymentReconciliationService::class)->markMatched($payment, $manager->id, 'Matched with provider statement');

    expect($payment->refresh()->reconciliation_status)->toBe('MATCHED')
        ->and($payment->reconciled_by)->toBe($manager->id);
});

it('hides another branch sales from a manager', function () {
    $branchA = Branch::create(['name' => 'A', 'code' => 'A', 'status' => 'ACTIVE']);
    $branchB = Branch::create(['name' => 'B', 'code' => 'B', 'status' => 'ACTIVE']);
    $manager = marketUser('MANAGER', $branchB);

    Sale::create([
        'receipt_no' => 'RCPT-BRANCH-A',
        'branch_id' => $branchA->id,
        'user_id' => $manager->id,
        'subtotal' => 1000,
        'tax' => 0,
        'grand_total' => 1000,
        'amount_paid' => 1000,
        'payment_method' => 'CASH',
        'payment_status' => 'PAID',
        'sale_status' => Sale::STATUS_COMPLETED,
    ]);

    $this->actingAs($manager)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertDontSee('RCPT-BRANCH-A');
});

it('does not render audit logs navigation for cashiers', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $cashier = marketUser('CASHIER', $branch);

    $this->actingAs($cashier)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertDontSee('Audit Logs')
        ->assertDontSee('audit-logs');
});

it('prints only the authenticated cashier sales report', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $cashier = marketUser('CASHIER', $branch);
    $otherCashier = marketUser('CASHIER', $branch);

    Sale::create([
        'receipt_no' => 'RCPT-MINE',
        'branch_id' => $branch->id,
        'user_id' => $cashier->id,
        'customer_name' => 'Mine Customer',
        'subtotal' => 1000,
        'tax' => 0,
        'grand_total' => 1000,
        'amount_paid' => 1000,
        'payment_method' => 'CASH',
        'payment_status' => 'PAID',
        'sale_status' => Sale::STATUS_COMPLETED,
    ]);

    Sale::create([
        'receipt_no' => 'RCPT-OTHER',
        'branch_id' => $branch->id,
        'user_id' => $otherCashier->id,
        'customer_name' => 'Other Customer',
        'subtotal' => 5000,
        'tax' => 0,
        'grand_total' => 5000,
        'amount_paid' => 5000,
        'payment_method' => 'CASH',
        'payment_status' => 'PAID',
        'sale_status' => Sale::STATUS_COMPLETED,
    ]);

    $this->actingAs($cashier)
        ->get(route('sales.report.print'))
        ->assertOk()
        ->assertSee('MY SALES REPORT')
        ->assertSee('RCPT-MINE')
        ->assertDontSee('RCPT-OTHER');
});

it('collapses duplicate POS product cards for the same branch and product identity', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $cashier = marketUser('CASHIER', $branch);
    $product = marketProduct($branch, 1200);

    $duplicate = $product->replicate(['product_code', 'barcode']);
    $duplicate->product_code = 'PRD-DUP-' . uniqid();
    $duplicate->barcode = 'BC-DUP-' . uniqid();
    $duplicate->stock = 25;
    $duplicate->save();

    Shift::create([
        'user_id' => $cashier->id,
        'branch_id' => $branch->id,
        'shift_code' => 'SHIFT-DUPLICATE-CARDS',
        'opening_cash' => 0,
        'expected_cash' => 0,
        'status' => 'OPEN',
        'is_open' => true,
        'opened_at' => now(),
    ]);

    $response = $this->actingAs($cashier)
        ->get(route('pos.index'))
        ->assertOk();

    $products = $response->viewData('products');

    expect($products)->toHaveCount(1)
        ->and($products->first()->name)->toBe($product->name)
        ->and($products->first()->stock)->toEqual('25.00');
});

it('selects the branch-owned default store by type before legacy global store codes', function () {
    $branchA = Branch::create(['name' => 'A', 'code' => 'A', 'status' => 'ACTIVE']);
    $branchB = Branch::create(['name' => 'B', 'code' => 'B', 'status' => 'ACTIVE']);
    $department = Department::firstOrCreate(['code' => 'BAR'], ['name' => 'Bar', 'active' => true, 'sort_order' => 1]);
    $category = Category::firstOrCreate(['code' => 'BEER'], ['name' => 'Beer', 'department_id' => $department->id, 'active' => true]);

    Store::create([
        'code' => 'GLOBAL-BAR',
        'branch_id' => null,
        'name' => 'Global Bar Store',
        'type' => 'BAR',
        'department_id' => $department->id,
        'active' => true,
        'sort_order' => 1,
    ]);

    Store::create([
        'code' => 'A-BAR',
        'branch_id' => $branchA->id,
        'name' => 'A Bar Store',
        'type' => 'BAR',
        'department_id' => $department->id,
        'active' => true,
        'sort_order' => 2,
    ]);

    $branchBStore = Store::create([
        'code' => 'B-BAR',
        'branch_id' => $branchB->id,
        'name' => 'B Bar Store',
        'type' => 'BAR',
        'department_id' => $department->id,
        'active' => true,
        'sort_order' => 3,
    ]);

    $product = Product::create([
        'product_code' => 'PRD-BRANCH-B-' . uniqid(),
        'branch_id' => $branchB->id,
        'barcode' => 'BC-BRANCH-B-' . uniqid(),
        'name' => 'Branch B Beer',
        'category_id' => $category->id,
        'department_id' => $department->id,
        'product_type' => 'FINISHED_PRODUCT',
        'buy_price' => 500,
        'selling_price' => 1000,
        'track_stock' => true,
        'stock' => 10,
        'alert_stock' => 2,
        'unit' => 'Bottle',
        'active' => true,
        'status' => 'ACTIVE',
    ]);

    expect(app(StoreStockService::class)->defaultStoreFor($product)?->id)->toBe($branchBStore->id);
});

it('keeps manager audit log visibility inside the assigned branch', function () {
    $branchA = Branch::create(['name' => 'A', 'code' => 'A', 'status' => 'ACTIVE']);
    $branchB = Branch::create(['name' => 'B', 'code' => 'B', 'status' => 'ACTIVE']);
    $manager = marketUser('MANAGER', $branchB);

    AuditLog::create([
        'user_id' => $manager->id,
        'branch_id' => $branchA->id,
        'action' => 'SALE_COMPLETED',
        'module' => 'Sales',
        'event' => 'SALE_COMPLETED',
        'model' => 'Sale',
        'description' => 'Other branch sale log',
        'severity' => 'INFO',
    ]);

    AuditLog::create([
        'user_id' => $manager->id,
        'branch_id' => $branchB->id,
        'action' => 'SALE_COMPLETED',
        'module' => 'Sales',
        'event' => 'SALE_COMPLETED',
        'model' => 'Sale',
        'description' => 'Own branch sale log',
        'severity' => 'INFO',
    ]);

    $this->actingAs($manager)
        ->get(route('audit.logs'))
        ->assertOk()
        ->assertSee('Own branch sale log')
        ->assertDontSee('Other branch sale log');
});

it('blocks cashiers from audit logs', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $cashier = marketUser('CASHIER', $branch);
    $log = AuditLog::create([
        'user_id' => $cashier->id,
        'branch_id' => $branch->id,
        'action' => 'SALE_COMPLETED',
        'module' => 'Sales',
        'event' => 'SALE_COMPLETED',
        'model' => 'Sale',
        'description' => 'Cashier sale log',
        'severity' => 'INFO',
    ]);

    $this->actingAs($cashier)
        ->get(route('audit.logs'))
        ->assertForbidden();

    $this->actingAs($cashier)
        ->get(route('audit.logs.show', $log))
        ->assertForbidden();
});

it('allows a manager to update a product image from the adjust page', function () {
    Storage::fake('public');

    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $manager = marketUser('MANAGER', $branch);
    $product = marketProduct($branch);

    $this->actingAs($manager)
        ->post(route('products.image.update', $product), [
            'product_image' => new UploadedFile(
                tap(tempnam(sys_get_temp_dir(), 'pos-product-image-'), function ($path) {
                    file_put_contents(
                        $path,
                        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
                    );
                }),
                'bazooka.png',
                'image/png',
                null,
                true
            ),
        ])
        ->assertRedirect(route('products.adjust', $product));

    $product->refresh();

    expect($product->image_path)->not->toBeNull()
        ->and($product->image_url)->toBeNull();

    Storage::disk('public')->assertExists($product->image_path);
});
