<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Refund;
use App\Models\RefundRequest;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefundWorkflowService
{
    public function request(Sale $sale, User $user, ?string $reason = null): RefundRequest
    {
        if ((bool) $sale->is_refunded || $sale->sale_status === Sale::STATUS_REFUNDED) {
            throw new \RuntimeException('Sale already refunded.');
        }

        $existing = RefundRequest::where('sale_id', $sale->id)
            ->where('status', RefundRequest::STATUS_PENDING)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $request = RefundRequest::create([
            'request_number' => 'RFR-' . now()->format('Ymd-His') . '-' . random_int(100, 999),
            'sale_id' => $sale->id,
            'requested_by' => $user->id,
            'amount' => $sale->grand_total,
            'reason' => $reason,
            'status' => RefundRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        app(ApprovalNotificationService::class)->create(
            'Refunds',
            'REFUND_APPROVAL',
            $request->request_number,
            ['sale_id' => $sale->id, 'receipt_no' => $sale->receipt_no, 'amount' => $sale->grand_total]
        );

        AuditLogService::record([
            'action' => 'REFUND_REQUESTED',
            'module' => 'Refunds',
            'model' => $request,
            'department_id' => $sale->items()->value('department_id'),
            'branch_id' => $sale->branch_id,
            'reference' => $sale->receipt_no,
            'description' => 'Requested refund approval for sale ' . $sale->receipt_no,
            'amount' => $sale->grand_total,
            'severity' => 'WARNING',
        ]);

        return $request;
    }

    public function approveAndExecute(RefundRequest $refundRequest, User $approver, ?string $note = null): Refund
    {
        if ($refundRequest->status !== RefundRequest::STATUS_PENDING) {
            throw new \RuntimeException('Only pending refund requests can be approved.');
        }

        if (!$approver->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $refundRequest->requested_by === (int) $approver->id) {
            throw new \RuntimeException('Separation of duties: requester cannot approve their own refund.');
        }

        return DB::transaction(function () use ($refundRequest, $approver, $note) {
            $refundRequest->load('sale.items.product.department', 'sale.items.department', 'sale.payments', 'sale.shift');
            $sale = $refundRequest->sale;

            if (!$sale || (bool) $sale->is_refunded || $sale->sale_status === Sale::STATUS_REFUNDED) {
                throw new \RuntimeException('Sale is already refunded or unavailable.');
            }

            $refundRequest->update([
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'status' => RefundRequest::STATUS_APPROVED,
                'approval_note' => $note,
            ]);

            $refund = Refund::create($this->onlyExistingColumns('refunds', [
                'sale_id' => $sale->id,
                'user_id' => $approver->id,
                'amount' => $sale->grand_total,
                'reason' => $refundRequest->reason,
                'status' => Refund::STATUS_COMPLETED,
                'refunded_at' => now(),
            ]));

            $restoredUnits = 0;

            foreach ($sale->items as $item) {
                $restoredUnits += $this->restoreItemStock($item, $sale, $refund, $approver);
            }

            $sale->update($this->onlyExistingColumns('sales', [
                'is_refunded' => true,
                'refund_amount' => $sale->grand_total,
                'refund_reason' => $refundRequest->reason,
                'refunded_at' => now(),
                'refunded_by' => $approver->id,
                'sale_status' => Sale::STATUS_REFUNDED,
            ]));

            $this->reverseShiftTotals($sale);

            $refundRequest->update([
                'executed_by' => $approver->id,
                'executed_at' => now(),
                'status' => RefundRequest::STATUS_EXECUTED,
            ]);

            AuditLogService::record([
                'action' => 'REFUND_APPROVED',
                'module' => 'Refunds',
                'event_type' => 'FINANCIAL',
                'model' => $refund,
                'department_id' => $sale->items->first()?->department_id,
                'branch_id' => $sale->branch_id,
                'reference' => $sale->receipt_no,
                'description' => 'Approved and executed refund for sale ' . $sale->receipt_no,
                'old_values' => ['sale_status' => Sale::STATUS_COMPLETED, 'is_refunded' => false],
                'new_values' => ['sale_status' => Sale::STATUS_REFUNDED, 'is_refunded' => true, 'refund_amount' => $sale->grand_total],
                'amount' => $sale->grand_total,
                'quantity_changed' => $restoredUnits,
                'severity' => 'WARNING',
            ]);

            app(SyncOutboxService::class)->push('REFUND_EXECUTED', $refund, [
                'receipt_no' => $sale->receipt_no,
                'amount' => $refund->amount,
                'executed_at' => now()->toDateTimeString(),
            ]);

            return $refund;
        });
    }

    public function reject(RefundRequest $refundRequest, User $user, ?string $note = null): void
    {
        if ($refundRequest->status !== RefundRequest::STATUS_PENDING) {
            throw new \RuntimeException('Only pending refund requests can be rejected.');
        }

        $refundRequest->update([
            'approved_by' => $user->id,
            'rejected_at' => now(),
            'approval_note' => $note,
            'status' => RefundRequest::STATUS_REJECTED,
        ]);

        AuditLogService::record([
            'action' => 'REFUND_REJECTED',
            'module' => 'Refunds',
            'model' => $refundRequest,
            'reference' => $refundRequest->request_number,
            'description' => 'Rejected refund request ' . $refundRequest->request_number,
            'severity' => 'WARNING',
        ]);
    }

    private function restoreItemStock($item, Sale $sale, Refund $refund, User $user): float
    {
        $product = Product::whereKey($item->product_id)
            ->lockForUpdate()
            ->first();

        if (!$product) {
            return 0;
        }

        $recipe = Recipe::with(['items.ingredient', 'items.store'])
            ->where('product_id', $product->id)
            ->where('active', true)
            ->first();

        if ($recipe) {
            return $this->restoreRecipeIngredients($recipe, (float) $item->quantity, $sale, $refund, $user);
        }

        if (!$product->track_stock) {
            return 0;
        }

        return $this->restoreProduct($product, (float) $item->quantity, $item->department_id, $sale, $refund, $user);
    }

    private function restoreRecipeIngredients(Recipe $recipe, float $soldQuantity, Sale $sale, Refund $refund, User $user): float
    {
        $yieldQuantity = max((float) $recipe->yield_quantity, 1);
        $multiplier = $soldQuantity / $yieldQuantity;
        $restored = 0;

        foreach ($recipe->items as $recipeItem) {
            $ingredient = Product::whereKey($recipeItem->ingredient_product_id)
                ->lockForUpdate()
                ->first();

            if (!$ingredient || !$ingredient->track_stock) {
                continue;
            }

            $quantity = (float) $recipeItem->quantity * $multiplier;
            $restored += $this->restoreProduct($ingredient, $quantity, $ingredient->department_id ?: $recipe->department_id, $sale, $refund, $user);
        }

        return $restored;
    }

    private function restoreProduct(Product $product, float $quantity, ?int $departmentId, Sale $sale, Refund $refund, User $user): float
    {
        $before = (float) $product->stock;
        $product->increment('stock', $quantity);
        $product->refresh();

        $storeStockService = app(StoreStockService::class);
        $store = $storeStockService->defaultStoreFor($product);
        $storeSnapshot = null;

        if ($store) {
            $storeSnapshot = $storeStockService->increaseStoreOnly(
                $product,
                $store,
                $quantity,
                (float) ($product->buy_price ?? 0)
            );
        }

        Stock::create($this->onlyExistingColumns('stocks', [
            'product_id' => $product->id,
            'department_id' => $departmentId,
            'type' => 'refund',
            'quantity' => $quantity,
            'before_stock' => $before,
            'after_stock' => $product->stock,
            'note' => 'Refund for ' . $sale->receipt_no,
            'user_id' => $user->id,
        ]));

        StockMovement::create($this->onlyExistingColumns('stock_movements', [
            'product_id' => $product->id,
            'department_id' => $departmentId,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'to_store_id' => $store?->id,
            'type' => 'REFUND',
            'movement_type' => 'REFUND',
            'quantity' => $quantity,
            'before_stock' => $before,
            'after_stock' => $product->stock,
            'quantity_before' => $storeSnapshot['before'] ?? $before,
            'quantity_changed' => abs($quantity),
            'quantity_after' => $storeSnapshot['after'] ?? $product->stock,
            'unit_cost' => $product->buy_price ?? 0,
            'total_cost' => ($product->buy_price ?? 0) * $quantity,
            'performed_by' => $user->id,
            'reference_type' => Refund::class,
            'reference_id' => $refund->id,
            'reason' => 'Refund for ' . $sale->receipt_no,
            'notes' => 'Refund for ' . $sale->receipt_no,
        ]));

        return $quantity;
    }

    private function reverseShiftTotals(Sale $sale): void
    {
        $shift = $sale->shift ?: Shift::find($sale->shift_id);

        if (!$shift) {
            return;
        }

        $shift->total_sales = max((float) $shift->total_sales - (float) $sale->grand_total, 0);

        $payments = $sale->payments->isNotEmpty()
            ? $sale->payments
            : collect([(object) ['method' => $sale->payment_method, 'amount' => $sale->amount_paid, 'change_amount' => $sale->change_amount]]);

        foreach ($payments as $payment) {
            $amount = max((float) $payment->amount - (float) ($payment->change_amount ?? 0), 0);

            match (Sale::normalizePaymentMethod($payment->method)) {
                'CASH' => $this->subtractShiftTotal($shift, 'cash_sales', $amount, true),
                'MOMO' => $this->subtractShiftTotal($shift, 'momo_sales', $amount),
                'AIRTEL_MONEY' => $this->subtractShiftTotal($shift, 'airtel_sales', $amount),
                'VISA' => $this->subtractShiftTotal($shift, 'visa_sales', $amount),
                'MASTER_CARD' => $this->subtractShiftTotal($shift, 'mastercard_sales', $amount),
                'BANK_TRANSFER' => $this->subtractShiftTotal($shift, 'bank_transfer_sales', $amount),
                default => null,
            };
        }

        $shift->save();
    }

    private function subtractShiftTotal(Shift $shift, string $column, float $amount, bool $cashExpected = false): void
    {
        $shift->{$column} = max((float) $shift->{$column} - $amount, 0);

        if ($cashExpected) {
            $shift->expected_cash = max((float) $shift->expected_cash - $amount, (float) $shift->opening_cash);
        }
    }

    private function onlyExistingColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return collect($data)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }
}
