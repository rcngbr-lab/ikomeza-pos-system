@extends('layouts.app')

@section('content')
@php
    $cartCount = collect($cart)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
    $oldPayments = old('payments', [[
        'method' => old('payment_method', 'CASH'),
        'amount' => old('amount_paid', $taxPreview['grand_total'] ?? $total),
        'reference' => null,
    ]]);
@endphp

<script>
    window.posTerminal = function (config) {
        return {
            cartOpen: false,
            activeCategory: 'All',
            activeDepartment: 'All',
            productQuery: '',
            barcodeQuery: '',
            visibleLimit: Number(config.visibleLimit || 80),
            discount: Number(config.discount || 0),
            payments: (config.initialPayments || []).map((payment) => ({
                method: payment.method || 'CASH',
                amount: Number(payment.amount || 0),
                reference: payment.reference || '',
            })),
            paymentMethods: config.paymentMethods || {},
            total: Number(config.total || 0),
            cartItems: config.cartItems || [],
            cartCount: Number(config.cartCount || 0),
            csrfToken: config.csrfToken,
            routes: config.routes || {},
            vatRate: Number(config.vatRate || 0),
            pricesIncludeVat: Boolean(config.pricesIncludeVat),
            addingProductId: null,
            recentProductId: null,
            cartFeedback: '',
            productDetail: null,
            longPressTimer: null,
            longPressTriggered: false,
            tapTimers: {},

            init() {
                if (!this.payments.length) {
                    this.payments = [{ method: 'CASH', amount: this.grandTotal, reference: '' }];
                }
            },

            setCategory(category) {
                this.activeCategory = category;
                this.visibleLimit = Math.max(this.visibleLimit, 80);
            },

            setDepartment(department) {
                this.activeDepartment = department;
                this.visibleLimit = Math.max(this.visibleLimit, 80);
            },

            chipClass(active, tone = 'light') {
                if (active && tone === 'dark') {
                    return 'bg-slate-950 text-white border-slate-950 shadow-sm';
                }

                if (active) {
                    return 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
                }

                return 'bg-white text-slate-700 border-slate-200 hover:border-indigo-200 hover:text-indigo-700';
            },

            productVisible(card) {
                const query = this.productQuery.toLowerCase().trim();
                const barcode = this.barcodeQuery.toLowerCase().trim();
                const category = card.dataset.category || '';
                const department = card.dataset.department || '';
                const haystack = card.dataset.search || '';
                const productBarcode = card.dataset.barcode || '';
                const index = Number(card.dataset.index || 0);
                const filtered = query || barcode || this.activeCategory !== 'All' || this.activeDepartment !== 'All';

                return (this.activeCategory === 'All' || category === this.activeCategory)
                    && (this.activeDepartment === 'All' || department === this.activeDepartment)
                    && (!query || haystack.includes(query))
                    && (!barcode || productBarcode.includes(barcode))
                    && (filtered || index < this.visibleLimit);
            },

            get discountedSubtotal() {
                return Math.max(this.total - Math.min(Math.max(this.discount || 0, 0), this.total), 0);
            },

            get taxAmount() {
                if (!this.vatRate || this.discountedSubtotal <= 0) {
                    return 0;
                }

                if (this.pricesIncludeVat) {
                    const taxable = this.discountedSubtotal / (1 + (this.vatRate / 100));
                    return this.discountedSubtotal - taxable;
                }

                return this.discountedSubtotal * (this.vatRate / 100);
            },

            get grandTotal() {
                return this.pricesIncludeVat
                    ? this.discountedSubtotal
                    : this.discountedSubtotal + this.taxAmount;
            },

            money(value) {
                return new Intl.NumberFormat().format(Math.max(Number(value || 0), 0)) + ' RWF';
            },

            tapProduct(event, product) {
                if (this.longPressTriggered) {
                    this.longPressTriggered = false;
                    return;
                }

                if (product.is_out) {
                    this.cartFeedback = product.name + ' is out of stock.';
                    return;
                }

                if (this.tapTimers[product.id]) {
                    clearTimeout(this.tapTimers[product.id]);
                    delete this.tapTimers[product.id];
                    this.addProduct(event, product, 2);
                    return;
                }

                this.tapTimers[product.id] = setTimeout(() => {
                    delete this.tapTimers[product.id];
                    this.addProduct(event, product, 1);
                }, 180);
            },

            startLongPress(product) {
                this.longPressTriggered = false;
                clearTimeout(this.longPressTimer);

                this.longPressTimer = setTimeout(() => {
                    this.longPressTriggered = true;
                    this.productDetail = product;
                }, 520);
            },

            cancelLongPress() {
                clearTimeout(this.longPressTimer);
            },

            async addProduct(event, product, quantity = 1) {
                const form = event?.currentTarget;
                const body = form ? new FormData(form) : new FormData();

                if (!form) {
                    body.append('_token', this.csrfToken);
                    body.append('product_id', product.id);
                }

                body.set('quantity', String(quantity));
                this.addingProductId = product.id;

                try {
                    const response = await fetch(form ? form.action : this.routes.add, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body,
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Cart update failed.');
                    }

                    this.syncCart(payload);
                    this.recentProductId = product.id;
                    this.cartFeedback = payload.message || product.name + ' added to cart.';

                    setTimeout(() => {
                        if (this.recentProductId === product.id) {
                            this.recentProductId = null;
                        }
                    }, 450);
                } catch (error) {
                    this.cartFeedback = error.message || 'Cart update failed.';
                } finally {
                    this.addingProductId = null;
                }
            },

            async cartPost(route, productId = null) {
                const body = new FormData();
                body.append('_token', this.csrfToken);

                if (productId) {
                    body.append('product_id', productId);
                }

                try {
                    const response = await fetch(route, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body,
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Cart update failed.');
                    }

                    this.syncCart(payload);
                    this.cartFeedback = payload.message || 'Cart updated.';
                } catch (error) {
                    this.cartFeedback = error.message || 'Cart update failed.';
                }
            },

            syncCart(payload) {
                this.cartItems = payload.cart_items || [];
                this.cartCount = Number(payload.cart_count || 0);
                this.total = Number(payload.total || 0);

                if (this.payments.length === 1) {
                    this.payments[0].amount = this.grandTotal;
                }
            },

            cartLineTotal(item) {
                return Number(item.price || 0) * Number(item.quantity || 0);
            },

            addPayment() {
                this.payments.push({ method: 'CASH', amount: 0, reference: '' });
            },

            removePayment(index) {
                if (this.payments.length > 1) {
                    this.payments.splice(index, 1);
                }
            },

            fillFirstPayment() {
                if (!this.payments.length) {
                    this.addPayment();
                }

                this.payments[0].amount = this.grandTotal;
            },

            closeProductDetail() {
                this.productDetail = null;
            },
        };
    };
