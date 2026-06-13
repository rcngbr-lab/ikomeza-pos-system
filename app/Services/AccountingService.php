<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SupplierLedgerEntry;
use Illuminate\Support\Facades\Schema;

class AccountingService
{
    public function postSale(Sale $sale): void
    {
        if (!Schema::hasTable('journal_entries')) {
            return;
        }

        $sale->loadMissing('payments');

        $cashPaid = (float) $sale->payments->where('method', 'CASH')->sum('amount');
        $nonCashPaid = max((float) $sale->payments->where('method', '!=', 'CASH')->sum('amount'), 0);
        $creditDue = max((float) $sale->credit_due, 0);
        $tax = max((float) $sale->tax, 0);
        $salesRevenue = max((float) $sale->grand_total - $tax, 0);

        $lines = [];

        if ($cashPaid > 0) {
            $lines[] = $this->line('1010', 'Cash on Hand', $cashPaid, 0, $sale);
        }

        if ($nonCashPaid > 0) {
            $lines[] = $this->line('1020', 'Bank and Mobile Money', $nonCashPaid, 0, $sale);
        }

        if ($creditDue > 0) {
            $lines[] = $this->line('1100', 'Customer Receivables', $creditDue, 0, $sale);
        }

        $lines[] = $this->line('4000', 'Sales Revenue', 0, $salesRevenue, $sale);

        if ($tax > 0) {
            $lines[] = $this->line('2100', 'VAT Payable', 0, $tax, $sale);
        }

        $this->createEntry(
            sourceType: Sale::class,
            sourceId: $sale->id,
            reference: $sale->receipt_no,
            description: 'POS sale ' . $sale->receipt_no,
            userId: $sale->user_id,
            lines: $lines
        );
    }

    public function postPurchaseLiability(Purchase $purchase): void
    {
        if (!Schema::hasTable('supplier_ledger_entries') || !$purchase->supplier_id) {
            return;
        }

        $balance = (float) SupplierLedgerEntry::where('supplier_id', $purchase->supplier_id)
            ->latest()
            ->value('balance_after');

        $amount = max((float) $purchase->balance_due ?: (float) $purchase->total_amount, 0);
        $balance += $amount;

        SupplierLedgerEntry::create([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'entry_type' => 'PURCHASE_CREDIT',
            'debit' => $amount,
            'credit' => 0,
            'balance_after' => $balance,
            'reference' => $purchase->purchase_number,
            'description' => 'Supplier balance from purchase ' . $purchase->purchase_number,
            'created_by' => $purchase->purchased_by,
        ]);
    }

    private function createEntry(string $sourceType, int $sourceId, string $reference, string $description, ?int $userId, array $lines): void
    {
        $totalDebit = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return;
        }

        $entry = JournalEntry::create([
            'entry_number' => 'JE-' . now()->format('Ymd-His') . '-' . random_int(100, 999),
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
            JournalLine::create(array_merge($line, ['journal_entry_id' => $entry->id]));
        }
    }

    private function line(string $code, string $name, float $debit, float $credit, Sale $sale): array
    {
        $account = $this->account($code, $name, $debit > 0 ? 'ASSET' : 'INCOME');

        return [
            'ledger_account_id' => $account?->id,
            'account_code' => $code,
            'account_name' => $name,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'department_id' => $sale->items()->value('department_id'),
            'branch_id' => $sale->branch_id,
            'memo' => $sale->receipt_no,
        ];
    }

    private function account(string $code, string $name, string $type): ?LedgerAccount
    {
        if (!Schema::hasTable('ledger_accounts')) {
            return null;
        }

        return LedgerAccount::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => $type, 'active' => true]
        );
    }
}

