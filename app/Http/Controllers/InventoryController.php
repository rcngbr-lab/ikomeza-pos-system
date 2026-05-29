<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\CategoryCatalogService;
use App\Services\DepartmentAccessService;
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

        $stockHistory = Stock::with(
            'product',
            'department',
            'user'
        )->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId));

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {

            $stockHistory->where(
                'user_id',
                $user->id
            );
        }

        $stockHistory = $stockHistory
            ->latest()
            ->get();

        return view(
            'inventory.print-history',
            compact('stockHistory')
        );
    }

    public function stockIn(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request) {
            $product = Product::whereKey($request->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            app(DepartmentAccessService::class)->authorize(
                $request->user(),
                $product->department_id
            );

            $before = $product->stock;
            $product->increment('stock', (int) $request->quantity);
            $product->refresh();

            Stock::create([
                'product_id' => $product->id,
                'department_id' => $product->department_id,
                'type' => 'stock_in',
                'quantity' => (int) $request->quantity,
                'before_stock' => $before,
                'after_stock' => $product->stock,
                'note' => $request->note ?: 'Manual stock in',
                'user_id' => auth()->id(),
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'department_id' => $product->department_id,
                'branch_id' => auth()->user()->branch_id,
                'user_id' => auth()->id(),
                'type' => 'STOCK_IN',
                'quantity' => (int) $request->quantity,
                'before_stock' => $before,
                'after_stock' => $product->stock,
                'reason' => $request->note ?: 'Manual stock in',
            ]);
        });

        return back()->with('success', 'Stock received successfully.');
    }

    public function damage(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($request) {
                $product = Product::whereKey($request->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                app(DepartmentAccessService::class)->authorize(
                    $request->user(),
                    $product->department_id
                );

                if ($product->stock < (int) $request->quantity) {
                    throw new \Exception('Not enough stock to mark as damaged.');
                }

                $before = $product->stock;
                $product->decrement('stock', (int) $request->quantity);
                $product->refresh();

                Stock::create([
                    'product_id' => $product->id,
                    'department_id' => $product->department_id,
                    'type' => 'damage',
                    'quantity' => (int) $request->quantity,
                    'before_stock' => $before,
                    'after_stock' => $product->stock,
                    'note' => $request->note ?: 'Damaged product',
                    'user_id' => auth()->id(),
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'department_id' => $product->department_id,
                    'branch_id' => auth()->user()->branch_id,
                    'user_id' => auth()->id(),
                    'type' => 'DAMAGE',
                    'quantity' => (int) $request->quantity,
                    'before_stock' => $before,
                    'after_stock' => $product->stock,
                    'reason' => $request->note ?: 'Damaged product',
                ]);
            });
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Damaged stock recorded.');
    }
}
