<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStock;
use App\Services\AuditLogService;
use App\Services\BranchAccessService;
use App\Services\StoreStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockCountController extends Controller
{
    public function index(Request $request)
    {
        $branchAccess = app(BranchAccessService::class);
        $selectedBranchId = $branchAccess->selectedBranchId($request->user(), $request->integer('branch_id') ?: null);

        $counts = StockCount::with(['items', 'items.product'])
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stores = Store::where('active', true)
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->orderBy('sort_order')
            ->get();
        $products = Product::with('department')
            ->where('active', true)
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->orderBy('name')
            ->get();

        return view('store.stock-counts', compact('counts', 'stores', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'product_id' => ['required', 'exists:products,id'],
            'counted_quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $store = Store::findOrFail($validated['store_id']);
        $product = Product::findOrFail($validated['product_id']);
        $this->authorizeStore($request, $store);
        $storeStock = StoreStock::where('store_id', $store->id)->where('product_id', $product->id)->first();
        $systemQuantity = (float) ($storeStock?->quantity ?? $product->stock ?? 0);
        $countedQuantity = (float) $validated['counted_quantity'];
        $variance = $countedQuantity - $systemQuantity;
        $unitCost = (float) ($storeStock?->unit_cost ?: $product->buy_price ?: 0);

        $count = DB::transaction(function () use ($request, $validated, $store, $product, $systemQuantity, $countedQuantity, $variance, $unitCost) {
            $count = StockCount::create([
                'count_number' => 'SC-' . now()->format('Ymd-His') . '-' . random_int(100, 999),
                'store_id' => $store->id,
                'department_id' => $product->department_id ?: $store->department_id,
                'branch_id' => $request->user()->branch_id,
                'counted_by' => $request->user()->id,
                'status' => StockCount::STATUS_SUBMITTED,
                'count_date' => today(),
                'submitted_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            StockCountItem::create([
                'stock_count_id' => $count->id,
                'product_id' => $product->id,
                'branch_id' => $request->user()->branch_id,
                'barcode' => $product->barcode,
                'system_quantity' => $systemQuantity,
                'counted_quantity' => $countedQuantity,
                'variance_quantity' => $variance,
                'unit_cost' => $unitCost,
                'variance_value' => $variance * $unitCost,
                'reason' => $validated['reason'] ?? null,
            ]);

            AuditLogService::record([
                'action' => 'STOCK_COUNT_SUBMITTED',
                'module' => 'Inventory',
                'model' => $count,
                'department_id' => $count->department_id,
                'reference' => $count->count_number,
                'description' => 'Submitted physical stock count for approval. Stock was not adjusted yet.',
                'quantity_before' => $systemQuantity,
                'quantity_changed' => $variance,
                'quantity_after' => $countedQuantity,
                'severity' => abs($variance) > 0 ? 'WARNING' : 'INFO',
            ]);

            return $count;
        });

        return back()->with('success', 'Stock count ' . $count->count_number . ' submitted for approval.');
    }

    public function approve(Request $request, StockCount $stockCount, StoreStockService $storeStockService)
    {
        abort_unless($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'), 403);

        if (!$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $stockCount->counted_by === (int) $request->user()->id) {
            return back()->with('error', 'Separation of duties: counter cannot approve their own stock count.');
        }

        if ($stockCount->status !== StockCount::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted stock counts can be approved.');
        }

        DB::transaction(function () use ($request, $stockCount, $storeStockService) {
            $stockCount->load(['items.product', 'items',]);
            $store = Store::findOrFail($stockCount->store_id);

            foreach ($stockCount->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                $before = (float) $product->stock;
                $variance = (float) $item->variance_quantity;

                if ($variance === 0.0) {
                    continue;
                }

                if ($variance > 0) {
                    $product->increment('stock', $variance);
                    $product->refresh();
                    $snapshot = $storeStockService->increaseStoreOnly($product, $store, $variance, (float) $item->unit_cost);
                } else {
                    $decrease = abs($variance);

                    if ($before < $decrease) {
                        throw new \RuntimeException('Insufficient stock to apply negative variance for ' . $product->name . '.');
                    }

                    $product->decrement('stock', $decrease);
                    $product->refresh();
                    $snapshot = $storeStockService->decreaseStoreOnly($product, $store, $decrease);
                }

                StockMovement::create([
                    'product_id' => $product->id,
                    'department_id' => $product->department_id ?: $stockCount->department_id,
                    'branch_id' => $stockCount->branch_id,
                    'user_id' => $request->user()->id,
                    'from_store_id' => $variance < 0 ? $store->id : null,
                    'to_store_id' => $variance > 0 ? $store->id : null,
                    'type' => 'STOCK_ADJUSTMENT',
                    'movement_type' => 'STOCK_COUNT_VARIANCE',
                    'quantity' => abs($variance),
                    'before_stock' => $before,
                    'after_stock' => $product->stock,
                    'quantity_before' => $snapshot['before'] ?? $before,
                    'quantity_changed' => $variance,
                    'quantity_after' => $snapshot['after'] ?? $product->stock,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => abs($variance) * (float) $item->unit_cost,
                    'performed_by' => $request->user()->id,
                    'approved_by' => $request->user()->id,
                    'reference_type' => StockCount::class,
                    'reference_id' => $stockCount->id,
                    'reason' => 'Approved physical stock count variance',
                    'notes' => $item->reason,
                ]);
            }

            $stockCount->update([
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'status' => StockCount::STATUS_APPROVED,
            ]);

            AuditLogService::record([
                'action' => 'STOCK_COUNT_APPROVED',
                'module' => 'Inventory',
                'model' => $stockCount,
                'department_id' => $stockCount->department_id,
                'reference' => $stockCount->count_number,
                'description' => 'Approved stock count and applied inventory variance.',
                'severity' => 'WARNING',
            ]);
        });

        return back()->with('success', 'Stock count approved and variance applied.');
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if ($request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'STORE_KEEPER')) {
            return;
        }

        if ($request->user()->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF')) {
            abort_unless($store->code === 'KITCHEN', 403);
        }

        if ($request->user()->hasOperationalRole('BAR_MANAGER', 'BAR_CHIEF')) {
            abort_unless($store->code === 'BAR', 403);
        }
    }
}
