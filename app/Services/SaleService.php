<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Customer;
use App\Models\DiscountApproval;
use App\Models\Payment;
use App\Models\Recipe;
use App\Models\RestaurantTable;
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
        ?string $notes = null,
        ?int $customerId = null,
        ?int $tableId = null,
        array $payments = [],
        float $discount = 0,
        ?string $discountReason = null
    ): Sale {
        return DB::transaction(function () use (
            $cart,
            $user,
            $paymentMethod,
            $amountPaid,
            $customerName,
            $notes,
            $customerId,
            $tableId,
            $payments,
            $discount,
            $discountReason
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
            $this->authorizeDiscount($user, $subtotal, $discount, $discountReason);

            $taxService = app(TaxService::class);
            $taxableProductIds = $this->taxableProductIds($cart);
            $taxableSubtotal = collect($cart)->sum(function ($item) use ($taxableProductIds) {
                $productId = (int) ($item['product_id'] ?? $item['id']);

                return in_array($productId, $taxableProductIds, true)
                    ? (float) $item['price'] * (float) $item['quantity']
                    : 0;
            });
            $nonTaxableSubtotal = max($subtotal - $taxableSubtotal, 0);
            $taxableDiscount = $subtotal > 0 ? $discount * ($taxableSubtotal / $subtotal) : 0;
            $nonTaxableDiscount = max($discount - $taxableDiscount, 0);
            $taxableTotals = $taxService->saleTotals($taxableSubtotal, $taxableDiscount);
            $nonTaxableNet = max($nonTaxableSubtotal - $nonTaxableDiscount, 0);
            $saleTotals = array_merge($taxableTotals, [
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'grand_total' => round($taxableTotals['grand_total'] + $nonTaxableNet, 2),
                'non_taxable_amount' => round($nonTaxableNet, 2),
            ]);
            $saleTotals['fiscal_payload']['total'] = $saleTotals['grand_total'];
            $saleTotals['fiscal_payload']['taxable_amount'] = $saleTotals['taxable_amount'];
            $saleTotals['fiscal_payload']['non_taxable_amount'] = $saleTotals['non_taxable_amount'];
            $tax = $saleTotals['tax'];
            $grandTotal = $saleTotals['grand_total'];
            $payments = $this->normalizedPayments($payments, $paymentMethod, $amountPaid, $grandTotal);
            $paidTotal = collect($payments)->sum('amount');
            $changeAmount = max($paidTotal - $grandTotal, 0);
            $creditDue = max($grandTotal - min($paidTotal, $grandTotal), 0);
            $customer = null;

            if ($customerId) {
                $customer = Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();

                if ($customer->status !== Customer::STATUS_ACTIVE) {
                    throw new \Exception('Customer account is not active.');
                }

                $customerName = $customerName ?: $customer->name;
            }

            if ($creditDue > 0 && !$customer) {
                throw new \Exception('Partial or credit sale requires a customer account.');
            }

            if ($customer && $creditDue > 0) {
                app(AccountsReceivableService::class)->validateCreditSale($customer, $creditDue, $user);
            }

            $table = $tableId ? RestaurantTable::find($tableId) : null;

            if ($table && $table->status === 'OUT_OF_SERVICE') {
                throw new \Exception('Selected table is out of service.');
            }

            $sale = Sale::create([
                'receipt_no' => 'RCPT-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'branch_id' => $user->branch_id,
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'customer_id' => $customer?->id,
                'customer_name' => $customerName,
                'table_id' => $table?->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'taxable_amount' => $saleTotals['taxable_amount'],
                'vat_rate' => $saleTotals['vat_rate'],
                'discount' => $discount,
                'discount_reason' => $discountReason,
                'discount_approved_by' => $discount > 0 ? $user->id : null,
                'grand_total' => $grandTotal,
                'amount_paid' => min($paidTotal, $grandTotal),
                'change_amount' => $changeAmount,
                'payment_method' => $payments[0]['method'] ?? $paymentMethod,
                'payment_status' => $creditDue > 0 ? 'PARTIAL' : 'PAID',
                'credit_due' => $creditDue,
                'sale_status' => 'COMPLETED',
                'notes' => $notes,
                'fiscal_status' => ($taxService->setting('fiscal_ebm_mode', 'MANUAL') === 'MANUAL') ? 'MANUAL_PENDING' : 'NOT_SUBMITTED',
                'fiscal_payload' => $saleTotals['fiscal_payload'],
                'tax_summary' => [
                    'vat_rate' => $saleTotals['vat_rate'],
                    'taxable_amount' => $saleTotals['taxable_amount'],
                    'non_taxable_amount' => $saleTotals['non_taxable_amount'],
                    'vat_amount' => $tax,
                    'prices_include_vat' => $saleTotals['prices_include_vat'],
                ],
            ]);

            if ($discount > 0) {
                DiscountApproval::create([
                    'sale_id' => $sale->id,
                    'requested_by' => $user->id,
                    'approved_by' => $sale->discount_approved_by,
                    'branch_id' => $sale->branch_id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'discount_percent' => $subtotal > 0 ? ($discount / $subtotal) * 100 : 0,
                    'status' => 'AUTO_APPROVED_WITHIN_LIMIT',
                    'reason' => $discountReason,
                    'approved_at' => now(),
                ]);
            }

            foreach ($payments as $index => $payment) {
                $paymentChange = $index === 0 ? $changeAmount : 0;

                Payment::create([
                    'sale_id' => $sale->id,
                    'shift_id' => $shift->id,
                    'customer_id' => $customer?->id,
                    'branch_id' => $sale->branch_id,
                    'received_by' => $user->id,
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'change_amount' => $paymentChange,
                    'reference' => $payment['reference'] ?? null,
                    'provider_name' => $payment['provider_name'] ?? $this->defaultProviderFor($payment['method']),
                    'payment_reference' => $payment['payment_reference'] ?? $payment['reference'] ?? null,
                    'transaction_id' => $payment['transaction_id'] ?? null,
                    'payment_status' => $payment['payment_status'] ?? Payment::STATUS_COMPLETED,
                    'reconciliation_status' => $payment['method'] === 'CASH' ? 'NOT_REQUIRED' : 'UNMATCHED',
                    'idempotency_key' => $payment['idempotency_key'] ?? $this->paymentIdempotencyKey($sale->receipt_no, $index, $payment),
                    'status' => Payment::STATUS_COMPLETED,
                    'paid_at' => now(),
                    'metadata' => $payment['metadata'] ?? null,
                ]);
            }

            if ($customer && $creditDue > 0) {
                app(AccountsReceivableService::class)->postCreditSale($sale, $customer, $creditDue, $user);
            }

            if ($table) {
                $table->update([
                    'status' => RestaurantTable::STATUS_OCCUPIED,
                    'assigned_user_id' => $user->id,
                ]);
            }

            foreach ($cart as $item) {
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];

                $product = Product::whereKey($item['product_id'] ?? $item['id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $recipe = Recipe::with(['items.ingredient', 'items.store'])
                    ->where('product_id', $product->id)
                    ->where('active', true)
                    ->first();
                $costPrice = $recipe
                    ? $this->recipeUnitCost($recipe)
                    : (float) ($product->buy_price ?? 0);

                if (!$recipe && $product->track_stock && $product->stock < $quantity) {
                    throw new \Exception($product->name . ' stock insufficient.');
                }

                $lineSubtotal = $quantity * $price;
                $lineDiscount = $subtotal > 0 ? ($discount * ($lineSubtotal / $subtotal)) : 0;
                $isTaxable = $this->productIsTaxable($product);
                $lineTotals = $isTaxable
                    ? $taxService->saleTotals($lineSubtotal, $lineDiscount)
                    : [
                        'tax' => 0,
                        'taxable_amount' => 0,
                        'vat_rate' => 0,
                        'grand_total' => max($lineSubtotal - $lineDiscount, 0),
                    ];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->product_code,
                    'department_id' => $product->department_id,
                    'department_name' => $product->department?->name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'unit_price' => $price,
                    'cost_price' => $costPrice,
                    'discount' => $lineDiscount,
                    'tax' => $lineTotals['tax'],
                    'taxable_amount' => $lineTotals['taxable_amount'],
                    'is_taxable' => $isTaxable,
                    'tax_category' => $product->tax_category ?? ($isTaxable ? 'VATABLE' : 'EXEMPT'),
                    'vat_rate' => $lineTotals['vat_rate'],
                    'vat_amount' => $lineTotals['tax'],
                    'subtotal' => $lineSubtotal,
                    'total' => $lineTotals['grand_total'],
                    'profit' => $lineTotals['grand_total'] - (($costPrice * $quantity) + $lineTotals['tax']),
                    'status' => 'ACTIVE',
                    'ticket_status' => 'PENDING',
                ]);

                if ($recipe) {
                    $this->consumeRecipeIngredients($recipe, $quantity, $user, $sale);
                } elseif ($product->track_stock) {
                    $beforeStock = $product->stock;

                    $product->decrement('stock', $quantity);
                    $product->refresh();

                    $storeStockService = app(StoreStockService::class);
                    $store = $storeStockService->defaultStoreFor($product);
                    $storeSnapshot = null;

                    if ($store) {
                        $storeSnapshot = $storeStockService->decreaseStoreOnly(
                            $product,
                            $store,
                            $quantity,
                            (float) ($product->buy_price ?? 0)
                        );
                    }

                    Stock::create([
                        'product_id' => $product->id,
                        'department_id' => $product->department_id,
                        'type' => 'sale',
                        'quantity' => $quantity,
                        'before_stock' => $beforeStock,
                        'after_stock' => $product->stock,
                        'note' => 'POS sale ' . $sale->receipt_no,
                        'user_id' => $user->id,
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'department_id' => $product->department_id,
                        'branch_id' => $user->branch_id,
                        'user_id' => $user->id,
                        'from_store_id' => $store?->id,
                        'type' => 'SALE',
                        'movement_type' => 'SALE',
                        'quantity' => $quantity,
                        'before_stock' => $beforeStock,
                        'after_stock' => $product->stock,
                        'quantity_before' => $storeSnapshot['before'] ?? $beforeStock,
                        'quantity_changed' => -abs($quantity),
                        'quantity_after' => $storeSnapshot['after'] ?? $product->stock,
                        'unit_cost' => $product->buy_price ?? 0,
                        'total_cost' => ($product->buy_price ?? 0) * $quantity,
                        'performed_by' => $user->id,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'reason' => 'POS checkout',
                        'notes' => 'POS checkout ' . $sale->receipt_no,
                    ]);
                }
            }

            $shift->total_sales += $grandTotal;

            foreach ($payments as $payment) {
                $netPayment = max((float) $payment['amount'] - ($payment['method'] === 'CASH' ? $changeAmount : 0), 0);

                match ($payment['method']) {
                    'CASH' => $this->addShiftTotal($shift, 'cash_sales', $netPayment, true),
                    'MOMO' => $this->addShiftTotal($shift, 'momo_sales', $netPayment),
                    'AIRTEL_MONEY' => $this->addShiftTotal($shift, 'airtel_sales', $netPayment),
                    'VISA' => $this->addShiftTotal($shift, 'visa_sales', $netPayment),
                    'MASTER_CARD' => $this->addShiftTotal($shift, 'mastercard_sales', $netPayment),
                    'BANK_TRANSFER' => $this->addShiftTotal($shift, 'bank_transfer_sales', $netPayment),
                    default => null,
                };
            }

            $shift->save();

            $sale->load('items.product.department', 'items.department', 'payments');
            app(OrderTicketService::class)->createForSale($sale);
            app(AccountingService::class)->postSale($sale);

            AuditService::log(
                'SALE_COMPLETED',
                'Sale',
                'Completed sale ' . $sale->receipt_no,
                $sale->id,
                null,
                [
                    'receipt_no' => $sale->receipt_no,
                    'grand_total' => $grandTotal,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $sale->payment_status,
                    'tax' => $tax,
                    'credit_due' => $creditDue,
                ],
                'INFO',
                [
                    'module' => 'Sales',
                    'event_type' => 'FINANCIAL',
                    'department_id' => $sale->items()->value('department_id'),
                    'branch_id' => $sale->branch_id,
                    'reference' => $sale->receipt_no,
                    'amount' => $grandTotal,
                    'metadata' => [
                        'payment_method' => $paymentMethod,
                        'payments' => $payments,
                        'vat_rate' => $saleTotals['vat_rate'],
                        'table_id' => $table?->id,
                        'customer_id' => $customer?->id,
                        'line_items' => count($cart),
                    ],
                ]
            );

            app(SyncOutboxService::class)->push('SALE_COMPLETED', $sale, [
                'receipt_no' => $sale->receipt_no,
                'grand_total' => $sale->grand_total,
                'payment_status' => $sale->payment_status,
                'created_at' => $sale->created_at?->toDateTimeString(),
            ]);

            return $sale;
        });
    }

    private function normalizedPayments(array $payments, string $fallbackMethod, float $amountPaid, float $grandTotal): array
    {
        $normalized = collect($payments)
            ->map(function ($payment) {
                return [
                    'method' => Sale::normalizePaymentMethod($payment['method'] ?? null),
                    'amount' => round(max((float) ($payment['amount'] ?? 0), 0), 2),
                    'reference' => $payment['reference'] ?? null,
                    'provider_name' => $payment['provider_name'] ?? null,
                    'payment_reference' => $payment['payment_reference'] ?? null,
                    'transaction_id' => $payment['transaction_id'] ?? null,
                    'payment_status' => $payment['payment_status'] ?? Payment::STATUS_COMPLETED,
                    'idempotency_key' => $payment['idempotency_key'] ?? null,
                    'metadata' => $payment['metadata'] ?? null,
                ];
            })
            ->filter(fn ($payment) => $payment['amount'] > 0 || $payment['method'] === 'CREDIT')
            ->values()
            ->all();

        if ($normalized !== []) {
            return $normalized;
        }

        $fallbackMethod = Sale::normalizePaymentMethod($fallbackMethod);
        $amount = $fallbackMethod === 'CREDIT'
            ? 0
            : ($fallbackMethod === 'CASH' ? $amountPaid : $grandTotal);

        return [[
            'method' => $fallbackMethod,
            'amount' => round(max($amount, 0), 2),
            'reference' => null,
            'provider_name' => null,
            'payment_reference' => null,
            'transaction_id' => null,
            'payment_status' => Payment::STATUS_COMPLETED,
            'idempotency_key' => null,
            'metadata' => null,
        ]];
    }

    private function taxableProductIds(array $cart): array
    {
        $productIds = collect($cart)
            ->map(fn ($item) => (int) ($item['product_id'] ?? $item['id']))
            ->filter()
            ->unique()
            ->values();

        return Product::whereIn('id', $productIds)
            ->get()
            ->filter(fn (Product $product) => $this->productIsTaxable($product))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function productIsTaxable(Product $product): bool
    {
        return (bool) ($product->is_taxable ?? true)
            && strtoupper((string) ($product->tax_category ?? 'VATABLE')) !== 'EXEMPT';
    }

    private function defaultProviderFor(string $method): ?string
    {
        return match (Sale::normalizePaymentMethod($method)) {
            'MOMO' => 'MTN MoMo',
            'AIRTEL_MONEY' => 'Airtel Money',
            'VISA', 'MASTER_CARD' => 'Card Processor',
            'BANK_TRANSFER' => 'Bank',
            default => null,
        };
    }

    private function paymentIdempotencyKey(string $receiptNo, int $index, array $payment): string
    {
        return hash('sha256', implode('|', [
            $receiptNo,
            $index,
            $payment['method'] ?? 'CASH',
            $payment['amount'] ?? 0,
            $payment['payment_reference'] ?? $payment['reference'] ?? '',
            $payment['transaction_id'] ?? '',
        ]));
    }

    private function authorizeDiscount($user, float $subtotal, float $discount, ?string $reason = null): void
    {
        if ($discount <= 0) {
            return;
        }

        if (blank($reason)) {
            throw new \Exception('Discount reason is required.');
        }

        if ($discount > $subtotal) {
            throw new \Exception('Discount cannot exceed sale subtotal.');
        }

        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return;
        }

        $taxService = app(TaxService::class);
        $roleLimit = match (true) {
            $user->hasOperationalRole('MANAGER') => (float) $taxService->setting('discount_manager_limit', 10),
            $user->hasOperationalRole('CASHIER') => (float) $taxService->setting('discount_cashier_limit', 0),
            $user->hasOperationalRole('WAITER', 'SERVER') => (float) $taxService->setting('discount_waiter_limit', 0),
            default => 0,
        };

        $discountPercent = $subtotal > 0 ? ($discount / $subtotal) * 100 : 0;

        if ($discountPercent > $roleLimit) {
            throw new \Exception('Discount exceeds your role approval limit.');
        }
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

    private function recipeUnitCost(Recipe $recipe): float
    {
        $yieldQuantity = max((float) $recipe->yield_quantity, 1);

        return (float) $recipe->items->sum(function ($item) {
            $ingredientCost = (float) ($item->unit_cost ?: $item->ingredient?->buy_price ?: 0);

            return (float) $item->quantity * $ingredientCost;
        }) / $yieldQuantity;
    }

    private function consumeRecipeIngredients(Recipe $recipe, int $soldQuantity, $user, Sale $sale): void
    {
        $yieldQuantity = max((float) $recipe->yield_quantity, 1);
        $multiplier = $soldQuantity / $yieldQuantity;
        $storeStockService = app(StoreStockService::class);

        foreach ($recipe->items as $recipeItem) {
            $ingredient = Product::whereKey($recipeItem->ingredient_product_id)
                ->lockForUpdate()
                ->firstOrFail();
            $requiredQuantity = (float) $recipeItem->quantity * $multiplier;

            if ($ingredient->track_stock && (float) $ingredient->stock < $requiredQuantity) {
                throw new \Exception($ingredient->name . ' ingredient stock insufficient for ' . ($recipe->product?->name ?? $recipe->name ?? 'recipe') . '.');
            }

            $beforeStock = (float) $ingredient->stock;
            $ingredient->decrement('stock', $requiredQuantity);
            $ingredient->refresh();

            $store = $recipeItem->store ?: $storeStockService->defaultStoreFor($ingredient);
            $storeSnapshot = null;

            if ($store) {
                $storeSnapshot = $storeStockService->decreaseStoreOnly(
                    $ingredient,
                    $store,
                    $requiredQuantity,
                    (float) ($recipeItem->unit_cost ?: $ingredient->buy_price ?? 0)
                );
            }

            Stock::create([
                'product_id' => $ingredient->id,
                'department_id' => $ingredient->department_id ?: $recipe->department_id,
                'type' => 'recipe_consumption',
                'quantity' => $requiredQuantity,
                'before_stock' => $beforeStock,
                'after_stock' => $ingredient->stock,
                'note' => 'Recipe consumption for ' . $sale->receipt_no,
                'user_id' => $user->id,
            ]);

            $storeStockService->recordMovement(
                product: $ingredient,
                user: $user,
                type: 'RECIPE_CONSUMPTION',
                quantity: $requiredQuantity,
                beforeStock: $beforeStock,
                afterStock: (float) $ingredient->stock,
                referenceType: Sale::class,
                referenceId: $sale->id,
                fromStore: $store,
                quantityBefore: $storeSnapshot['before'] ?? $beforeStock,
                quantityAfter: $storeSnapshot['after'] ?? (float) $ingredient->stock,
                unitCost: (float) ($recipeItem->unit_cost ?: $ingredient->buy_price ?? 0),
                note: 'Recipe consumption for ' . $sale->receipt_no
            );
        }
    }
}
