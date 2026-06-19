<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Services\AccountsReceivableService;
use App\Services\AuditLogService;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $branchAccess = app(BranchAccessService::class);
        $query = Customer::query();
        $branchAccess->apply($query, $request->user(), $request->integer('branch_id') ?: null);

        $customers = $query
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('phone', 'like', '%' . $request->search . '%')
                        ->orWhere('customer_code', 'like', '%' . $request->search . '%');
                });
            })
            ->with('creditAccount')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'tin' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'in:WALK_IN,REGISTERED,VIP,EMPLOYEE,CORPORATE'],
            'national_id' => ['nullable', 'string', 'max:80'],
            'company_registration_number' => ['nullable', 'string', 'max:120'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'risk_level' => ['nullable', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')
            && (float) ($validated['credit_limit'] ?? 0) > 0
        ) {
            abort(403);
        }

        $customer = Customer::create(array_merge($validated, [
            'customer_code' => 'CUS-' . now()->format('Ymd-His') . '-' . random_int(100, 999),
            'branch_id' => $request->user()->branch_id,
            'category' => $validated['category'] ?? 'WALK_IN',
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'credit_period_days' => $validated['credit_period_days'] ?? 30,
            'risk_level' => $validated['risk_level'] ?? 'LOW',
            'status' => Customer::STATUS_ACTIVE,
        ]));

        app(AccountsReceivableService::class)->ensureAccount($customer, $request->user());

        AuditLogService::record([
            'action' => 'CUSTOMER_CREATED',
            'module' => 'Customers',
            'model' => $customer,
            'reference' => $customer->customer_code,
            'description' => 'Created customer account ' . $customer->name,
        ]);

        return back()->with('success', 'Customer account created.');
    }

    public function update(Request $request, Customer $customer)
    {
        abort_unless($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'), 403);
        $this->authorizeCustomerBranch($request, $customer);

        $validated = $request->validate([
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'credit_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'category' => ['nullable', 'in:WALK_IN,REGISTERED,VIP,EMPLOYEE,CORPORATE'],
            'risk_level' => ['nullable', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,SUSPENDED,BLOCKED,CLOSED'],
            'blocked_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $old = $customer->only(['credit_limit', 'status']);
        app(AccountsReceivableService::class)->updateCreditProfile($customer, $validated, $request->user());

        AuditLogService::record([
            'action' => 'CUSTOMER_UPDATED',
            'module' => 'Customers',
            'model' => $customer,
            'reference' => $customer->customer_code,
            'description' => 'Updated customer controls for ' . $customer->name,
            'old_values' => $old,
            'new_values' => $validated,
            'severity' => 'WARNING',
        ]);

        return back()->with('success', 'Customer account updated.');
    }

    public function payment(Request $request, Customer $customer)
    {
        $this->authorizeCustomerBranch($request, $customer);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:' . implode(',', Sale::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        if (Sale::normalizePaymentMethod($validated['payment_method']) === 'CREDIT') {
            return back()->with('error', 'Customer account payment cannot use credit as tender.');
        }

        try {
            $payment = app(AccountsReceivableService::class)->receivePayment(
                customer: $customer,
                amount: (float) $validated['amount'],
                method: $validated['payment_method'],
                reference: $validated['reference'] ?? null,
                user: $request->user()
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        AuditLogService::record([
            'action' => 'CUSTOMER_PAYMENT_RECEIVED',
            'module' => 'Customers',
            'model' => $customer,
            'reference' => $customer->customer_code,
            'description' => 'Received customer account payment from ' . $customer->name,
            'amount' => $payment->amount,
            'severity' => 'INFO',
        ]);

        return back()->with('success', 'Customer payment received and balance updated.');
    }

    private function authorizeCustomerBranch(Request $request, Customer $customer): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return;
        }

        abort_unless((int) $customer->branch_id === (int) $request->user()->branch_id, 403);
    }
}
