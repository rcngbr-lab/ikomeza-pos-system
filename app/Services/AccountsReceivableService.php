<?php

namespace App\Services;

use App\Models\ApprovalLevel;
use App\Models\ApprovalRequest;
use App\Models\CreditCollection;
use App\Models\CreditPayment;
use App\Models\CreditTransaction;
use App\Models\Customer;
use App\Models\CustomerCreditAccount;
use App\Models\CustomerLedgerEntry;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountsReceivableService
{
    public function ensureAccount(Customer $customer, ?User $user = null): CustomerCreditAccount
    {
        $account = $customer->creditAccount()->first();

        if ($account) {
            return $this->syncAccountFromCustomer($customer, $account);
        }

        $balance = (float) ($customer->balance ?? 0);
        $limit = (float) ($customer->credit_limit ?? 0);

        return CustomerCreditAccount::create([
            'customer_id' => $customer->id,
            'branch_id' => $customer->branch_id,
            'account_number' => $this->number('AR'),
            'category' => $customer->category ?: 'WALK_IN',
            'credit_limit' => $limit,
            'credit_period_days' => (int) ($customer->credit_period_days ?: 30),
            'risk_level' => $customer->risk_level ?: 'LOW',
            'status' => $this->accountStatusFromCustomer($customer),
            'current_balance' => $balance,
            'available_credit' => max($limit - $balance, 0),
            'total_credit_sales' => (float) ($customer->total_credit_sales ?? 0),
            'total_payments' => (float) ($customer->total_payments ?? 0),
            'total_outstanding' => $balance,
            'last_payment_date' => $customer->last_payment_date,
            'last_credit_date' => $customer->last_credit_date,
            'created_by' => $user?->id,
        ]);
    }

    public function updateCreditProfile(Customer $customer, array $data, User $user): CustomerCreditAccount
    {
        return DB::transaction(function () use ($customer, $data, $user) {
            $account = $this->ensureAccount($customer, $user);
            $old = $account->only([
                'category',
                'credit_limit',
                'credit_period_days',
                'risk_level',
                'status',
                'blocked_reason',
            ]);

            $account->update([
                'category' => $data['category'] ?? $account->category,
                'credit_limit' => (float) ($data['credit_limit'] ?? $account->credit_limit),
                'credit_period_days' => (int) ($data['credit_period_days'] ?? $account->credit_period_days),
                'risk_level' => $data['risk_level'] ?? $account->risk_level,
                'status' => $data['status'] ?? $account->status,
                'blocked_reason' => $data['blocked_reason'] ?? null,
                'available_credit' => max((float) ($data['credit_limit'] ?? $account->credit_limit) - (float) $account->current_balance, 0),
                'approved_by' => $user->id,
            ]);

            $customer->update([
                'category' => $account->category,
                'credit_limit' => $account->credit_limit,
                'credit_period_days' => $account->credit_period_days,
                'risk_level' => $account->risk_level,
                'status' => $account->status === CustomerCreditAccount::STATUS_ACTIVE ? Customer::STATUS_ACTIVE : $account->status,
                'total_outstanding' => $account->current_balance,
            ]);

            AuditLogService::record([
                'action' => 'CUSTOMER_CREDIT_PROFILE_UPDATED',
                'module' => 'Accounts Receivable',
                'model' => $account,
                'reference' => $account->account_number,
                'description' => 'Updated customer credit controls for ' . $customer->name,
                'old_values' => $old,
                'new_values' => $account->only(array_keys($old)),
                'amount' => $account->credit_limit,
                'severity' => 'WARNING',
            ]);

            return $account->refresh();
        });
    }

    public function validateCreditSale(Customer $customer, float $amount, User $user): CustomerCreditAccount
    {
        $account = $this->ensureAccount($customer, $user)->refresh();
        $status = strtoupper((string) $account->status);

        if ($status !== CustomerCreditAccount::STATUS_ACTIVE) {
            throw new \RuntimeException('Customer credit account is not active.');
        }

        if ($customer->branch_id && $user->branch_id && (int) $customer->branch_id !== (int) $user->branch_id && !$user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            throw new \RuntimeException('Customer credit account belongs to another branch.');
        }

        $newBalance = (float) $account->current_balance + $amount;

        if ((float) $account->credit_limit > 0 && $newBalance > (float) $account->credit_limit) {
            throw new \RuntimeException('Customer credit limit exceeded.');
        }

        if ($this->hasOverdueBalance($customer)) {
            throw new \RuntimeException('Customer has overdue receivables. Manager approval or payment is required before more credit.');
        }

        $level = $this->approvalLevelFor($amount);

        if ($level && !$this->userCanApproveLevel($user, (int) $level->level_number)) {
            throw new \RuntimeException('Credit sale requires ' . $level->name . ' approval.');
        }

        return $account;
    }

    public function postCreditSale(Sale $sale, Customer $customer, float $amount, User $user): ?CreditTransaction
    {
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($sale, $customer, $amount, $user) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $account = $this->ensureAccount($customer, $user);
            $account = CustomerCreditAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $account->current_balance;
            $balanceAfter = $balanceBefore + $amount;
            $dueDate = now()->addDays((int) ($account->credit_period_days ?: 30))->toDateString();
            $approvalRequest = $this->createApprovalRequest($customer, $account, $sale, $amount, $user, 'AUTO_APPROVED');

            $transaction = CreditTransaction::create([
                'transaction_number' => $this->number('CTX'),
                'customer_id' => $customer->id,
                'credit_account_id' => $account->id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'approval_request_id' => $approvalRequest?->id,
                'transaction_type' => 'CREDIT_SALE',
                'document_number' => $sale->receipt_no,
                'description' => 'Credit sale ' . $sale->receipt_no,
                'debit' => $amount,
                'credit' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'due_date' => $dueDate,
                'transaction_date' => now()->toDateString(),
                'status' => 'POSTED',
                'posted_by' => $user->id,
            ]);

            CustomerLedgerEntry::create([
                'customer_id' => $customer->id,
                'credit_account_id' => $account->id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'entry_type' => 'CREDIT_SALE',
                'debit' => $amount,
                'credit' => 0,
                'balance_after' => $balanceAfter,
                'transaction_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'reference' => $sale->receipt_no,
                'description' => 'Credit sale ' . $sale->receipt_no,
                'created_by' => $user->id,
            ]);

            $account->update([
                'current_balance' => $balanceAfter,
                'available_credit' => max((float) $account->credit_limit - $balanceAfter, 0),
                'total_credit_sales' => (float) $account->total_credit_sales + $amount,
                'total_outstanding' => $balanceAfter,
                'last_credit_date' => now(),
            ]);

            $customer->update([
                'balance' => $balanceAfter,
                'total_credit_sales' => (float) ($customer->total_credit_sales ?? 0) + $amount,
                'total_outstanding' => $balanceAfter,
                'last_credit_date' => now(),
            ]);

            AuditLogService::record([
                'action' => 'CREDIT_SALE_POSTED',
                'module' => 'Accounts Receivable',
                'model' => $transaction,
                'reference' => $transaction->transaction_number,
                'description' => 'Posted credit sale for ' . $customer->name,
                'amount' => $amount,
                'branch_id' => $sale->branch_id,
                'severity' => 'INFO',
            ]);

            return $transaction;
        });
    }

    public function receivePayment(Customer $customer, float $amount, string $method, ?string $reference, User $user, ?string $notes = null): CreditPayment
    {
        return DB::transaction(function () use ($customer, $amount, $method, $reference, $user, $notes) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $account = $this->ensureAccount($customer, $user);
            $account = CustomerCreditAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $amount = min($amount, max((float) $account->current_balance, 0));

            if ($amount <= 0) {
                throw new \RuntimeException('Customer has no outstanding receivable balance.');
            }

            $balanceBefore = (float) $account->current_balance;
            $balanceAfter = max($balanceBefore - $amount, 0);

            $payment = CreditPayment::create([
                'payment_number' => $this->number('ARP'),
                'customer_id' => $customer->id,
                'credit_account_id' => $account->id,
                'branch_id' => $customer->branch_id,
                'amount' => $amount,
                'payment_method' => Sale::normalizePaymentMethod($method),
                'reference' => $reference,
                'received_by' => $user->id,
                'received_at' => now(),
                'allocation_method' => 'OLDEST_FIRST',
                'status' => 'POSTED',
                'notes' => $notes,
            ]);

            CreditTransaction::create([
                'transaction_number' => $this->number('CTX'),
                'customer_id' => $customer->id,
                'credit_account_id' => $account->id,
                'branch_id' => $customer->branch_id,
                'transaction_type' => 'PAYMENT_RECEIVED',
                'document_number' => $payment->payment_number,
                'description' => 'Customer payment ' . $payment->payment_number,
                'debit' => 0,
                'credit' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => now()->toDateString(),
                'status' => 'POSTED',
                'posted_by' => $user->id,
            ]);

            CustomerLedgerEntry::create([
                'customer_id' => $customer->id,
                'credit_account_id' => $account->id,
                'branch_id' => $customer->branch_id,
                'entry_type' => 'PAYMENT_RECEIVED',
                'debit' => 0,
                'credit' => $amount,
                'balance_after' => $balanceAfter,
                'transaction_date' => now()->toDateString(),
                'payment_method' => Sale::normalizePaymentMethod($method),
                'reference' => $reference ?: $payment->payment_number,
                'description' => 'Customer account payment',
                'created_by' => $user->id,
            ]);

            $account->update([
                'current_balance' => $balanceAfter,
                'available_credit' => max((float) $account->credit_limit - $balanceAfter, 0),
                'total_payments' => (float) $account->total_payments + $amount,
                'total_outstanding' => $balanceAfter,
                'last_payment_date' => now(),
            ]);

            $customer->update([
                'balance' => $balanceAfter,
                'total_payments' => (float) ($customer->total_payments ?? 0) + $amount,
                'total_outstanding' => $balanceAfter,
                'last_payment_date' => now(),
            ]);

            $this->postJournal(
                reference: $payment->payment_number,
                description: 'Customer AR payment received',
                userId: $user->id,
                branchId: $customer->branch_id,
                lines: [
                    $this->journalLine(Sale::normalizePaymentMethod($method) === 'CASH' ? '1010' : '1020', Sale::normalizePaymentMethod($method) === 'CASH' ? 'Cash on Hand' : 'Bank and Mobile Money', $amount, 0, $customer->branch_id),
                    $this->journalLine('1100', 'Customer Receivables', 0, $amount, $customer->branch_id),
                ],
                sourceType: CreditPayment::class,
                sourceId: $payment->id
            );

            AuditLogService::record([
                'action' => 'AR_PAYMENT_RECEIVED',
                'module' => 'Accounts Receivable',
                'model' => $payment,
                'reference' => $payment->payment_number,
                'description' => 'Received AR payment from ' . $customer->name,
                'amount' => $amount,
                'branch_id' => $customer->branch_id,
                'severity' => 'INFO',
            ]);

            return $payment;
        });
    }

    public function recordCollection(Customer $customer, array $data, User $user): CreditCollection
    {
        $account = $this->ensureAccount($customer, $user);

        $collection = CreditCollection::create([
            'collection_number' => $this->number('COL'),
            'customer_id' => $customer->id,
            'credit_account_id' => $account->id,
            'branch_id' => $customer->branch_id,
            'stage' => $data['stage'],
            'channel' => $data['channel'],
            'contact_person' => $data['contact_person'] ?? null,
            'commitment_amount' => $data['commitment_amount'] ?? 0,
            'commitment_date' => $data['commitment_date'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            'status' => $data['status'] ?? 'OPEN',
            'notes' => $data['notes'] ?? null,
            'handled_by' => $user->id,
        ]);

        AuditLogService::record([
            'action' => 'AR_COLLECTION_RECORDED',
            'module' => 'Accounts Receivable',
            'model' => $collection,
            'reference' => $collection->collection_number,
            'description' => 'Recorded collection follow-up for ' . $customer->name,
            'branch_id' => $customer->branch_id,
        ]);

        return $collection;
    }

    public function agingSummary($query): array
    {
        $transactions = (clone $query)
            ->where('transaction_type', 'CREDIT_SALE')
            ->where('debit', '>', 0)
            ->get(['debit', 'credit', 'due_date', 'transaction_date']);

        $buckets = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_91_120' => 0,
            'over_120' => 0,
        ];

        foreach ($transactions as $transaction) {
            $amount = max((float) $transaction->debit - (float) $transaction->credit, 0);

            if ($amount <= 0) {
                continue;
            }

            $days = now()->startOfDay()->diffInDays(($transaction->due_date ?: $transaction->transaction_date)->startOfDay(), false);
            $overdue = abs(min($days, 0));

            match (true) {
                $days >= 0 => $buckets['current'] += $amount,
                $overdue <= 30 => $buckets['days_1_30'] += $amount,
                $overdue <= 60 => $buckets['days_31_60'] += $amount,
                $overdue <= 90 => $buckets['days_61_90'] += $amount,
                $overdue <= 120 => $buckets['days_91_120'] += $amount,
                default => $buckets['over_120'] += $amount,
            };
        }

        $buckets['total'] = array_sum($buckets);

        return array_map(fn ($value) => round($value, 2), $buckets);
    }

    public function approvalLevelFor(float $amount): ?ApprovalLevel
    {
        if (!Schema::hasTable('approval_levels')) {
            return null;
        }

        return ApprovalLevel::where('active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('level_number')
            ->first();
    }

    public function userCanApproveLevel(User $user, int $level): bool
    {
        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return true;
        }

        return match (true) {
            $level <= 1 => $user->hasOperationalRole('MANAGER', 'SUPERVISOR'),
            $level === 2 => $user->hasOperationalRole('MANAGER', 'FINANCE_MANAGER', 'GENERAL_MANAGER'),
            $level === 3 => $user->hasOperationalRole('FINANCE_MANAGER', 'GENERAL_MANAGER'),
            default => $user->hasOperationalRole('GENERAL_MANAGER'),
        };
    }

    private function createApprovalRequest(Customer $customer, CustomerCreditAccount $account, Sale $sale, float $amount, User $user, string $status): ?ApprovalRequest
    {
        if (!Schema::hasTable('approval_requests')) {
            return null;
        }

        $level = $this->approvalLevelFor($amount);

        return ApprovalRequest::create([
            'request_number' => $this->number('APR'),
            'approval_type' => 'CREDIT_SALE',
            'module' => 'Accounts Receivable',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'customer_id' => $customer->id,
            'credit_account_id' => $account->id,
            'branch_id' => $sale->branch_id,
            'requested_by' => $user->id,
            'approved_by' => $status === 'AUTO_APPROVED' ? $user->id : null,
            'level_required' => (int) ($level?->level_number ?? 1),
            'amount' => $amount,
            'status' => $status,
            'reason' => 'Credit sale ' . $sale->receipt_no,
            'requested_at' => now(),
            'approved_at' => $status === 'AUTO_APPROVED' ? now() : null,
        ]);
    }

    private function hasOverdueBalance(Customer $customer): bool
    {
        if (!Schema::hasTable('credit_transactions')) {
            return false;
        }

        $currentBalance = (float) ($customer->creditAccount?->current_balance ?? $customer->balance ?? 0);

        if ($currentBalance <= 0) {
            return false;
        }

        return CreditTransaction::where('customer_id', $customer->id)
            ->where('transaction_type', 'CREDIT_SALE')
            ->where('status', 'POSTED')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('balance_after', '>', 0)
            ->exists();
    }

    private function syncAccountFromCustomer(Customer $customer, CustomerCreditAccount $account): CustomerCreditAccount
    {
        $balance = max((float) ($customer->balance ?? $account->current_balance), 0);
        $limit = (float) ($customer->credit_limit ?? $account->credit_limit);

        $account->forceFill([
            'branch_id' => $account->branch_id ?: $customer->branch_id,
            'category' => $customer->category ?: $account->category,
            'credit_limit' => $limit,
            'credit_period_days' => (int) ($customer->credit_period_days ?: $account->credit_period_days ?: 30),
            'risk_level' => $customer->risk_level ?: $account->risk_level,
            'current_balance' => $balance,
            'available_credit' => max($limit - $balance, 0),
            'total_outstanding' => $balance,
        ])->save();

        return $account->refresh();
    }

    private function accountStatusFromCustomer(Customer $customer): string
    {
        return match (strtoupper((string) $customer->status)) {
            Customer::STATUS_BLOCKED => CustomerCreditAccount::STATUS_BLOCKED,
            Customer::STATUS_INACTIVE => CustomerCreditAccount::STATUS_INACTIVE,
            Customer::STATUS_SUSPENDED => CustomerCreditAccount::STATUS_SUSPENDED,
            Customer::STATUS_CLOSED => CustomerCreditAccount::STATUS_CLOSED,
            default => CustomerCreditAccount::STATUS_ACTIVE,
        };
    }

    private function postJournal(string $reference, string $description, ?int $userId, ?int $branchId, array $lines, string $sourceType, int $sourceId): void
    {
        if (!Schema::hasTable('journal_entries')) {
            return;
        }

        $lines = collect($lines)->filter(fn ($line) => (float) $line['debit'] > 0 || (float) $line['credit'] > 0)->values();
        $totalDebit = round((float) $lines->sum('debit'), 2);
        $totalCredit = round((float) $lines->sum('credit'), 2);

        if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
            return;
        }

        $entry = JournalEntry::create([
            'entry_number' => $this->number('JE'),
            'entry_date' => today(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference' => $reference,
            'description' => $description,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'posted_by' => $userId,
            'status' => 'POSTED',
        ]);

        foreach ($lines as $line) {
            JournalLine::create(array_merge($line, [
                'journal_entry_id' => $entry->id,
                'branch_id' => $branchId,
            ]));
        }
    }

    private function journalLine(string $code, string $name, float $debit, float $credit, ?int $branchId): array
    {
        $accountType = match (substr($code, 0, 1)) {
            '1' => 'ASSET',
            '2' => 'LIABILITY',
            '3' => 'EQUITY',
            '4' => 'INCOME',
            '5' => 'EXPENSE',
            default => $debit > 0 ? 'ASSET' : 'INCOME',
        };

        $account = Schema::hasTable('ledger_accounts')
            ? LedgerAccount::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $accountType, 'active' => true])
            : null;

        return [
            'ledger_account_id' => $account?->id,
            'account_code' => $code,
            'account_name' => $name,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'branch_id' => $branchId,
        ];
    }

    private function number(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
