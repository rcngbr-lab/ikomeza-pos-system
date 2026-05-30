<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\Supplier;
use App\Services\CategoryCatalogService;
use App\Services\DepartmentAccessService;
use App\Services\StoreStockService;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PRODUCTS LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request, DepartmentAccessService $departmentAccess)
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $request->user(),
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($request->user());

        $products = Product::with('category', 'department')

            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))

            ->latest()

            ->paginate(20)

            ->withQueryString();

        return view(

            'products.index',

            compact('products', 'departments', 'selectedDepartmentId')

        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PRODUCT FORM
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $departments = app(DepartmentAccessService::class)->visibleDepartments(auth()->user());

        $categories = Category::with('department')
            ->whereIn('department_id', $departments->pluck('id')->all())
            ->orderBy('name')

            ->get();

        $stores = Store::where('active', true)
            ->orderBy('sort_order')
            ->get();

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)
            ->orderBy('company_name')
            ->get();

        return view(

            'products.create',

            compact('categories', 'departments', 'stores', 'suppliers')

        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'name' =>

                'required|string|max:255',

            'barcode' =>

                'nullable|string|max:255|unique:products',

            'category_id' =>

                'required|exists:categories,id',

            'department_id' =>

                'required|exists:departments,id',

            'default_store_id' =>

                'nullable|exists:stores,id',

            'supplier_id' =>

                'nullable|exists:suppliers,id',

            'buy_price' =>

                'required|numeric|min:0',

            'selling_price' =>

                'required|numeric|min:0',

            'stock' =>

                'required|numeric|min:0',

            'alert_stock' =>

                'nullable|numeric|min:0',

            'unit' =>

                'nullable|string|max:50',

            'product_type' =>

                'nullable|in:FINISHED_PRODUCT,RAW_MATERIAL,SERVICE',

        ]);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            (int) $request->department_id
        );

        $this->ensureCategoryBelongsToDepartment(
            (int) $request->category_id,
            (int) $request->department_id
        );

        $product = Product::create([

            'product_code' =>

                $this->generateProductCode(),

            'barcode' =>

                $request->barcode,

            'name' =>

                $request->name,

            'description' =>

                $request->description,

            'category_id' =>

                $request->category_id,

            'department_id' =>

                $request->department_id,

            'default_store_id' =>

                $request->default_store_id,

            'supplier_id' =>

                $request->supplier_id,

            'product_type' =>

                $request->input('product_type', 'FINISHED_PRODUCT'),

            'buy_price' =>

                $request->buy_price,

            'selling_price' =>

                $request->selling_price,

            'track_stock' =>

                true,

            'stock' =>

                $request->stock,

            'alert_stock' =>

                $request->alert_stock ?? 0,

            'unit' =>

                $request->unit,

            'active' =>

                true,

            'status' =>

                'ACTIVE',

        ]);

        if (!$product->default_store_id) {
            $store = app(StoreStockService::class)->defaultStoreFor($product->load('department'));

            if ($store) {
                $product->update(['default_store_id' => $store->id]);
            }
        }

        if ($product->track_stock && $product->default_store_id) {
            StoreStock::updateOrCreate(
                [
                    'store_id' => $product->default_store_id,
                    'product_id' => $product->id,
                ],
                [
                    'department_id' => $product->department_id,
                    'quantity' => $product->stock,
                    'alert_stock' => $product->alert_stock ?: 0,
                    'unit_cost' => $product->buy_price ?: 0,
                    'total_value' => (float) $product->stock * (float) $product->buy_price,
                ]
            );
        }

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Product created successfully.'

            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PRODUCT
    |--------------------------------------------------------------------------
    */

    public function show(Product $product)
    {
        return view(

            'products.show',

            compact('product')

        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PRODUCT FORM
    |--------------------------------------------------------------------------
    */

    public function edit(Product $product)
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        app(DepartmentAccessService::class)->authorize(
            auth()->user(),
            $product->department_id
        );

        $departments = app(DepartmentAccessService::class)->visibleDepartments(auth()->user());

        $categories = Category::with('department')
            ->whereIn('department_id', $departments->pluck('id')->all())
            ->orderBy('name')

            ->get();

        $stores = Store::where('active', true)
            ->orderBy('sort_order')
            ->get();

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)
            ->orderBy('company_name')
            ->get();

        return view(

            'products.edit',

            compact(

                'product',
                'categories',
                'departments',
                'stores',
                'suppliers'

            )

        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Product $product
    )
    {
        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $product->department_id
        );

        $request->validate([

            'name' =>

                'required|string|max:255',

            'barcode' =>

                'nullable|string|max:255|unique:products,barcode,' . $product->id,

            'category_id' =>

                'required|exists:categories,id',

            'department_id' =>

                'required|exists:departments,id',

            'default_store_id' =>

                'nullable|exists:stores,id',

            'supplier_id' =>

                'nullable|exists:suppliers,id',

            'buy_price' =>

                'required|numeric|min:0',

            'selling_price' =>

                'required|numeric|min:0',

            'stock' =>

                'required|numeric|min:0',

            'alert_stock' =>

                'nullable|numeric|min:0',

            'unit' =>

                'nullable|string|max:50',

            'product_type' =>

                'nullable|in:FINISHED_PRODUCT,RAW_MATERIAL,SERVICE',

        ]);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            (int) $request->department_id
        );

        $this->ensureCategoryBelongsToDepartment(
            (int) $request->category_id,
            (int) $request->department_id
        );

        $product->update([

            'barcode' =>

                $request->barcode,

            'name' =>

                $request->name,

            'description' =>

                $request->description,

            'category_id' =>

                $request->category_id,

            'department_id' =>

                $request->department_id,

            'default_store_id' =>

                $request->default_store_id ?: $product->default_store_id,

            'supplier_id' =>

                $request->supplier_id,

            'product_type' =>

                $request->input('product_type', $product->product_type ?: 'FINISHED_PRODUCT'),

            'buy_price' =>

                $request->buy_price,

            'selling_price' =>

                $request->selling_price,

            'stock' =>

                $request->stock,

            'alert_stock' =>

                $request->alert_stock ?? 0,

            'unit' =>

                $request->unit,

            'active' =>

                $request->has('active'),

            'status' =>

                $request->has('active') ? 'ACTIVE' : 'INACTIVE',

        ]);

        if ($product->track_stock && $product->default_store_id) {
            StoreStock::updateOrCreate(
                [
                    'store_id' => $product->default_store_id,
                    'product_id' => $product->id,
                ],
                [
                    'department_id' => $product->department_id,
                    'quantity' => $product->stock,
                    'alert_stock' => $product->alert_stock ?: 0,
                    'unit_cost' => $product->buy_price ?: 0,
                    'total_value' => (float) $product->stock * (float) $product->buy_price,
                ]
            );
        }

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Product updated successfully.'

            );
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK ADJUST FORM
    |--------------------------------------------------------------------------
    */

    public function adjust(Product $product)
    {
        app(DepartmentAccessService::class)->authorize(
            auth()->user(),
            $product->department_id
        );

        return view(

            'products.adjust',

            compact('product')

        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADJUST STOCK
    |--------------------------------------------------------------------------
    */

    public function adjustStock(
        Request $request,
        Product $product
    )
    {
        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            $product->department_id
        );

        $request->validate([

            'quantity' =>

                'required|numeric|min:1',

            'type' =>

                'required|in:ADD,REMOVE',

            'reason' =>

                'nullable|string'

        ]);

        $before =
            $product->stock;

        /*
        |--------------------------------------------------------------------------
        | CALCULATE STOCK
        |--------------------------------------------------------------------------
        */

        if ($request->type === 'ADD') {

            $after =

                $before
                +
                $request->quantity;

        } else {

            $after =

                $before
                -
                $request->quantity;

            if ($after < 0) {

                return back()->with(

                    'error',

                    'Insufficient stock.'

                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE PRODUCT STOCK
        |--------------------------------------------------------------------------
        */

        $product->update([

            'stock' => $after

        ]);

        $storeStockService = app(StoreStockService::class);
        $store = $storeStockService->defaultStoreFor($product->load('department'));
        $storeSnapshot = null;

        if ($store) {
            $storeSnapshot = $request->type === 'ADD'
                ? $storeStockService->increaseStoreOnly($product, $store, (float) $request->quantity, (float) ($product->buy_price ?? 0))
                : $storeStockService->decreaseStoreOnly($product, $store, (float) $request->quantity, (float) ($product->buy_price ?? 0));
        }

        /*
        |--------------------------------------------------------------------------
        | STOCK MOVEMENT LOG
        |--------------------------------------------------------------------------
        */

        StockMovement::create([

            'product_id' =>

                $product->id,

            'department_id' =>

                $product->department_id,

            'user_id' =>

                auth()->id(),

            'from_store_id' =>

                $request->type === 'ADD' ? null : $store?->id,

            'to_store_id' =>

                $request->type === 'ADD' ? $store?->id : null,

            'type' =>

                $request->type,

            'movement_type' =>

                $request->type === 'ADD' ? 'STOCK_ADJUSTMENT_IN' : 'STOCK_ADJUSTMENT_OUT',

            'quantity' =>

                $request->quantity,

            'before_stock' =>

                $before,

            'after_stock' =>

                $after,

            'quantity_before' =>

                $storeSnapshot['before'] ?? $before,

            'quantity_changed' =>

                $request->type === 'ADD' ? abs((float) $request->quantity) : -abs((float) $request->quantity),

            'quantity_after' =>

                $storeSnapshot['after'] ?? $after,

            'unit_cost' =>

                $product->buy_price ?? 0,

            'total_cost' =>

                ($product->buy_price ?? 0) * (float) $request->quantity,

            'performed_by' =>

                auth()->id(),

            'reason' =>

                $request->reason,

            'notes' =>

                $request->reason,

        ]);

        Stock::create([

            'product_id' =>

                $product->id,

            'department_id' =>

                $product->department_id,

            'type' =>

                $request->type === 'ADD' ? 'adjustment_in' : 'adjustment_out',

            'quantity' =>

                $request->quantity,

            'before_stock' =>

                $before,

            'after_stock' =>

                $after,

            'note' =>

                $request->reason,

            'user_id' =>

                auth()->id(),

        ]);

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Stock adjusted successfully.'

            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function destroy(Product $product)
    {
        app(DepartmentAccessService::class)->authorize(
            auth()->user(),
            $product->department_id
        );

        $product->delete();

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Product deleted successfully.'

            );
    }

    private function generateProductCode(): string
    {
        do {
            $code = 'PRD-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
        } while (Product::where('product_code', $code)->exists());

        return $code;
    }

    private function ensureCategoryBelongsToDepartment(int $categoryId, int $departmentId): void
    {
        $category = Category::findOrFail($categoryId);

        if ($category->department_id && (int) $category->department_id !== $departmentId) {
            throw ValidationException::withMessages([
                'category_id' => 'Selected category belongs to a different department.',
            ]);
        }
    }
}
