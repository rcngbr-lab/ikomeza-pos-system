<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\CategoryCatalogService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Request $request)
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $shift = Shift::where('user_id', auth()->id())
            ->where(function ($query) {
                $query->where('is_open', true)
                    ->orWhere('status', 'OPEN');
            })
            ->latest()
            ->first();

        if (!$shift) {
            return redirect()
                ->route('shifts.open.form')
                ->with('error', 'Open a shift before using the cashier terminal.');
        }

        $products = Product::with('category', 'department')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::with('department')->orderBy('name')->get();
        $departments = Department::where('active', true)
            ->orderBy('sort_order')
            ->get();
        $cart = session()->get('cart', []);
        $total = $this->cartTotal($cart);
        $paymentMethods = Sale::PAYMENT_METHOD_LABELS;

        return view(
            'pos.index',
            compact('products', 'categories', 'departments', 'cart', 'total', 'shift', 'paymentMethods')
        );
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::with('category', 'department')->findOrFail($request->product_id);
        $quantity = (int) $request->input('quantity', 1);

        if ($product->track_stock && $product->stock <= 0) {
            return back()->with('error', $product->name . ' is out of stock.');
        }

        $cart = session()->get('cart', []);
        $currentQuantity = (int) ($cart[$product->id]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($product->track_stock && $newQuantity > $product->stock) {
            return back()->with('error', 'Stock limit reached for ' . $product->name . '.');
        }

        $cart[$product->id] = [
            'id' => $product->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'category' => $product->category->name ?? 'Uncategorized',
            'department_id' => $product->department_id,
            'department' => $product->department->name ?? 'Unassigned',
            'department_code' => $product->department->code ?? null,
            'price' => (float) $product->selling_price,
            'quantity' => $newQuantity,
        ];

        session()->put('cart', $cart);

        return back()->with('success', $product->name . ' added to cart.');
    }

    public function increaseCart(Request $request)
    {
        return $this->changeCartQuantity($request, 1);
    }

    public function decreaseCart(Request $request)
    {
        return $this->changeCartQuantity($request, -1);
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart = session()->get('cart', []);
        $product = Product::findOrFail($request->product_id);
        $quantity = (int) $request->quantity;

        if ($quantity <= 0) {
            unset($cart[$product->id]);
            session()->put('cart', $cart);

            return back()->with('success', 'Item removed from cart.');
        }

        if ($product->track_stock && $quantity > $product->stock) {
            return back()->with('error', 'Stock limit reached for ' . $product->name . '.');
        }

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = $quantity;
        }

        session()->put('cart', $cart);

        return back()->with('success', 'Cart updated.');
    }

    public function removeCartItem(Request $request, $productId = null)
    {
        $cart = session()->get('cart', []);
        $productId = $productId ?? $request->input('product_id');

        if (!$productId) {
            return back()->with('error', 'Select an item to remove.');
        }

        unset($cart[$productId]);
        session()->put('cart', $cart);

        return back()->with('success', 'Item removed from cart.');
    }

    public function clearCart()
    {
        session()->forget('cart');

        return back()->with('success', 'Cart cleared.');
    }

    public function checkout(Request $request, SaleService $saleService)
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty.');
        }

        $paymentMethod = Sale::normalizePaymentMethod($request->input('payment_method'));
        $request->merge(['payment_method' => $paymentMethod]);

        $request->validate([
            'payment_method' => ['required', 'in:' . implode(',', Sale::PAYMENT_METHODS)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $total = $this->cartTotal($cart);
            $amountPaid = (float) $request->input('amount_paid', $paymentMethod === 'CASH' ? 0 : $total);

            $sale = $saleService->checkout(
                $cart,
                $request->user(),
                $paymentMethod,
                $amountPaid,
                $request->input('customer_name'),
                $request->input('notes')
            );

            session()->forget('cart');

            return redirect()
                ->route('pos.receipt', $sale)
                ->with('success', 'Sale completed successfully.');
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function receipt($id)
    {
        $sale = Sale::with('items.product.department', 'items.department', 'user', 'shift')->findOrFail($id);

        if (
            auth()->user()->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
            && $sale->user_id !== auth()->id()
        ) {
            abort(403);
        }

        return view('pos.receipt', compact('sale'));
    }

    private function changeCartQuantity(Request $request, int $direction)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $cart = session()->get('cart', []);
        $productId = $request->product_id;

        if (!isset($cart[$productId])) {
            return back()->with('error', 'Item is no longer in the cart.');
        }

        $product = Product::findOrFail($productId);
        $nextQuantity = (int) $cart[$productId]['quantity'] + $direction;

        if ($nextQuantity <= 0) {
            unset($cart[$productId]);
            session()->put('cart', $cart);

            return back()->with('success', 'Item removed from cart.');
        }

        if ($product->track_stock && $nextQuantity > $product->stock) {
            return back()->with('error', 'Stock limit reached for ' . $product->name . '.');
        }

        $cart[$productId]['quantity'] = $nextQuantity;
        session()->put('cart', $cart);

        return back();
    }

    private function cartTotal(array $cart): float
    {
        return collect($cart)->sum(
            fn ($item) => (float) $item['price'] * (float) $item['quantity']
        );
    }
}
