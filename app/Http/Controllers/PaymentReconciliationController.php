<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\BranchAccessService;
use App\Services\PaymentReconciliationService;
use Illuminate\Http\Request;

class PaymentReconciliationController extends Controller
{
    public function index(Request $request, BranchAccessService $branchAccess)
    {
        $query = Payment::with(['sale.user', 'reconciler'])
            ->where('method', '!=', 'CASH')
            ->latest('paid_at');

        $selectedBranchId = $branchAccess->selectedBranchId(
            $request->user(),
            $request->integer('branch_id') ?: null
        );

        $branchAccess->apply($query, $request->user(), $selectedBranchId);

        if ($request->filled('status')) {
            $query->where('reconciliation_status', strtoupper($request->status));
        } else {
            $query->whereIn('reconciliation_status', ['UNMATCHED', 'EXCEPTION']);
        }

        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->method));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($payments) use ($search) {
                $payments->where('payment_reference', 'like', '%' . $search . '%')
                    ->orWhere('transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%')
                    ->orWhereHas('sale', fn ($sale) => $sale->where('receipt_no', 'like', '%' . $search . '%'));
            });
        }

        $payments = $query->paginate(25)->withQueryString();
        $branches = $branchAccess->visibleBranches($request->user());

        return view('payments.reconciliation', compact('payments', 'branches', 'selectedBranchId'));
    }

    public function match(Request $request, Payment $payment, PaymentReconciliationService $service)
    {
        abort_unless($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'), 403);
        $this->authorizeBranch($request, $payment);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->markMatched($payment, $request->user()->id, $validated['notes'] ?? null);

        return back()->with('success', 'Payment reconciled.');
    }

    public function exception(Request $request, Payment $payment, PaymentReconciliationService $service)
    {
        abort_unless($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'), 403);
        $this->authorizeBranch($request, $payment);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $service->markException($payment, $request->user()->id, $validated['notes']);

        return back()->with('success', 'Payment marked as reconciliation exception.');
    }

    private function authorizeBranch(Request $request, Payment $payment): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return;
        }

        abort_unless((int) $payment->branch_id === (int) $request->user()->branch_id, 403);
    }
}
