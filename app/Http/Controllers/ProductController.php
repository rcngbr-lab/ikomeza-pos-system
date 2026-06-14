<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Stock;
use App\Models\StockRequisition;
use App\Models\Store;
use App\Models\StoreStock;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\CategoryCatalogService;
use App\Services\DepartmentAccessService;
use App\Services\StoreStockService;
use Illuminate\Support\Facades\Storage;
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

            'image_url' =>

                'nullable|url|max:2048',

            'product_image' =>

                'nullable|image|max:3072',

        ]);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            (int) $request->department_id
        );

        $this->ensureCategoryBelongsToDepartment(
            (int) $request->category_id,
            (int) $request->department_id
        );

        $requestedOpeningStock = (float) $request->stock;

        $imagePath = $request->file('product_image')
            ? $request->file('product_image')->store('product-images', 'public')
            : null;

        $product = Product::create([

            'product_code' =>

                $this->generateProductCode(),

            'barcode' =>

                $request->barcode,

            'name' =>

                $request->name,

            'description' =>

                $request->description,

            'image_path' =>

                $imagePath,

            'image_url' =>

                $request->filled('image_url') ? $request->image_url : null,

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

                0,

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
                    'quantity' => 0,
                    'alert_stock' => $product->alert_stock ?: 0,
                    'unit_cost' => $product->buy_price ?: 0,
                    'total_value' => 0,
                ]
            );
        }

        AuditLogService::record([
            'action' => 'PRODUCT_CREATED',
            'module' => 'Products',
            'model' => $product,
            'department_id' => $product->department_id,
            'reference' => $product->product_code,
            'description' => 'Created product ' . $product->name . '.',
            'new_values' => $product->only([
                'product_code',
                'name',
                'department_id',
                'category_id',
                'buy_price',
                'selling_price',
                'stock',
                'alert_stock',
                'status',
            ]),
            'amount' => $product->selling_price,
            'quantity_after' => $product->stock,
        ]);

        if ($requestedOpeningStock > 0) {
            $requisition = StockRequisition::create([
                'product_id' => $product->id,
                'department_id' => $product->department_id,
                'requester_id' => $request->user()->id,
                'type' => StockRequisition::TYPE_STOCK_IN,
                'quantity' => $requestedOpeningStock,
                'status' => StockRequisition::STATUS_PENDING,
                'reason' => 'Opening stock request for new product ' . $product->name,
            ]);

            AuditLogService::record([
                'action' => 'OPENING_STOCK_REQUESTED',
                'module' => 'Inventory',
                'model' => $requisition,
                'department_id' => $product->department_id,
                'reference' => $product->product_code,
                'description' => 'Requested opening stock for ' . $product->name . '. Stock will increase only after approval and receiving.',
                'new_values' => [
                    'product_id' => $product->id,
                    'quantity_requested' => $requestedOpeningStock,
                    'status' => StockRequisition::STATUS_PENDING,
                ],
                'quantity_changed' => $requestedOpeningStock,
                'severity' => 'INFO',
            ]);
        }

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                $requestedOpeningStock > 0
                    ? 'Product created. Opening stock was sent for approval before it affects live inventory.'
                    : 'Product created successfully.'

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

            'image_url' =>

                'nullable|url|max:2048',

            'product_image' =>

                'nullable|image|max:3072',

            'remove_image' =>

                'nullable|boolean',

        ]);

        app(DepartmentAccessService::class)->authorize(
            $request->user(),
            (int) $request->department_id
        );

        $this->ensureCategoryBelongsToDepartment(
            (int) $request->category_id,
            (int) $request->department_id
        );

        if (abs((float) $request->stock - (float) $product->stock) > 0.00001) {
            return back()
                ->withErrors([
                    'stock' => 'Stock quantity cannot be edited here. Use Store Control, Purchase Receiving, Inventory Requisition, or approved Stock Adjustment.',
                ])
                ->withInput();
        }

        $oldValues = $product->only([
            'barcode',
            'name',
            'description',
            'category_id',
            'department_id',
            'default_store_id',
            'supplier_id',
            'product_type',
            'image_path',
            'image_url',
            'buy_price',
            'selling_price',
            'stock',
            'alert_stock',
            'unit',
            'active',
            'status',
        ]);

        $imagePath = $product->image_path;

        if ($request->boolean('remove_image') && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($request->file('product_image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $request->file('product_image')->store('product-images', 'public');
        }

        $imageUrl = $request->boolean('remove_image')
            ? null
            : ($request->filled('image_url') ? $request->image_url : null);

        $product->update([

            'barcode' =>

                $request->barcode,

            'name' =>

                $request->name,

            'description' =>

                $request->description,

            'image_path' =>

                $imagePath,

            'image_url' =>

                $imageUrl,

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

                $product->stock,

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

        $product->refresh();

        AuditLogService::record([
            'action' => (
                (float) $oldValues['selling_price'] !== (float) $product->selling_price
                || (float) $oldValues['buy_price'] !== (float) $product->buy_price
            ) ? 'PRICE_CHANGED' : 'PRODUCT_UPDATED',
            'module' => 'Products',
            'model' => $product,
            'department_id' => $product->department_id,
            'reference' => $product->product_code,
            'description' => 'Updated product ' . $product->name . '.',
            'old_values' => $oldValues,
            'new_values' => $product->only(array_keys($oldValues)),
            'amount' => $product->selling_price,
            'severity' => (
                (float) $oldValues['selling_price'] !== (float) $product->selling_price
                || (float) $oldValues['buy_price'] !== (float) $product->buy_price
            ) ? 'WARNING' : 'INFO',
        ]);

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

        if ($request->type === 'REMOVE' && (float) $product->stock < (float) $request->quantity) {
            return back()->with('error', 'Insufficient stock for this stock-out request.');
        }

        $requisition = StockRequisition::create([
            'product_id' => $product->id,
            'department_id' => $product->department_id,
            'requester_id' => $request->user()->id,
            'type' => $request->type === 'ADD'
                ? StockRequisition::TYPE_STOCK_IN
                : StockRequisition::TYPE_STOCK_OUT,
            'quantity' => (float) $request->quantity,
            'status' => StockRequisition::STATUS_PENDING,
            'reason' => $request->reason ?: (
                $request->type === 'ADD'
                    ? 'Manual stock-in request'
                    : 'Manual stock-out request'
            ),
        ]);

        AuditLogService::record([
            'action' => $request->type === 'ADD' ? 'STOCK_IN_REQUESTED' : 'STOCK_OUT_REQUESTED',
            'module' => 'Inventory',
            'model' => $requisition,
            'department_id' => $product->department_id,
            'reference' => $product->product_code,
            'description' => 'Requested stock ' . strtolower($request->type === 'ADD' ? 'in' : 'out') . ' for ' . $product->name . '. Live stock is unchanged until approval.',
            'new_values' => [
                'product_id' => $product->id,
                'quantity_requested' => (float) $request->quantity,
                'status' => StockRequisition::STATUS_PENDING,
                'reason' => $request->reason,
            ],
            'quantity_before' => $product->stock,
            'quantity_changed' => $request->type === 'ADD'
                ? abs((float) $request->quantity)
                : -abs((float) $request->quantity),
            'quantity_after' => $product->stock,
            'severity' => $request->type === 'ADD' ? 'INFO' : 'WARNING',
        ]);

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Stock change request submitted for approval. Live stock was not changed.'

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

        $oldValues = $product->only(['active', 'status']);

        $product->update([
            'active' => false,
            'status' => 'INACTIVE',
        ]);

        AuditLogService::record([
            'action' => 'PRODUCT_DEACTIVATED',
            'module' => 'Products',
            'model' => $product,
            'department_id' => $product->department_id,
            'reference' => $product->product_code,
            'description' => 'Deactivated product ' . $product->name . ' instead of deleting it, preserving historical sales and stock records.',
            'old_values' => $oldValues,
            'new_values' => $product->only(['active', 'status']),
            'severity' => 'WARNING',
        ]);

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Product deactivated successfully. Historical receipts and stock records were preserved.'

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
