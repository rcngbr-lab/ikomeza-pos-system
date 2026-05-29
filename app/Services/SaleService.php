<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function checkout(
        array $cart,
        $user,
        string $paymentMethod = 'CASH',
        float $amountPaid = 0,
        ?string $customerName = null,
        ?string $notes = null
    ): Sale {
        return DB::transaction(function () use (
            $cart,
            $user,
            $paymentMethod,
            $amountPaid,
            $customerName,
            $notes
        ) {
            $shift = Shift::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('is_open', true)
                        ->orWhere('status', 'OPEN');
                })
                ->latest()
                ->first();

            if (!$shift) {
                throw new \Exception('No active shift opened.');
            }

            $paymentMethod = Sale::normalizePaymentMethod($paymentMethod);
            $subtotal = collect($cart)->sum(
                fn ($item) => (float) $item['price'] * (float) $item['quantity']
            );
            $tax = 0;
            $discount = 0;
            $grandTotal = $subtotal + $tax - $discount;

            if ($paymentMethod === 'CASH') {
                if ($amountPaid < $grandTotal) {
                    throw new \Exception('Insufficient cash payment.');
                }

                $changeAmount = $amountPaid - $grandTotal;
            } else {
                $amountPaid = $grandTotal;
                $changeAmount = 0;
            }

            $sale = Sale::create([
                'receipt_no' => 'RCPT-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'customer_name' => $customerName,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
                'payment_method' => $paymentMethod,
                'payment_status' => 'PAID',
                'sale_status' => 'COMPLETED',
                'notes' => $notes,
            ]);

            foreach ($cart as $item) {
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];

                $product = Product::whereKey($item['product_id'] ?? $item['id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->track_stock && $product->stock < $quantity) {
                    throw new \Exception($product->name . ' stock insufficient.');
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'unit_price' => $price,
                    'cost_price' => $product->buy_price ?? 0,
                    'discount' => 0,
                    'tax' => 0,
                    'subtotal' => $quantity * $price,
                    'total' => $quantity * $price,
                    'profit' => ($price - ($product->buy_price ?? 0)) * $quantity,
                    'status' => 'ACTIVE',
                ]);

                if ($product->track_stock) {
                    $beforeStock = $product->stock;

                    $product->decrement('stock', $quantity);
                    $product->refresh();

                    Stock::create([
                        'product_id' => $product->id,
                        'type' => 'sale',
                        'quantity' => $quantity,
                        'before_stock' => $beforeStock,
                        'after_stock' => $product->stock,
                        'note' => 'POS sale ' . $sale->receipt_no,
                        'user_id' => $user->id,
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'branch_id' => $user->branch_id,
                        'user_id' => $user->id,
                        'type' => 'SALE',
                        'quantity' => $quantity,
                        'before_stock' => $beforeStock,
                        'after_stock' => $product->stock,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'reason' => 'POS checkout',
                    ]);
                }
            }

            $shift->total_sales += $grandTotal;

            match ($paymentMethod) {
                'CASH' => $this->addShiftTotal($shift, 'cash_sales', $grandTotal, true),
                'MOMO' => $this->addShiftTotal($shift, 'momo_sales', $grandTotal),
                'AIRTEL_MONEY' => $this->addShiftTotal($shift, 'airtel_sales', $grandTotal),
                'VISA' => $this->addShiftTotal($shift, 'visa_sales', $grandTotal),
                'MASTER_CARD' => $this->addShiftTotal($shift, 'mastercard_sales', $grandTotal),
                'BANK_TRANSFER' => $this->addShiftTotal($shift, 'bank_transfer_sales', $grandTotal),
                default => null,
            };

            $shift->save();

            AuditService::log(
                'SALE',
                'Sale',
                'Completed sale ' . $sale->receipt_no
            );

            return $sale;
        });
    }

    private function addShiftTotal(
        Shift $shift,
        string $column,
        float $amount,
        bool $cashExpected = false
    ): void {
        $shift->{$column} += $amount;

        if ($cashExpected) {
            $shift->expected_cash = ($shift->expected_cash ?: $shift->opening_cash) + $amount;
        }
    }
}
