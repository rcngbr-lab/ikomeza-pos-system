<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Services\CategoryCatalogService;
use App\Services\BranchAccessService;
use App\Services\SaleService;
use App\Services\TaxService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Request $request)
    {
        app(CategoryCatalogService::class)->ensureDefaults();
        $branchAccess = app(BranchAccessService::class);
        $selectedBranchId = $branchAccess->selectedBranchId(
            $request->user(),
            $request->integer('branch_id') ?: null
        );

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
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->orderBy('name')
            ->get();

        $products = $this->uniqueProductsForPos($products);

        $categories = Category::with('department')
            ->when($selectedBranchId, fn ($query) => $query->where(function ($categories) use ($selectedBranchId) {
                $categories->where('branch_id', $selectedBranchId)->orWhereNull('branch_id');
            }))
            ->orderBy('name')
            ->get();
        $departments = Department::where('active', true)
            ->orderBy('sort_order')
            ->get();
        $cart = session()->get('cart', []);
        $total = $this->cartTotal($cart);
        $taxPreview = app(TaxService::class)->saleTotals($total, (float) old('discount', 0));
        $paymentMethods = Sale::PAYMENT_METHOD_LABELS;
        $customers = Customer::query()
            ->where('status', Customer::STATUS_ACTIVE)
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->orderBy('name')
            ->take(80)
            ->get();
        $tables = RestaurantTable::query()
            ->whereIn('status', ['AVAILABLE', 'OCCUPIED'])
            ->when($selectedBranchId, fn ($query) => $query->where('branch_id', $selectedBranchId))
            ->orderBy('section')
            ->orderBy('name')
            ->get();

        return view(
            'pos.index',
            compact('products', 'categories', 'departments', 'cart', 'total', 'taxPreview', 'shift', 'paymentMethods', 'customers', 'tables')
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

        if (
            !$request->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
            && $product->branch_id
            && (int) $product->branch_id !== (int) $request->user()->branch_id
        ) {
            abort(403);
        }

        if ($product->track_stock && $product->stock <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $product->name . ' is out of stock.',
                ], 422);
            }

            return back()->with('error', $product->name . ' is out of stock.');
        }

        $cart = session()->get('cart', []);
        $currentQuantity = (int) ($cart[$product->id]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($product->track_stock && $newQuantity > $product->stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Stock limit reached for ' . $product->name . '.',
                ], 422);
            }

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
            'unit' => $product->unit ?? 'item',
            'stock' => (float) $product->stock,
        ];

        session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload($cart, $product->name . ' added to cart.'));
        }

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

            if ($request->expectsJson()) {
                return response()->json($this->cartPayload($cart, 'Item removed from cart.'));
            }

            return back()->with('success', 'Item removed from cart.');
        }

        if ($product->track_stock && $quantity > $product->stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Stock limit reached for ' . $product->name . '.',
                ], 422);
            }

            return back()->with('error', 'Stock limit reached for ' . $product->name . '.');
        }

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = $quantity;
        }

        session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload($cart, 'Cart updated.'));
        }

        return back()->with('success', 'Cart updated.');
    }

    public function removeCartItem(Request $request, $productId = null)
    {
        $cart = session()->get('cart', []);
        $productId = $productId ?? $request->input('product_id');

        if (!$productId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Select an item to remove.',
                ], 422);
            }

            return back()->with('error', 'Select an item to remove.');
        }

        unset($cart[$productId]);
        session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload($cart, 'Item removed from cart.'));
        }

        return back()->with('success', 'Item removed from cart.');
    }

    public function clearCart(Request $request)
    {
        session()->forget('cart');

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload([], 'Cart cleared.'));
        }

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
            'payment_method' => ['nullable', 'in:' . implode(',', Sale::PAYMENT_METHODS)],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'provider_name' => ['nullable', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:160'],
            'transaction_id' => ['nullable', 'string', 'max:160'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'in:' . implode(',', Sale::PAYMENT_METHODS)],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
            'payments.*.provider_name' => ['nullable', 'string', 'max:80'],
            'payments.*.payment_reference' => ['nullable', 'string', 'max:160'],
            'payments.*.transaction_id' => ['nullable', 'string', 'max:160'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'table_id' => ['nullable', 'exists:restaurant_tables,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ((array) $request->input('payments', []) as $payment) {
            $method = Sale::normalizePaymentMethod($payment['method'] ?? $paymentMethod);

            if (!in_array($method, ['CASH', 'CREDIT'], true) && blank($payment['payment_reference'] ?? $payment['reference'] ?? $payment['transaction_id'] ?? null)) {
                return back()
                    ->withInput()
                    ->with('error', 'Non-cash payments require a payment reference or transaction ID for reconciliation.');
            }
        }

        if (
            empty($request->input('payments', []))
            && !in_array($paymentMethod, ['CASH', 'CREDIT'], true)
            && blank($request->input('payment_reference') ?? $request->input('transaction_id'))
        ) {
            return back()
                ->withInput()
                ->with('error', 'Non-cash payments require a payment reference or transaction ID for reconciliation.');
        }

        try {
            $total = $this->cartTotal($cart);
            $amountPaid = (float) $request->input('amount_paid', $paymentMethod === 'CASH' ? 0 : $total);
            $payments = $request->input('payments', []);

            if ($payments === [] && ($request->filled('payment_reference') || $request->filled('transaction_id') || $request->filled('provider_name'))) {
                $payments = [[
                    'method' => $paymentMethod,
                    'amount' => $paymentMethod === 'CASH' ? $amountPaid : $total,
                    'reference' => $request->input('payment_reference'),
                    'payment_reference' => $request->input('payment_reference'),
                    'transaction_id' => $request->input('transaction_id'),
                    'provider_name' => $request->input('provider_name'),
                ]];
            }

            $sale = $saleService->checkout(
                $cart,
                $request->user(),
                $paymentMethod,
                $amountPaid,
                $request->input('customer_name'),
                $request->input('notes'),
                $request->integer('customer_id') ?: null,
                $request->integer('table_id') ?: null,
                $payments,
                (float) $request->input('discount', 0),
                $request->input('discount_reason')
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
        $sale = Sale::with('items.product.department', 'items.department', 'user', 'shift', 'payments', 'customer', 'table')->findOrFail($id);

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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Item is no longer in the cart.',
                ], 422);
            }

            return back()->with('error', 'Item is no longer in the cart.');
        }

        $product = Product::findOrFail($productId);
        $nextQuantity = (int) $cart[$productId]['quantity'] + $direction;

        if ($nextQuantity <= 0) {
            unset($cart[$productId]);
            session()->put('cart', $cart);

            if ($request->expectsJson()) {
                return response()->json($this->cartPayload($cart, 'Item removed from cart.'));
            }

            return back()->with('success', 'Item removed from cart.');
        }

        if ($product->track_stock && $nextQuantity > $product->stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Stock limit reached for ' . $product->name . '.',
                ], 422);
            }

            return back()->with('error', 'Stock limit reached for ' . $product->name . '.');
        }

        $cart[$productId]['quantity'] = $nextQuantity;
        session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return response()->json($this->cartPayload($cart, 'Cart updated.'));
        }

        return back();
    }

    private function cartTotal(array $cart): float
    {
        return collect($cart)->sum(
            fn ($item) => (float) $item['price'] * (float) $item['quantity']
        );
    }

    private function cartPayload(array $cart, string $message): array
    {
        $items = collect($cart)
            ->values()
            ->map(function ($item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);

                return array_merge($item, [
                    'quantity' => $quantity,
                    'price' => $price,
                    'line_total' => $price * $quantity,
                    'unit' => $item['unit'] ?? 'item',
                ]);
            })
            ->all();

        return [
            'message' => $message,
            'cart_items' => $items,
            'cart_count' => collect($items)->sum('quantity'),
            'total' => $this->cartTotal($cart),
        ];
    }

    private function uniqueProductsForPos(Collection $products): Collection
    {
        return $products
            ->groupBy(fn (Product $product) => $this->posProductCardKey($product))
            ->map(function (Collection $matches) {
                return $matches
                    ->sort(function (Product $a, Product $b) {
                        return $this->posProductCardScore($b) <=> $this->posProductCardScore($a);
                    })
                    ->first();
            })
            ->values();
    }

    private function posProductCardKey(Product $product): string
    {
        $name = $this->normalizeProductCardValue($product->name);
        $unit = $this->normalizeProductCardValue($product->unit ?: 'item');
        $price = number_format((float) $product->selling_price, 2, '.', '');

        return implode('|', [
            (int) ($product->branch_id ?? 0),
            (int) ($product->department_id ?? 0),
            (int) ($product->category_id ?? 0),
            $name,
            $unit,
            $price,
        ]);
    }

    private function posProductCardScore(Product $product): float
    {
        $stock = max((float) $product->stock, 0);
        $hasImage = $product->image_source ? 1 : 0;
        $inStock = (!$product->track_stock || $stock > 0) ? 1 : 0;

        return ($inStock * 1_000_000) + ($hasImage * 100_000) + $stock + ((float) $product->id / 1_000_000);
    }

    private function normalizeProductCardValue(?string $value): string
    {
        return strtolower((string) preg_replace('/\s+/', ' ', trim((string) $value)));
    }
}
