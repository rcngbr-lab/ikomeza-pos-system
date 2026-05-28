@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">
                Cashier Terminal
            </p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                Point of Sale
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Shift {{ $shift->shift_code ?? ('#' . $shift->id) }} is open.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:flex">
            <a href="{{ route('shifts.current') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-bold text-slate-700 shadow-sm">
                Shift
            </a>
            <a href="{{ route('sales.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-bold text-slate-700 shadow-sm">
                Sales
            </a>
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

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="space-y-4">
            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_auto]">
                <div class="grid gap-3 sm:grid-cols-2">
                    <input
                        id="productSearch"
                        type="search"
                        placeholder="Search by name or barcode"
                        class="h-12 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        autofocus
                    >

                    <input
                        id="barcodeSearch"
                        type="search"
                        placeholder="Scan barcode"
                        class="h-12 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <form method="POST" action="{{ route('pos.clear') }}">
                    @csrf
                    <button class="h-12 w-full rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 lg:w-auto">
                        Clear Cart
                    </button>
                </form>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    class="category-chip rounded-full bg-slate-950 px-5 py-2.5 text-sm font-bold text-white"
                    data-category="All"
                >
                    All
                </button>

                @foreach($categories as $category)
                    <button
                        type="button"
                        class="category-chip rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700"
                        data-category="{{ $category->name }}"
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-4">
                @foreach($products as $product)
                    @php
                        $isLow = $product->track_stock && $product->stock <= $product->alert_stock;
                        $isOut = $product->track_stock && $product->stock <= 0;
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('pos.add') }}"
                        class="product-card group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md"
                        data-category="{{ $product->category->name ?? 'Uncategorized' }}"
                        data-name="{{ strtolower($product->name) }}"
                        data-barcode="{{ strtolower($product->barcode ?? '') }}"
                    >
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <button type="submit" class="flex h-full w-full flex-col text-left" {{ $isOut ? 'disabled' : '' }}>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">
                                        {{ $product->name }}
                                    </p>
                                    <p class="mt-1 truncate text-xs text-slate-500">
                                        {{ $product->barcode ?: $product->product_code }}
                                    </p>
                                </div>

                                <span class="rounded-full px-2 py-1 text-[10px] font-black {{ $isOut ? 'bg-rose-100 text-rose-700' : ($isLow ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $isOut ? 'OUT' : ($isLow ? 'LOW' : 'OK') }}
                                </span>
                            </div>

                            <div class="mt-6">
                                <p class="text-2xl font-black text-slate-950">
                                    {{ number_format($product->selling_price) }}
                                </p>
                                <p class="text-xs font-semibold text-slate-500">
                                    RWF per {{ $product->unit ?? 'item' }}
                                </p>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">
                                    Stock: {{ number_format($product->stock) }}
                                </span>
                                <span class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-black text-white transition group-hover:bg-indigo-600">
                                    Add
                                </span>
                            </div>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>

        <aside class="xl:sticky xl:top-20 xl:self-start">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">
                                Current Sale
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ count($cart) }} line items
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                Total
                            </p>
                            <p class="text-2xl font-black text-indigo-600">
                                {{ number_format($total) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="max-h-[44vh] space-y-3 overflow-y-auto p-4">
                    @forelse($cart as $item)
                        @php $lineTotal = $item['price'] * $item['quantity']; @endphp

                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">
                                        {{ $item['name'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ number_format($item['price']) }} RWF
                                    </p>
                                </div>
                                <p class="text-sm font-black text-slate-950">
                                    {{ number_format($lineTotal) }}
                                </p>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('pos.decrease') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                        <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-lg font-black text-slate-700 shadow-sm">
                                            -
                                        </button>
                                    </form>

                                    <span class="min-w-8 text-center text-sm font-black">
                                        {{ $item['quantity'] }}
                                    </span>

                                    <form method="POST" action="{{ route('pos.increase') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                        <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-lg font-black text-white shadow-sm">
                                            +
                                        </button>
                                    </form>
                                </div>

                                <form method="POST" action="{{ route('pos.remove') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                    <button class="text-xs font-bold text-rose-600">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">
                            Add products to begin checkout.
                        </div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('pos.checkout') }}" class="space-y-4 border-t border-slate-100 p-5">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <input
                            type="text"
                            name="customer_name"
                            value="{{ old('customer_name') }}"
                            placeholder="Customer name optional"
                            class="h-12 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >

                        <select
                            id="paymentMethod"
                            name="payment_method"
                            class="h-12 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach($paymentMethods as $method => $label)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input
                        id="amountPaid"
                        type="number"
                        step="0.01"
                        min="0"
                        name="amount_paid"
                        value="{{ old('amount_paid', $total) }}"
                        placeholder="Amount paid"
                        class="h-12 w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <div class="rounded-xl bg-slate-950 p-4 text-white">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-300">Grand total</span>
                            <span class="text-3xl font-black">{{ number_format($total) }} RWF</span>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="h-14 w-full rounded-xl bg-indigo-600 text-base font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50"
                        {{ empty($cart) ? 'disabled' : '' }}
                    >
                        Complete Sale
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>

<script>
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    const searchInput = document.getElementById('productSearch');
    const barcodeInput = document.getElementById('barcodeSearch');
    const categoryChips = Array.from(document.querySelectorAll('.category-chip'));
    let activeCategory = 'All';

    function filterProducts() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const barcode = (barcodeInput?.value || '').toLowerCase().trim();

        productCards.forEach((card) => {
            const matchesCategory = activeCategory === 'All' || card.dataset.category === activeCategory;
            const matchesSearch = !query || card.dataset.name.includes(query) || card.dataset.barcode.includes(query);
            const matchesBarcode = !barcode || card.dataset.barcode.includes(barcode);

            card.classList.toggle('hidden', !(matchesCategory && matchesSearch && matchesBarcode));
        });
    }

    categoryChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            activeCategory = chip.dataset.category;

            categoryChips.forEach((item) => {
                item.classList.remove('bg-slate-950', 'text-white');
                item.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200');
            });

            chip.classList.add('bg-slate-950', 'text-white');
            chip.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200');

            filterProducts();
        });
    });

    searchInput?.addEventListener('input', filterProducts);
    barcodeInput?.addEventListener('input', filterProducts);

    document.getElementById('paymentMethod')?.addEventListener('change', (event) => {
        const amountPaid = document.getElementById('amountPaid');

        if (amountPaid && event.target.value !== 'CASH') {
            amountPaid.value = '{{ $total }}';
        }
    });
</script>

@endsection