</script>

<div
    class="pos-screen mx-auto max-w-[1920px] space-y-3 pb-24 xl:pb-0"
    :class="{ 'pos-kiosk': posKiosk }"
    x-data="posTerminal({
        total: @js($total),
        vatRate: @js($taxPreview['vat_rate'] ?? 0),
        pricesIncludeVat: @js($taxPreview['prices_include_vat'] ?? true),
        discount: @js((float) old('discount', 0)),
        paymentMethods: @js($paymentMethods),
        initialPayments: @js(array_values($oldPayments)),
        cartItems: @js(array_values($cart)),
        cartCount: @js($cartCount),
        csrfToken: @js(csrf_token()),
        visibleLimit: 80,
        routes: {
            add: @js(route('pos.add')),
            increase: @js(route('pos.increase')),
            decrease: @js(route('pos.decrease')),
            remove: @js(route('pos.remove')),
            clear: @js(route('pos.clear')),
        },
    })"
>
    <div class="pos-page-header flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Cashier terminal</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Point of Sale</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">
                Shift {{ $shift->shift_code ?? ('#' . $shift->id) }} is open.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex">
            <button
                type="button"
                class="rounded-xl bg-slate-950 px-3 py-2.5 text-center text-xs font-black text-white shadow-lg shadow-slate-950/15 transition hover:bg-slate-800"
                @click="togglePosKiosk()"
                x-text="posKiosk ? 'Exit Full' : 'Full Screen'"
            >
                Full Screen
            </button>
            <a href="{{ route('shifts.current') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-xs font-black text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700">
                Shift
            </a>
            <a href="{{ route('sales.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-xs font-black text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700">
                Sales
            </a>
            <button
                type="button"
                class="rounded-xl bg-indigo-600 px-3 py-2.5 text-center text-xs font-black text-white shadow-lg shadow-indigo-600/20 xl:hidden"
                @click="cartOpen = true"
            >
                Cart <span x-text="cartCount"></span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div
        x-cloak
        x-show="cartFeedback"
        x-transition.opacity.duration.150ms
        class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-700"
        x-text="cartFeedback"
    ></div>

    <div class="pos-workspace grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_360px] 2xl:grid-cols-[minmax(0,1fr)_390px]">
        <section class="pos-products-section min-w-0 space-y-3">
            <div class="pos-sticky-toolbar sticky top-14 z-30 -mx-2 space-y-2 border-y border-slate-200 bg-slate-100/95 px-2 py-2 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:bg-white/95 sm:p-2 sm:shadow-sm">
                <div class="grid gap-2 lg:grid-cols-[1fr_260px_auto]">
                    <input
                        id="productSearch"
                        type="search"
                        placeholder="Search product or barcode"
                        class="h-10 rounded-lg border-slate-200 bg-white text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                        x-model.debounce.180ms="productQuery"
                        autofocus
                    >

                    <input
                        id="barcodeSearch"
                        type="search"
                        placeholder="Scan barcode"
                        class="h-10 rounded-lg border-slate-200 bg-white text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                        x-model.debounce.120ms="barcodeQuery"
                    >

                    <button
                        type="button"
                        class="hidden h-10 rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 transition hover:border-rose-200 hover:text-rose-700 lg:block"
                        @click="cartPost(routes.clear)"
                    >
                        Clear Cart
                    </button>
                </div>

                <div class="touch-scroll flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-black transition"
                        :class="chipClass(activeDepartment === 'All')"
                        @click="setDepartment('All')"
                    >
                        All departments
                    </button>

                    @foreach($departments as $department)
                        <button
                            type="button"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-black transition"
                            :class="chipClass(activeDepartment === @js($department->name))"
                            @click="setDepartment(@js($department->name))"
                        >
                            {{ $department->name }}
                        </button>
                    @endforeach
                </div>

                <div class="touch-scroll flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-black transition"
                        :class="chipClass(activeCategory === 'All', 'dark')"
                        @click="setCategory('All')"
                    >
                        All
                    </button>

                    @foreach($categories as $category)
                        <button
                            type="button"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-black transition"
                            :class="chipClass(activeCategory === @js($category->name), 'dark')"
                            @click="setCategory(@js($category->name))"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="pos-product-grid grid grid-cols-2 gap-2 min-[520px]:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-7 min-[1800px]:grid-cols-8">
                @forelse($products as $product)
                    @include('pos._product-card', ['product' => $product, 'loopIndex' => $loop->index])
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-semibold text-slate-500">
                        No active products are available for POS.
                    </div>
                @endforelse
            </div>

            @if($products->count() > 80)
                <div class="flex justify-center py-2">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700"
                        x-show="!productQuery && !barcodeQuery && activeCategory === 'All' && activeDepartment === 'All' && visibleLimit < {{ $products->count() }}"
                        @click="visibleLimit += 80"
                    >
                        Load more products
                    </button>
                </div>
            @endif
        </section>

        <aside class="hidden min-w-0 xl:block">
            @include('pos._cart-panel', ['cartPanelMode' => 'desktop', 'cartCount' => $cartCount])
        </aside>
    </div>

    <div
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 p-3 backdrop-blur-sm sm:items-center"
        x-cloak
        x-show="productDetail"
        x-transition.opacity
        @keydown.escape.window="closeProductDetail"
        @click.self="closeProductDetail"
    >
        <template x-if="productDetail">
            <div class="w-full max-w-sm rounded-2xl bg-white p-4 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600" x-text="productDetail.category"></p>
                        <h3 class="mt-1 text-xl font-black leading-tight text-slate-950" x-text="productDetail.name"></h3>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-black text-slate-700"
                        @click="closeProductDetail"
                    >
                        X
                    </button>
                </div>

                <div class="mt-4 flex h-44 items-center justify-center rounded-xl bg-white ring-1 ring-slate-100">
                    <img
                        x-show="productDetail.image"
                        :src="productDetail.image"
                        :alt="productDetail.name"
                        class="h-full w-full object-contain p-3"
                        loading="lazy"
                    >
                    <div
                        x-show="!productDetail.image"
                        class="flex h-full w-full flex-col items-center justify-center rounded-xl bg-slate-50"
                    >
                        <span class="text-4xl leading-none">📦</span>
                        <span class="mt-2 text-xs font-black uppercase tracking-wide text-slate-400">No Image</span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Price</p>
                        <p class="mt-1 text-lg font-black text-slate-950" x-text="money(productDetail.price)"></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Unit</p>
                        <p class="mt-1 text-lg font-black text-slate-950" x-text="productDetail.unit"></p>
                    </div>
                    <div class="col-span-2 rounded-xl bg-slate-50 p-3">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Stock</p>
                        <p class="mt-1 text-base font-black text-slate-950" x-text="productDetail.stock_label"></p>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-4 flex h-11 w-full items-center justify-center rounded-xl bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:bg-slate-300 disabled:shadow-none"
                    :disabled="productDetail.is_out"
                    @click="addProduct(null, productDetail); closeProductDetail()"
                >
                    + Add To Cart
                </button>
            </div>
        </template>
    </div>

    <button
        type="button"
        class="pos-floating-cart fixed bottom-[5.25rem] right-3 z-40 flex items-center gap-2 rounded-2xl bg-slate-950 px-3 py-2.5 text-white shadow-2xl shadow-slate-950/25 xl:hidden"
        @click="cartOpen = true"
    >
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black">
            <span x-text="cartCount"></span>
        </span>
        <span class="text-sm font-black" x-text="new Intl.NumberFormat().format(total)"></span>
        <span class="text-[10px] font-black text-slate-300">RWF</span>
    </button>

    <div
        class="fixed inset-0 z-50 xl:hidden"
        x-cloak
        x-show="cartOpen"
        x-transition.opacity
        @keydown.escape.window="cartOpen = false"
    >
        <button
            type="button"
            class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"
            aria-label="Close cart"
            @click="cartOpen = false"
        ></button>

        <div
            class="absolute inset-x-0 bottom-0 max-h-[92vh] overflow-hidden rounded-t-3xl bg-white shadow-2xl"
            x-show="cartOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
        >
            @include('pos._cart-panel', ['cartPanelMode' => 'drawer', 'cartCount' => $cartCount])
        </div>
    </div>
</div>

@endsection
