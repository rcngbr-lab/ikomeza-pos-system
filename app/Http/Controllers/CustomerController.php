<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Sale;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_code', 'like', '%' . $request->search . '%');
            })
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
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
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
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'status' => Customer::STATUS_ACTIVE,
        ]));

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

        $validated = $request->validate([
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        $old = $customer->only(['credit_limit', 'status']);
        $customer->update($validated);

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
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:' . implode(',', Sale::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        if (Sale::normalizePaymentMethod($validated['payment_method']) === 'CREDIT') {
            return back()->with('error', 'Customer account payment cannot use credit as tender.');
        }

        $amount = min((float) $validated['amount'], (float) $customer->balance);

        if ($amount <= 0) {
            return back()->with('error', 'Customer has no outstanding balance.');
        }

        $customer->decrement('balance', $amount);
        $customer->refresh();

        CustomerLedgerEntry::create([
            'customer_id' => $customer->id,
            'entry_type' => 'PAYMENT_RECEIVED',
            'debit' => 0,
            'credit' => $amount,
            'balance_after' => $customer->balance,
            'payment_method' => Sale::normalizePaymentMethod($validated['payment_method']),
            'reference' => $validated['reference'] ?? null,
            'description' => 'Customer account payment',
            'created_by' => $request->user()->id,
        ]);

        AuditLogService::record([
            'action' => 'CUSTOMER_PAYMENT_RECEIVED',
            'module' => 'Customers',
            'model' => $customer,
            'reference' => $customer->customer_code,
            'description' => 'Received customer account payment from ' . $customer->name,
            'amount' => $amount,
            'severity' => 'INFO',
        ]);

        return back()->with('success', 'Customer payment received and balance updated.');
    }
}
