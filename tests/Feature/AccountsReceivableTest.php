<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerCreditAccount;
use App\Models\CustomerLedgerEntry;
use App\Models\Sale;
use App\Models\User;
use App\Services\AccountsReceivableService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

function arUser(string $role, Branch $branch): User
{
    return User::factory()->create([
        'role' => $role,
        'branch_id' => $branch->id,
        'status' => 'ACTIVE',
        'active' => true,
        'password' => Hash::make('Password123'),
    ]);
}

it('updates a local username and password through the recovery command', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $admin = arUser('ADMIN', $branch);
    $admin->forceFill(['username' => 'oldadmin', 'email' => 'oldadmin@frontier.local'])->save();

    $exitCode = Artisan::call('frontier:user-update', [
        'login' => 'oldadmin',
        '--username' => 'admin',
        '--password' => 'NewStrongPass123',
    ]);

    expect($exitCode)->toBe(0);

    $admin->refresh();

    expect($admin->username)->toBe('admin')
        ->and(Hash::check('NewStrongPass123', $admin->password))->toBeTrue();
});

it('posts credit sales and payments to receivables without double counting', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'ACTIVE']);
    $manager = arUser('MANAGER', $branch);
    $customer = Customer::create([
        'customer_code' => 'CUS-AR-001',
        'branch_id' => $branch->id,
        'name' => 'Credit Customer',
        'category' => 'REGISTERED',
        'credit_limit' => 100000,
        'credit_period_days' => 30,
        'risk_level' => 'LOW',
        'balance' => 0,
        'status' => Customer::STATUS_ACTIVE,
    ]);
    $sale = Sale::create([
        'receipt_no' => 'RCPT-AR-001',
        'branch_id' => $branch->id,
        'user_id' => $manager->id,
        'customer_id' => $customer->id,
        'subtotal' => 30000,
        'tax' => 0,
        'grand_total' => 30000,
        'amount_paid' => 0,
        'payment_method' => 'CREDIT',
        'payment_status' => 'PARTIAL',
        'credit_due' => 30000,
        'sale_status' => Sale::STATUS_COMPLETED,
    ]);

    $service = app(AccountsReceivableService::class);

    $service->validateCreditSale($customer, 30000, $manager);
    $service->postCreditSale($sale, $customer, 30000, $manager);

    expect($customer->fresh()->balance)->toEqual('30000.00')
        ->and(CustomerCreditAccount::where('customer_id', $customer->id)->value('current_balance'))->toEqual('30000.00')
        ->and(CustomerLedgerEntry::where('customer_id', $customer->id)->where('entry_type', 'CREDIT_SALE')->count())->toBe(1);

    $service->receivePayment($customer->fresh(), 10000, 'CASH', 'CASH-AR-001', $manager);

    expect($customer->fresh()->balance)->toEqual('20000.00')
        ->and(CustomerCreditAccount::where('customer_id', $customer->id)->value('current_balance'))->toEqual('20000.00');
});

it('scopes receivables dashboard to the manager assigned branch', function () {
    $branchA = Branch::create(['name' => 'A', 'code' => 'A', 'status' => 'ACTIVE']);
    $branchB = Branch::create(['name' => 'B', 'code' => 'B', 'status' => 'ACTIVE']);
    $manager = arUser('MANAGER', $branchB);

    $customerA = Customer::create([
        'customer_code' => 'CUS-A',
        'branch_id' => $branchA->id,
        'name' => 'Other Branch Customer',
        'credit_limit' => 50000,
        'balance' => 15000,
        'status' => Customer::STATUS_ACTIVE,
    ]);
    $customerB = Customer::create([
        'customer_code' => 'CUS-B',
        'branch_id' => $branchB->id,
        'name' => 'Own Branch Customer',
        'credit_limit' => 50000,
        'balance' => 12000,
        'status' => Customer::STATUS_ACTIVE,
    ]);

    app(AccountsReceivableService::class)->ensureAccount($customerA, $manager);
    app(AccountsReceivableService::class)->ensureAccount($customerB, $manager);

    $this->actingAs($manager)
        ->get(route('receivables.index'))
        ->assertOk()
        ->assertSee('Own Branch Customer')
        ->assertDontSee('Other Branch Customer');
});
