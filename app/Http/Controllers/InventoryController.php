<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockRequisition;
use App\Services\AuditLogService;
use App\Services\CategoryCatalogService;
use App\Services\DepartmentAccessService;
use App\Services\StoreStockService;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $user = auth()->user();
        $departmentAccess = app(DepartmentAccessService::class);
        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $user,
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($user);

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        $filter = $request->filter;

        $stockHistory = Stock::with('product.department', 'department', 'user')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId));

        /*
        |--------------------------------------------------------------------------
        | ROLE FILTERING
        |--------------------------------------------------------------------------
        */

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {

            $stockHistory->where(
                'user_id',
                $user->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DATE FILTERS
        |--------------------------------------------------------------------------
        */

        if ($filter === 'today') {

            $stockHistory->whereDate(
                'created_at',
                today()
            );
        }

        elseif ($filter === 'week') {

            $stockHistory->whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]
            );
        }

        elseif ($filter === 'month') {

            $stockHistory->whereMonth(
                'created_at',
                now()->month
            );
        }

        elseif ($filter === 'year') {

            $stockHistory->whereYear(
                'created_at',
                now()->year
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $products = Product::with('category', 'department')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->latest()
            ->paginate(10);
        $totalProducts = Product::when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))->count();

        $allProducts = Product::with('department')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | LOW STOCK
        |--------------------------------------------------------------------------
        */

        $lowStockProducts = Product::with('department')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->whereColumn(
                'stock',
                '<=',
                'alert_stock'
            )
            ->where('stock', '>', 0)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | OUT OF STOCK
        |--------------------------------------------------------------------------
        */

        $outOfStockProducts = Product::with('department')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->where(
            'stock',
            '<=',
            0
        )->get();

        /*
        |--------------------------------------------------------------------------
        | INVENTORY VALUE
        |--------------------------------------------------------------------------
        */

        $inventoryValue = Product::when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->selectRaw(
            'SUM(buy_price * stock) as total'
        )->value('total');

        /*
        |--------------------------------------------------------------------------
        | STOCK HISTORY
        |--------------------------------------------------------------------------
        */

        $stockHistory = $stockHistory
            ->latest()
            ->paginate(15);

        return view(
            'inventory.index',
            compact(
                'products',
                'lowStockProducts',
                'outOfStockProducts',
                'inventoryValue',
                'stockHistory',
                'totalProducts',
                'allProducts',
                'departments',
                'selectedDepartmentId',
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT STOCK HISTORY
    |--------------------------------------------------------------------------
    */

    public function printHistory(Request $request)
    {
        $user = auth()->user();
        $selectedDepartmentId = app(DepartmentAccessService::class)->selectedDepartmentId(
            $user,
            $request->integer('department_id') ?: null
        );
        $reportType = $request->input('type');
        $allowedTypes = ['stock_in', 'damage'];

        $stockHistory = Stock::with(
            'product.department',
            'department',
            'user'
        )
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->when(in_array($reportType, $allowedTypes, true), fn ($query) => $query->where('type', $reportType));

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {

            $stockHistory->where(
                'user_id',
                $user->id
            );
        }

        $this->applyStockDateFilter($stockHistory, $request);

        $stockHistory = $stockHistory
            ->latest()
            ->get();

        $reportTitle = match ($reportType) {
            'stock_in' => 'Stock In Report',
            'damage' => 'Damaged Stock Report',
            default => 'Inventory Stock History Report',
        };

        $reportDepartment = $selectedDepartmentId
            ? (Department::find($selectedDepartmentId)?->name ?? 'Selected Department')
            : 'All Departments';

        $reportPeriod = $this->stockPeriodLabel($request);
        $totalQuantity = $stockHistory->sum('quantity');
        $totalRecords = $stockHistory->count();

        return view(
            'inventory.print-history',
            compact(
                'stockHistory',
                'reportTitle',
                'reportDepartment',
                'reportPeriod',
                'totalQuantity',
                'totalRecords',
                'reportType'
            )
        );
    }

    public function stockIn(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $product->department_id
        );

        $requisition = StockRequisition::create([
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'requester_id' => $request->user()->id,
            'type' => StockRequisition::TYPE_STOCK_IN,
            'quantity' => (int) $validated['quantity'],
            'status' => StockRequisition::STATUS_PENDING,
            'reason' => $validated['note'] ?: 'Inventory stock-in request',
        ]);

        AuditLogService::record([
            'action' => 'STOCK_IN_REQUESTED',
            'module' => 'Inventory',
            'model' => StockRequisition::class,
            'model_id' => $requisition->id,
            'department_id' => $requisition->department_id,
            'reference' => 'RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT),
            'description' => 'Requested stock in from inventory screen. Stock was not increased.',
            'quantity_changed' => $requisition->quantity,
        ]);

        return back()->with('success', 'Stock-in request submitted for approval. Stock was not increased yet.');
    }

    public function damage(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $product->department_id
        );

        $requisition = StockRequisition::create([
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'requester_id' => $request->user()->id,
            'type' => StockRequisition::TYPE_DAMAGED,
            'quantity' => (int) $validated['quantity'],
            'status' => StockRequisition::STATUS_PENDING,
            'reason' => $validated['note'] ?: 'Damaged stock request',
        ]);

        AuditLogService::record([
            'action' => 'DAMAGE_REQUESTED',
            'module' => 'Inventory',
            'model' => StockRequisition::class,
            'model_id' => $requisition->id,
            'department_id' => $requisition->department_id,
            'reference' => 'RQ-' . str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT),
            'description' => 'Requested damaged-stock approval from inventory screen. Stock was not deducted.',
            'quantity_changed' => -abs((float) $requisition->quantity),
            'severity' => 'WARNING',
        ]);

        return back()->with('success', 'Damaged-stock request submitted for approval. Stock was not deducted yet.');
    }

    private function applyStockDateFilter($query, Request $request): void
    {
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);

            return;
        }

        match ($request->filter) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'year' => $query->whereYear('created_at', now()->year),
            default => null,
        };
    }

    private function stockPeriodLabel(Request $request): string
    {
        if ($request->start_date && $request->end_date) {
            return $request->start_date . ' to ' . $request->end_date;
        }

        return match ($request->filter) {
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'year' => 'This Year',
            default => 'All Time',
        };
    }
}
