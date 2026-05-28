<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use App\Models\Stock;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PRODUCTS LIST
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $products = Product::with('category')

            ->latest()

            ->paginate(20);

        return view(

            'products.index',

            compact('products')

        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PRODUCT FORM
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $categories = Category::orderBy('name')

            ->get();

        return view(

            'products.create',

            compact('categories')

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

        ]);

        Product::create([

            'product_code' =>

                'PRD-' . time(),

            'barcode' =>

                $request->barcode,

            'name' =>

                $request->name,

            'description' =>

                $request->description,

            'category_id' =>

                $request->category_id,

            'product_type' =>

                $request->product_type,

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

        ]);

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
        $categories = Category::orderBy('name')

            ->get();

        return view(

            'products.edit',

            compact(

                'product',
                'categories'

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
        $request->validate([

            'name' =>

                'required|string|max:255',

            'barcode' =>

                'nullable|string|max:255|unique:products,barcode,' . $product->id,

            'category_id' =>

                'required|exists:categories,id',

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

        ]);

        $product->update([

            'barcode' =>

                $request->barcode,

            'name' =>

                $request->name,

            'description' =>

                $request->description,

            'category_id' =>

                $request->category_id,

            'product_type' =>

                $request->product_type,

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

        /*
        |--------------------------------------------------------------------------
        | STOCK MOVEMENT LOG
        |--------------------------------------------------------------------------
        */

        StockMovement::create([

            'product_id' =>

                $product->id,

            'user_id' =>

                auth()->id(),

            'type' =>

                $request->type,

            'quantity' =>

                $request->quantity,

            'before_stock' =>

                $before,

            'after_stock' =>

                $after,

            'reason' =>

                $request->reason,

        ]);

        Stock::create([

            'product_id' =>

                $product->id,

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
        $product->delete();

        return redirect()

            ->route('products.index')

            ->with(

                'success',

                'Product deleted successfully.'

            );
    }
}
