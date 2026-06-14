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
            discount: Number(config.discount || 0),
            payments: (config.initialPayments || []).map((payment) => ({
                method: payment.method || 'CASH',
                amount: Number(payment.amount || 0),
                reference: payment.reference || '',
            })),
            paymentMethods: config.paymentMethods || {},
            total: Number(config.total || 0),
            vatRate: Number(config.vatRate || 0),
            pricesIncludeVat: Boolean(config.pricesIncludeVat),

            init() {
                if (!this.payments.length) {
                    this.payments = [{ method: 'CASH', amount: this.grandTotal, reference: '' }];
                }
            },

            setCategory(category) {
                this.activeCategory = category;
            },

            setDepartment(department) {
                this.activeDepartment = department;
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

                return (this.activeCategory === 'All' || category === this.activeCategory)
                    && (this.activeDepartment === 'All' || department === this.activeDepartment)
                    && (!query || haystack.includes(query))
                    && (!barcode || productBarcode.includes(barcode));
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
        };
    };
</script>

<div
    class="pos-screen mx-auto max-w-[1920px] space-y-3 pb-24 xl:pb-0"
    x-data="posTerminal({
        total: @js($total),
        vatRate: @js($taxPreview['vat_rate'] ?? 0),
        pricesIncludeVat: @js($taxPreview['prices_include_vat'] ?? true),
        discount: @js((float) old('discount', 0)),
        paymentMethods: @js($paymentMethods),
        initialPayments: @js(array_values($oldPayments)),
    })"
>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Cashier terminal</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Point of Sale</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">
                Shift {{ $shift->shift_code ?? ('#' . $shift->id) }} is open.
            </p>
        </div>

        <div class="grid grid-cols-3 gap-2 sm:flex">
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
                Cart {{ $cartCount }}
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

    <div class="pos-workspace grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_360px] 2xl:grid-cols-[minmax(0,1fr)_390px]">
        <section class="min-w-0 space-y-3">
            <div class="sticky top-14 z-30 -mx-2 space-y-2 border-y border-slate-200 bg-slate-100/95 px-2 py-2 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:bg-white/95 sm:p-2 sm:shadow-sm">
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

                    <form method="POST" action="{{ route('pos.clear') }}" class="hidden lg:block">
                        @csrf
                        <button class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 transition hover:border-rose-200 hover:text-rose-700">
                            Clear Cart
                        </button>
                    </form>
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
                    @php
                        $isLow = $product->track_stock && $product->stock <= $product->alert_stock;
                        $isOut = $product->track_stock && $product->stock <= 0;
                        $departmentCode = $product->department?->code ?? null;
                        $searchText = strtolower(trim(($product->name ?? '') . ' ' . ($product->barcode ?? '') . ' ' . ($product->product_code ?? '') . ' ' . ($product->category->name ?? '') . ' ' . ($product->department->name ?? '')));
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('pos.add') }}"
                        class="product-card group flex min-h-[112px] min-w-0 flex-col rounded-lg border border-slate-200 bg-white p-2 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md sm:min-h-[124px] {{ $isOut ? 'opacity-55' : '' }}"
                        data-pos-product
                        data-category="{{ $product->category->name ?? 'Uncategorized' }}"
                        data-department="{{ $product->department->name ?? 'Unassigned' }}"
                        data-search="{{ $searchText }}"
                        data-barcode="{{ strtolower($product->barcode ?? '') }}"
                        x-show="productVisible($el)"
                        x-transition.opacity.duration.120ms
                    >
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <button type="submit" class="flex h-full min-w-0 flex-1 flex-col text-left disabled:cursor-not-allowed" {{ $isOut ? 'disabled' : '' }}>
                            <div class="flex min-w-0 items-start gap-2">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-700">
                                    {{ strtoupper(substr($product->name, 0, 1)) }}
                                </span>
                                <span class="line-clamp-2 min-w-0 text-[12px] font-black leading-4 text-slate-950 sm:text-[13px]">
                                    {{ $product->name }}
                                </span>
                            </div>

                            <div class="mt-auto pt-2">
                                <p class="text-base font-black leading-none text-slate-950 sm:text-lg">
                                    {{ number_format($product->selling_price) }}
                                </p>
                                <p class="mt-0.5 truncate text-[10px] font-semibold text-slate-500">
                                    RWF / {{ $product->unit ?? 'item' }}
                                </p>
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="truncate text-[10px] font-bold text-slate-500">
                                    Stock {{ number_format($product->stock) }}
                                </span>
                                <span class="rounded-md px-2 py-1 text-[10px] font-black text-white transition group-hover:bg-indigo-600 {{ $isOut ? 'bg-rose-600' : ($isLow ? 'bg-amber-600' : 'bg-slate-950') }}">
                                    Add
                                </span>
                            </div>
                        </button>
                    </form>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-semibold text-slate-500">
                        No active products are available for POS.
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="hidden min-w-0 xl:block">
            @include('pos._cart-panel', ['cartPanelMode' => 'desktop', 'cartCount' => $cartCount])
        </aside>
    </div>

    <button
        type="button"
        class="pos-floating-cart fixed bottom-[5.25rem] right-3 z-40 flex items-center gap-2 rounded-2xl bg-slate-950 px-3 py-2.5 text-white shadow-2xl shadow-slate-950/25 xl:hidden"
        @click="cartOpen = true"
    >
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black">
            {{ $cartCount }}
        </span>
        <span class="text-sm font-black">{{ number_format($total) }}</span>
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
