<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Refund;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\DepartmentAccessService;
use App\Services\StoreStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Services\SaleService;
class SaleController extends Controller
{
    protected $saleService;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        SaleService $saleService
    ) {
        $this->saleService = $saleService;
    }

    /*
    |--------------------------------------------------------------------------
    | SALES LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTER + SEARCH
        |--------------------------------------------------------------------------
        */

        $filter = $request->filter ?? 'all';

        $search = $request->search ?? '';

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $departmentAccess = app(DepartmentAccessService::class);

        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $request->user(),
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($request->user());

        $query = Sale::with('user', 'items.department');

        if ($selectedDepartmentId) {
            $query->whereHas('items', fn ($items) => $items->where('department_id', $selectedDepartmentId));
        }

        /*
        |--------------------------------------------------------------------------
        | USER
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | CASHIER RESTRICTION
        |--------------------------------------------------------------------------
        */

        if (
            $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
            
        ) {

            $query->where(
                'user_id',
                $user->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        if ($filter == 'daily') {

            $query->whereDate(
                'created_at',
                today()
            );
        }

        elseif ($filter == 'weekly') {

            $query->whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]
            );
        }

        elseif ($filter == 'monthly') {

            $query->whereMonth(
                'created_at',
                now()->month
            )->whereYear(
                'created_at',
                now()->year
            );
        }

        elseif ($filter == 'yearly') {

            $query->whereYear(
                'created_at',
                now()->year
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if (!empty($search)) {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'receipt_no',
                    'LIKE',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'sale_status',
                    'LIKE',
                    '%' . $search . '%'
                )

                ->orWhereHas('user', function ($u) use ($search) {

                    $u->where(
                        'name',
                        'LIKE',
                        '%' . $search . '%'
                    );

                });

            });
        }

        /*
        |--------------------------------------------------------------------------
        | RESULTS
        |--------------------------------------------------------------------------
        */

        $sales = $query
            ->latest()
            ->paginate(10);

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        $grossSales = (clone $query)
            ->sum('grand_total');

        $refundedSales = Sale::refundedAmountFor($query);

        $totalSales = (clone $query)
            ->revenueBearing()
            ->sum('grand_total');

        $totalTransactions = (clone $query)
            ->revenueBearing()
            ->count();

        $grossTransactions = (clone $query)
            ->count();

        $refundedTransactions = (clone $query)
            ->refundedOnly()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'sales.index',
            compact(
                'sales',
                'totalSales',
                'grossSales',
                'refundedSales',
                'totalTransactions',
                'grossTransactions',
                'refundedTransactions',
                'filter',
                'search',
                'selectedDepartmentId',
                'departments'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT RECEIPT
    |--------------------------------------------------------------------------
    */

    public function print($id)
    {
        $sale = Sale::with([
            'items.product.department',
            'items.department',
            'user'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | CASHIER SECURITY
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        if (
            $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
            &&
            $sale->user_id != $user->id
        ) {

            abort(403);
        }

        return view(
            'sales.print',
            compact('sale')
        );
    }


public function refund(Request $request, $id)
{
    $request->validate([
        'refund_reason' => ['nullable', 'string', 'max:500'],
    ]);

    $sale = Sale::with('items.department')->findOrFail($id);

    if ((bool) $sale->is_refunded || $sale->sale_status === 'REFUNDED') {

        return back()->with(
            'error',
            'Sale already refunded.'
        );
    }

    $restoredUnits = 0;

    try {
        DB::transaction(function () use ($sale, $request, &$restoredUnits) {
            $refund = Refund::create($this->onlyExistingColumns('refunds', [

                'sale_id' => $sale->id,

                'user_id' => auth()->id(),

                'amount' => $sale->grand_total,

                'reason' => $request->refund_reason,

                'status' => Refund::STATUS_COMPLETED,

                'refunded_at' => now(),

            ]));

        foreach ($sale->items as $item) {

            $product = Product::whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {

                $before = $product->stock;

                $product->increment(
                    'stock',
                    $item->quantity
                );

                $product->refresh();
                $restoredUnits += (int) $item->quantity;

                $storeStockService = app(StoreStockService::class);
                $store = $storeStockService->defaultStoreFor($product);
                $storeSnapshot = null;

                if ($store) {
                    $storeSnapshot = $storeStockService->increaseStoreOnly(
                        $product,
                        $store,
                        (float) $item->quantity,
                        (float) ($product->buy_price ?? 0)
                    );
                }

                Stock::create($this->onlyExistingColumns('stocks', [
                    'product_id' => $product->id,
                    'department_id' => $product->department_id ?: $item->department_id,
                    'type' => 'refund',
                    'quantity' => $item->quantity,
                    'before_stock' => $before,
                    'after_stock' => $product->stock,
                    'note' => 'Refund for ' . $sale->receipt_no,
                    'user_id' => auth()->id(),
                ]));

                StockMovement::create($this->onlyExistingColumns('stock_movements', [
                    'product_id' => $product->id,
                    'department_id' => $product->department_id ?: $item->department_id,
                    'branch_id' => auth()->user()->branch_id,
                    'user_id' => auth()->id(),
                    'to_store_id' => $store?->id,
                    'type' => 'REFUND',
                    'movement_type' => 'REFUND',
                    'quantity' => $item->quantity,
                    'before_stock' => $before,
                    'after_stock' => $product->stock,
                    'quantity_before' => $storeSnapshot['before'] ?? $before,
                    'quantity_changed' => abs((float) $item->quantity),
                    'quantity_after' => $storeSnapshot['after'] ?? $product->stock,
                    'unit_cost' => $product->buy_price ?? 0,
                    'total_cost' => ($product->buy_price ?? 0) * (float) $item->quantity,
                    'performed_by' => auth()->id(),
                    'reference_type' => Refund::class,
                    'reference_id' => $refund->id,
                    'reason' => trim('Refund for ' . $sale->receipt_no . ' ' . ($request->refund_reason ?? '')),
                    'notes' => trim('Refund for ' . $sale->receipt_no . ' ' . ($request->refund_reason ?? '')),
                ]));
            }
        }

        $sale->update($this->onlyExistingColumns('sales', [

            'is_refunded' => true,

            'refund_amount' => $sale->grand_total,

            'refund_reason' => $request->refund_reason,

            'refunded_at' => now(),

            'refunded_by' => auth()->id(),

            'sale_status' => 'REFUNDED',

        ]));

        \App\Services\AuditLogService::record([
            'action' => 'REFUND_CREATED',
            'module' => 'Refunds',
            'event_type' => 'FINANCIAL',
            'model' => Refund::class,
            'model_id' => $refund->id,
            'department_id' => $sale->items->first()?->department_id,
            'branch_id' => $sale->branch_id,
            'reference' => $sale->receipt_no,
            'description' => 'Refunded sale ' . $sale->receipt_no,
            'old_values' => [
                'sale_status' => 'COMPLETED',
                'is_refunded' => false,
            ],
            'new_values' => [
                'sale_status' => 'REFUNDED',
                'is_refunded' => true,
                'refund_amount' => $sale->grand_total,
            ],
            'amount' => $sale->grand_total,
            'quantity_changed' => $sale->items->sum('quantity'),
            'severity' => 'WARNING',
        ]);
        });
    } catch (\Throwable $exception) {
        report($exception);

        return back()->with(
            'error',
            'Refund failed. No database data was deleted. Please try again after the latest update finishes deploying.'
        );
    }

    return back()->with(
        'success',
        'Sale refunded successfully. ' . number_format($restoredUnits) . ' stock units restored.'
    );
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





/*
|--------------------------------------------------------------------------
| SALE RECEIPT
|--------------------------------------------------------------------------
*/

public function receipt($id)
{
    $sale = Sale::with([

        'items.product.department',
        'items.department',
        'user'

    ])->findOrFail($id);

    if (
        auth()->user()->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
        && $sale->user_id !== auth()->id()
    ) {
        abort(403);
    }

    return view(

        'sales.receipt',

        compact('sale')

    );
}





















}
