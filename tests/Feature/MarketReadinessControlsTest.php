<?php

use App\Models\Branch;
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
use Illuminate\Support\Facades\Hash;

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
