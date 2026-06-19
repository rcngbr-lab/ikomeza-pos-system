<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\CreditCollection;
use App\Models\CreditPayment;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\CustomerCreditAccount;
use App\Models\Sale;
use App\Services\AccountsReceivableService;
use App\Services\AuditLogService;
use App\Services\BranchAccessService;
use Illuminate\Http\Request;

class AccountsReceivableController extends Controller
{
    public function index(Request $request, AccountsReceivableService $receivables)
    {
        $branchAccess = app(BranchAccessService::class);
        $branchId = $branchAccess->selectedBranchId($request->user(), $request->integer('branch_id') ?: null);

        $accountsQuery = CustomerCreditAccount::query()
            ->with('customer')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->when($request->filled('risk_level'), fn ($query) => $query->where('risk_level', $request->risk_level))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $search = '%' . $request->search . '%';
                    $query->where('account_number', 'like', $search)
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', $search)
                                ->orWhere('phone', 'like', $search)
                                ->orWhere('customer_code', 'like', $search);
                        });
                });
            });

        $accounts = (clone $accountsQuery)
            ->orderByDesc('current_balance')
            ->paginate(20)
            ->withQueryString();

        $transactionQuery = CreditTransaction::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date('start_date')->toDateString()))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date('end_date')->toDateString()));

        $summary = [
            'accounts' => (clone $accountsQuery)->count(),
            'outstanding' => (clone $accountsQuery)->sum('current_balance'),
            'available_credit' => (clone $accountsQuery)->sum('available_credit'),
            'payments' => CreditPayment::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereDate('received_at', now()->toDateString())
                ->sum('amount'),
            'overdue' => CreditTransaction::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->where('transaction_type', 'CREDIT_SALE')
                ->whereDate('due_date', '<', now()->toDateString())
                ->where('balance_after', '>', 0)
                ->sum('balance_after'),
            'high_risk' => (clone $accountsQuery)->whereIn('risk_level', ['HIGH', 'CRITICAL'])->count(),
        ];

        $aging = $receivables->agingSummary($transactionQuery);

        $collections = CreditCollection::query()
            ->with('customer')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'OPEN')
            ->latest()
            ->limit(8)
            ->get();

        $approvals = ApprovalRequest::query()
            ->with('customer')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('module', 'Accounts Receivable')
            ->latest()
            ->limit(8)
            ->get();

        $branches = $branchAccess->visibleBranches($request->user());

        return view('receivables.index', compact(
            'accounts',
            'summary',
            'aging',
            'collections',
            'approvals',
            'branches'
        ));
    }

    public function updateProfile(Request $request, Customer $customer, AccountsReceivableService $receivables)
    {
        $this->authorizeCustomerBranch($request, $customer);

        $validated = $request->validate([
            'category' => ['required', 'in:WALK_IN,REGISTERED,VIP,EMPLOYEE,CORPORATE'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'credit_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'risk_level' => ['required', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,SUSPENDED,BLOCKED,CLOSED'],
            'blocked_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $receivables->updateCreditProfile($customer, $validated, $request->user());

        return back()->with('success', 'Customer credit profile updated.');
    }

    public function payment(Request $request, Customer $customer, AccountsReceivableService $receivables)
    {
        $this->authorizeCustomerBranch($request, $customer);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:' . implode(',', Sale::PAYMENT_METHODS)],
            'reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (Sale::normalizePaymentMethod($validated['payment_method']) === 'CREDIT') {
            return back()->with('error', 'Receivable payment cannot use customer credit as tender.');
        }

        try {
            $receivables->receivePayment(
                customer: $customer,
                amount: (float) $validated['amount'],
                method: $validated['payment_method'],
                reference: $validated['reference'] ?? null,
                user: $request->user(),
                notes: $validated['notes'] ?? null
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Receivable payment posted.');
    }

    public function collection(Request $request, Customer $customer, AccountsReceivableService $receivables)
    {
        $this->authorizeCustomerBranch($request, $customer);

        $validated = $request->validate([
            'stage' => ['required', 'in:CURRENT,FIRST_REMINDER,SECOND_REMINDER,FINAL_NOTICE,LEGAL,BAD_DEBT'],
            'channel' => ['required', 'in:CALL,SMS,EMAIL,VISIT,WHATSAPP,LETTER'],
            'contact_person' => ['nullable', 'string', 'max:160'],
            'commitment_amount' => ['nullable', 'numeric', 'min:0'],
            'commitment_date' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'status' => ['required', 'in:OPEN,CLOSED,ESCALATED'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $receivables->recordCollection($customer, $validated, $request->user());

        return back()->with('success', 'Collection follow-up recorded.');
    }

    public function statement(Request $request, Customer $customer, AccountsReceivableService $receivables)
    {
        $this->authorizeCustomerBranch($request, $customer);

        $start = $request->filled('start_date') ? $request->date('start_date')->startOfDay() : now()->startOfMonth();
        $end = $request->filled('end_date') ? $request->date('end_date')->endOfDay() : now()->endOfDay();
        $account = $receivables->ensureAccount($customer, $request->user());

        $opening = (float) CreditTransaction::where('customer_id', $customer->id)
            ->whereDate('transaction_date', '<', $start->toDateString())
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) as balance')
            ->value('balance');

        $transactions = CreditTransaction::where('customer_id', $customer->id)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $debitTotal = (float) $transactions->sum('debit');
        $creditTotal = (float) $transactions->sum('credit');
        $closing = $opening + $debitTotal - $creditTotal;

        return view('receivables.statement', compact(
            'customer',
            'account',
            'transactions',
            'start',
            'end',
            'opening',
            'debitTotal',
            'creditTotal',
            'closing'
        ));
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        abort_unless($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'FINANCE_MANAGER', 'GENERAL_MANAGER'), 403);
        $this->authorizeBranchRecord($request, $approvalRequest->branch_id);

        $approvalRequest->update([
            'status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approval_note' => $request->input('approval_note'),
            'approved_at' => now(),
        ]);

        AuditLogService::record([
            'action' => 'AR_APPROVAL_APPROVED',
            'module' => 'Accounts Receivable',
            'model' => $approvalRequest,
            'reference' => $approvalRequest->request_number,
            'description' => 'Approved receivables request ' . $approvalRequest->request_number,
            'amount' => $approvalRequest->amount,
            'branch_id' => $approvalRequest->branch_id,
        ]);

        return back()->with('success', 'Approval request approved.');
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        abort_unless($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'FINANCE_MANAGER', 'GENERAL_MANAGER'), 403);
        $this->authorizeBranchRecord($request, $approvalRequest->branch_id);

        $validated = $request->validate([
            'approval_note' => ['required', 'string', 'max:500'],
        ]);

        $approvalRequest->update([
            'status' => 'REJECTED',
            'approved_by' => $request->user()->id,
            'approval_note' => $validated['approval_note'],
            'rejected_at' => now(),
        ]);

        AuditLogService::record([
            'action' => 'AR_APPROVAL_REJECTED',
            'module' => 'Accounts Receivable',
            'model' => $approvalRequest,
            'reference' => $approvalRequest->request_number,
            'description' => 'Rejected receivables request ' . $approvalRequest->request_number,
            'amount' => $approvalRequest->amount,
            'branch_id' => $approvalRequest->branch_id,
            'severity' => 'WARNING',
        ]);

        return back()->with('success', 'Approval request rejected.');
    }

    private function authorizeCustomerBranch(Request $request, Customer $customer): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return;
        }

        abort_unless((int) $customer->branch_id === (int) $request->user()->branch_id, 403);
    }

    private function authorizeBranchRecord(Request $request, ?int $branchId): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return;
        }

        abort_unless((int) $branchId === (int) $request->user()->branch_id, 403);
    }
}
