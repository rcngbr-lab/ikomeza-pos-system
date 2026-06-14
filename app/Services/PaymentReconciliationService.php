<?php

namespace App\Services;

use App\Models\Payment;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class PaymentReconciliationService
{
    public function markMatched(Payment $payment, int $userId, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($payment, $userId, $notes) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->method === 'CASH') {
                throw new \RuntimeException('Cash payments do not require provider reconciliation.');
            }

            if (blank($payment->payment_reference) && blank($payment->transaction_id) && blank($payment->reference)) {
                throw new \RuntimeException('Payment cannot be reconciled without a provider reference or transaction ID.');
            }

            $old = $payment->only(['reconciliation_status', 'reconciled_by', 'reconciled_at']);

            $payment->update([
                'reconciliation_status' => 'MATCHED',
                'reconciled_by' => $userId,
                'reconciled_at' => now(),
                'reconciliation_notes' => $notes,
            ]);

            AuditLogService::record([
                'action' => 'PAYMENT_RECONCILED',
                'module' => 'Payments',
                'model' => $payment,
                'branch_id' => $payment->branch_id,
                'reference' => $payment->payment_reference ?: $payment->reference,
                'description' => 'Reconciled ' . $payment->method . ' payment for sale #' . $payment->sale_id,
                'old_values' => $old,
                'new_values' => $payment->only(['reconciliation_status', 'reconciled_by', 'reconciled_at']),
                'amount' => $payment->amount,
            ]);

            return $payment->refresh();
        });
    }

    public function markException(Payment $payment, int $userId, string $notes): Payment
    {
        return DB::transaction(function () use ($payment, $userId, $notes) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $old = $payment->only(['reconciliation_status', 'reconciliation_notes']);

            $payment->update([
                'reconciliation_status' => 'EXCEPTION',
                'reconciled_by' => $userId,
                'reconciled_at' => now(),
                'reconciliation_notes' => $notes,
            ]);

            AuditLogService::record([
                'action' => 'PAYMENT_RECONCILIATION_EXCEPTION',
                'module' => 'Payments',
                'model' => $payment,
                'branch_id' => $payment->branch_id,
                'reference' => $payment->payment_reference ?: $payment->reference,
                'description' => 'Marked payment reconciliation exception.',
                'old_values' => $old,
                'new_values' => $payment->only(['reconciliation_status', 'reconciliation_notes']),
                'amount' => $payment->amount,
                'severity' => 'WARNING',
            ]);

            return $payment->refresh();
        });
    }
}
