<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class StoreStockService
{
    public function defaultStoreFor(Product $product): ?Store
    {
        if ($product->default_store_id) {
            $store = Store::find($product->default_store_id);

            if ($store) {
                return $store;
            }
        }

        $departmentCode = strtoupper((string) $product->department?->code);

        return Store::where('code', match ($departmentCode) {
            'KITCHEN' => 'KITCHEN',
            'BAR' => 'BAR',
            default => 'MAIN',
        })->first();
    }

    public function ensureBalance(Product $product, Store $store): StoreStock
    {
        return StoreStock::firstOrCreate(
            [
                'store_id' => $store->id,
                'product_id' => $product->id,
            ],
            [
                'department_id' => $product->department_id ?: $store->department_id,
                'quantity' => 0,
                'alert_stock' => $product->alert_stock ?: 0,
                'unit_cost' => $product->buy_price ?: 0,
                'total_value' => 0,
            ]
        );
    }

    public function receiveIntoStore(
        Product $product,
        Store $store,
        float $quantity,
        User $user,
        string $referenceType,
        int $referenceId,
        ?float $unitCost = null,
        ?string $note = null,
        ?string $movementType = null
    ): array {
        $productBefore = (float) $product->stock;
        $product->increment('stock', $quantity);
        $product->refresh();

        $storeSnapshot = $this->increaseStoreOnly($product, $store, $quantity, $unitCost);

        $this->recordMovement(
            product: $product,
            user: $user,
            type: $movementType ?: 'PURCHASE_RECEIVED',
            quantity: $quantity,
            beforeStock: $productBefore,
            afterStock: (float) $product->stock,
            referenceType: $referenceType,
            referenceId: $referenceId,
            toStore: $store,
            quantityBefore: $storeSnapshot['before'],
            quantityAfter: $storeSnapshot['after'],
            unitCost: $unitCost,
            note: $note ?: 'Supplier delivery received'
        );

        return [
            'product_before' => $productBefore,
            'product_after' => (float) $product->stock,
            'store_before' => $storeSnapshot['before'],
            'store_after' => $storeSnapshot['after'],
        ];
    }

    public function removeFromStore(
        Product $product,
        Store $store,
        float $quantity,
        User $user,
        string $movementType,
        string $referenceType,
        int $referenceId,
        ?float $unitCost = null,
        ?string $note = null,
        ?int $approvedBy = null
    ): array {
        $productBefore = (float) $product->stock;

        if ($productBefore < $quantity) {
            throw new \RuntimeException($product->name . ' does not have enough global stock.');
        }

        $storeSnapshot = $this->decreaseStoreOnly($product, $store, $quantity, $unitCost);

        $product->decrement('stock', $quantity);
        $product->refresh();

        $this->recordMovement(
            product: $product,
            user: $user,
            type: $movementType,
            quantity: $quantity,
            beforeStock: $productBefore,
            afterStock: (float) $product->stock,
            referenceType: $referenceType,
            referenceId: $referenceId,
            fromStore: $store,
            quantityBefore: $storeSnapshot['before'],
            quantityAfter: $storeSnapshot['after'],
            unitCost: $unitCost,
            note: $note,
            approvedBy: $approvedBy
        );

        return [
            'product_before' => $productBefore,
            'product_after' => (float) $product->stock,
            'store_before' => $storeSnapshot['before'],
            'store_after' => $storeSnapshot['after'],
        ];
    }

    public function decreaseStoreOnly(
        Product $product,
        Store $store,
        float $quantity,
        ?float $unitCost = null
    ): array {
        $balance = $this->ensureBalance($product, $store);
        $before = (float) $balance->quantity;

        if ($before < $quantity) {
            throw new \RuntimeException($product->name . ' is insufficient in ' . $store->name . '.');
        }

        $after = $before - $quantity;
        $cost = $unitCost ?? (float) ($balance->unit_cost ?: $product->buy_price ?: 0);

        $balance->update([
            'quantity' => $after,
            'unit_cost' => $cost,
            'total_value' => $after * $cost,
        ]);

        return ['before' => $before, 'after' => $after];
    }

    public function increaseStoreOnly(
        Product $product,
        Store $store,
        float $quantity,
        ?float $unitCost = null
    ): array {
        $balance = $this->ensureBalance($product, $store);
        $before = (float) $balance->quantity;
        $after = $before + $quantity;
        $cost = $unitCost ?? (float) ($balance->unit_cost ?: $product->buy_price ?: 0);

        $balance->update([
            'department_id' => $product->department_id ?: $store->department_id,
            'quantity' => $after,
            'alert_stock' => $product->alert_stock ?: $balance->alert_stock,
            'unit_cost' => $cost,
            'total_value' => $after * $cost,
        ]);

        return ['before' => $before, 'after' => $after];
    }

    public function transfer(
        Product $product,
        Store $fromStore,
        Store $toStore,
        float $quantity,
        User $user,
        string $referenceType,
        int $referenceId,
        ?string $note = null
    ): array {
        $source = $this->decreaseStoreOnly($product, $fromStore, $quantity);
        $destination = $this->increaseStoreOnly($product, $toStore, $quantity);

        $this->recordMovement(
            product: $product,
            user: $user,
            type: 'STORE_TRANSFER',
            quantity: $quantity,
            beforeStock: (float) $product->stock,
            afterStock: (float) $product->stock,
            referenceType: $referenceType,
            referenceId: $referenceId,
            fromStore: $fromStore,
            toStore: $toStore,
            quantityBefore: $source['before'],
            quantityAfter: $source['after'],
            note: $note ?: 'Store transfer'
        );

        return [
            'from_before' => $source['before'],
            'from_after' => $source['after'],
            'to_before' => $destination['before'],
            'to_after' => $destination['after'],
        ];
    }

    public function recordMovement(
        Product $product,
        User $user,
        string $type,
        float $quantity,
        float $beforeStock,
        float $afterStock,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?Store $fromStore = null,
        ?Store $toStore = null,
        ?float $quantityBefore = null,
        ?float $quantityAfter = null,
        ?float $unitCost = null,
        ?string $note = null,
        ?int $approvedBy = null
    ): ?StockMovement {
        if (!Schema::hasTable('stock_movements')) {
            return null;
        }

        $payload = [
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'from_store_id' => $fromStore?->id,
            'to_store_id' => $toStore?->id,
            'type' => $type,
            'movement_type' => $type,
            'quantity' => $quantity,
            'before_stock' => $beforeStock,
            'after_stock' => $afterStock,
            'quantity_before' => $quantityBefore,
            'quantity_changed' => in_array($type, ['SALE', 'DAMAGE', 'STORE_ISSUE', 'STOCK_OUT', 'SUPPLIER_RETURN', 'RECIPE_CONSUMPTION'], true)
                ? -abs($quantity)
                : abs($quantity),
            'quantity_after' => $quantityAfter,
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost !== null ? $quantity * $unitCost : null,
            'performed_by' => $user->id,
            'approved_by' => $approvedBy,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reason' => $note,
            'notes' => $note,
        ];

        return StockMovement::create(
            collect($payload)
                ->filter(fn ($value, $column) => Schema::hasColumn('stock_movements', $column))
                ->all()
        );
    }
}
