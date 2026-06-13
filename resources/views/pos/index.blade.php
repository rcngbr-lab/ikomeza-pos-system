@extends('layouts.app')

@section('content')

<div class="pos-screen space-y-4">
    <div class="pos-header flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">
                Cashier Terminal
            </p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-4xl">
                Point of Sale
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Shift {{ $shift->shift_code ?? ('#' . $shift->id) }} is open.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex">
            <a href="{{ route('shifts.current') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-xs font-bold text-slate-700 shadow-sm sm:px-4 sm:py-3 sm:text-sm">
                Shift
            </a>
            <a href="{{ route('sales.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-xs font-bold text-slate-700 shadow-sm sm:px-4 sm:py-3 sm:text-sm">
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

    <div class="pos-workspace grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="pos-products-panel min-w-0 {{ count($cart) ? 'order-2 xl:order-1' : 'order-1' }} space-y-3 sm:space-y-4">
            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4 lg:grid-cols-[1fr_auto]">
                <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                    <input
                        id="productSearch"
                        type="search"
                        placeholder="Search by name or barcode"
                        class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                        autofocus
                    >

                    <input
                        id="barcodeSearch"
                        type="search"
                        placeholder="Scan barcode"
                        class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                    >
                </div>

                <form method="POST" action="{{ route('pos.clear') }}">
                    @csrf
                    <button class="h-10 w-full rounded-xl border border-slate-200 px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 sm:h-12 sm:text-sm lg:w-auto">
                        Clear Cart
                    </button>
                </form>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    class="department-chip shrink-0 rounded-full bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white sm:px-5 sm:py-2.5 sm:text-sm"
                    data-department="All"
                >
                    All Departments
                </button>

                @foreach($departments as $department)
                    <button
                        type="button"
                        class="department-chip shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 sm:px-5 sm:py-2.5 sm:text-sm"
                        data-department="{{ $department->name }}"
                    >
                        {{ $department->name }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    class="category-chip shrink-0 rounded-full bg-slate-950 px-3 py-1.5 text-[11px] font-bold text-white sm:px-5 sm:py-2.5 sm:text-sm"
                    data-category="All"
                >
                    All
                </button>

                @foreach($categories as $category)
                    <button
                        type="button"
                        class="category-chip shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 sm:px-5 sm:py-2.5 sm:text-sm"
                        data-category="{{ $category->name }}"
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <div class="pos-product-grid grid grid-cols-2 gap-2 min-[420px]:grid-cols-3 sm:gap-3 sm:[grid-template-columns:repeat(auto-fill,minmax(150px,1fr))] xl:[grid-template-columns:repeat(auto-fill,minmax(172px,1fr))] 2xl:[grid-template-columns:repeat(auto-fill,minmax(190px,1fr))]">
                @foreach($products as $product)
                    @php
                        $isLow = $product->track_stock && $product->stock <= $product->alert_stock;
                        $isOut = $product->track_stock && $product->stock <= 0;
                    @endphp

                    <form
                        method="POST"
                        action="{{ route('pos.add') }}"
                        class="product-card group rounded-xl border border-slate-200 bg-white p-2 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md sm:rounded-2xl sm:p-3.5 {{ $isOut ? 'opacity-60' : '' }}"
                        data-category="{{ $product->category->name ?? 'Uncategorized' }}"
                        data-department="{{ $product->department->name ?? 'Unassigned' }}"
                        data-name="{{ strtolower($product->name) }}"
                        data-barcode="{{ strtolower($product->barcode ?? '') }}"
                    >
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <button type="submit" class="flex min-h-[112px] w-full flex-col text-left sm:min-h-[162px]" {{ $isOut ? 'disabled' : '' }}>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="line-clamp-2 max-h-8 text-[11px] font-black leading-4 text-slate-950 sm:max-h-none sm:truncate sm:text-sm">
                                        {{ $product->name }}
                                    </p>
                                    <p class="mt-1 hidden truncate text-xs text-slate-500 sm:block">
                                        {{ $product->barcode ?: $product->product_code }}
                                    </p>
                                </div>

                                <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-black sm:px-2 sm:py-1 sm:text-[10px] {{ $isOut ? 'bg-rose-100 text-rose-700' : ($isLow ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $isOut ? 'OUT' : ($isLow ? 'LOW' : 'OK') }}
                                </span>
                            </div>

                            <span class="mt-1.5 inline-flex w-fit rounded-full px-1.5 py-0.5 text-[8px] font-black uppercase sm:px-2.5 sm:py-1 sm:text-[10px] {{ ($product->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $product->department?->code ?? ($product->department->name ?? 'NA') }}
                            </span>

                            <div class="mt-auto pt-2 sm:pt-5">
                                <p class="text-base font-black leading-none text-slate-950 sm:text-2xl">
                                    {{ number_format($product->selling_price) }}
                                </p>
                                <p class="mt-0.5 truncate text-[9px] font-semibold text-slate-500 sm:text-xs">
                                    RWF per {{ $product->unit ?? 'item' }}
                                </p>
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-2 sm:mt-4">
                                <span class="truncate text-[9px] font-semibold text-slate-500 sm:text-xs">
                                    Stock: {{ number_format($product->stock) }}
                                </span>
                                <span class="shrink-0 rounded-lg bg-slate-950 px-2 py-1 text-[9px] font-black text-white transition group-hover:bg-indigo-600 sm:px-3 sm:py-2 sm:text-xs">
                                    Add
                                </span>
                            </div>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>

        <aside class="pos-cart-panel min-w-0 {{ count($cart) ? 'order-1 xl:order-2' : 'order-2' }} xl:sticky xl:top-20 xl:self-start">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-3 sm:p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-black text-slate-950 sm:text-xl">
                                Current Sale
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500 sm:mt-1 sm:text-sm">
                                {{ count($cart) }} line items
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                Total
                            </p>
                            <p class="text-xl font-black text-indigo-600 sm:text-2xl">
                                {{ number_format($total) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pos-cart-items max-h-[34vh] space-y-2 overflow-y-auto p-3 sm:max-h-[44vh] sm:space-y-3 sm:p-4">
                    @forelse($cart as $item)
                        @php $lineTotal = $item['price'] * $item['quantity']; @endphp

                        <div class="rounded-xl bg-slate-50 p-3 sm:p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">
                                        {{ $item['name'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ number_format($item['price']) }} RWF
                                    </p>
                                    <p class="mt-1 text-[11px] font-black uppercase tracking-wide {{ ($item['department_code'] ?? '') === 'KITCHEN' ? 'text-amber-700' : 'text-indigo-700' }}">
                                        {{ $item['department'] ?? 'Unassigned' }}
                                    </p>
                                </div>
                                <p class="text-sm font-black text-slate-950">
                                    {{ number_format($lineTotal) }}
                                </p>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 sm:mt-4">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('pos.decrease') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-base font-black text-slate-700 shadow-sm sm:h-9 sm:w-9 sm:text-lg">
                                            -
                                        </button>
                                    </form>

                                    <span class="min-w-8 text-center text-sm font-black">
                                        {{ $item['quantity'] }}
                                    </span>

                                    <form method="POST" action="{{ route('pos.increase') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-base font-black text-white shadow-sm sm:h-9 sm:w-9 sm:text-lg">
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

                <form method="POST" action="{{ route('pos.checkout') }}" class="space-y-3 border-t border-slate-100 p-3 sm:space-y-4 sm:p-5">
                    @csrf
                    @php
                        $oldPayments = old('payments', [[
                            'method' => old('payment_method', 'CASH'),
                            'amount' => old('amount_paid', $total),
                            'reference' => null,
                        ]]);
                    @endphp

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <select
                            name="table_id"
                            class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                        >
                            <option value="">Walk-in / no table</option>
                            @foreach($tables as $table)
                                <option value="{{ $table->id }}" @selected((int) old('table_id') === (int) $table->id)>
                                    {{ $table->name }}{{ $table->section ? ' - ' . $table->section : '' }} ({{ str($table->status)->headline() }})
                                </option>
                            @endforeach
                        </select>

                        <select
                            name="customer_id"
                            class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                        >
                            <option value="">No customer account</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((int) old('customer_id') === (int) $customer->id)>
                                    {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                                </option>
                            @endforeach
                        </select>

                        <input
                            type="text"
                            name="customer_name"
                            value="{{ old('customer_name') }}"
                            placeholder="Customer name optional"
                            class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                        >
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <input
                            id="discountAmount"
                            type="number"
                            step="0.01"
                            min="0"
                            max="{{ $total }}"
                            name="discount"
                            value="{{ old('discount', 0) }}"
                            placeholder="Discount amount"
                            class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                        >

                        <input
                            type="text"
                            name="discount_reason"
                            value="{{ old('discount_reason') }}"
                            placeholder="Discount reason / approval note"
                            class="h-10 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500 sm:h-12"
                        >
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 sm:p-3">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">
                                Payments
                            </p>
                            <button
                                type="button"
                                id="addPayment"
                                class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-black text-indigo-700 shadow-sm"
                            >
                                + Add
                            </button>
                        </div>

                        <div id="paymentRows" class="space-y-2">
                            @foreach($oldPayments as $index => $payment)
                                <div class="payment-row grid gap-2 rounded-xl bg-white p-2 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
                                    <select
                                        name="payments[{{ $index }}][method]"
                                        class="payment-method h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        @foreach($paymentMethods as $method => $label)
                                            <option value="{{ $method }}" @selected(($payment['method'] ?? 'CASH') === $method)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="payments[{{ $index }}][amount]"
                                        value="{{ $payment['amount'] ?? $total }}"
                                        placeholder="Amount"
                                        class="payment-amount h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500"
                                    >

                                    <div class="grid grid-cols-[1fr_auto] gap-2 sm:contents">
                                        <input
                                            type="text"
                                            name="payments[{{ $index }}][reference]"
                                            value="{{ $payment['reference'] ?? '' }}"
                                            placeholder="Ref"
                                            class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2"
                                        >
                                        <button
                                            type="button"
                                            class="remove-payment h-10 rounded-lg px-2 text-xs font-black text-rose-600"
                                            aria-label="Remove payment"
                                        >
                                            X
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-950 p-3 text-white sm:p-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-slate-300">
                                <span>Subtotal</span>
                                <span>{{ number_format($total) }} RWF</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-300">
                                <span>VAT {{ number_format($taxPreview['vat_rate'], 1) }}%</span>
                                <span>{{ number_format($taxPreview['tax']) }} RWF</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-white/10 pt-2">
                                <span class="text-sm text-slate-300">Estimated due</span>
                                <span id="estimatedDue" class="text-xl font-black sm:text-3xl">{{ number_format($taxPreview['grand_total']) }} RWF</span>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="h-12 w-full rounded-xl bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50 sm:h-14 sm:text-base"
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
    const departmentChips = Array.from(document.querySelectorAll('.department-chip'));
    let activeCategory = 'All';
    let activeDepartment = 'All';

    function filterProducts() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const barcode = (barcodeInput?.value || '').toLowerCase().trim();

        productCards.forEach((card) => {
            const matchesCategory = activeCategory === 'All' || card.dataset.category === activeCategory;
            const matchesDepartment = activeDepartment === 'All' || card.dataset.department === activeDepartment;
            const matchesSearch = !query || card.dataset.name.includes(query) || card.dataset.barcode.includes(query);
            const matchesBarcode = !barcode || card.dataset.barcode.includes(barcode);

            card.classList.toggle('hidden', !(matchesDepartment && matchesCategory && matchesSearch && matchesBarcode));
        });
    }

    departmentChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            activeDepartment = chip.dataset.department;

            departmentChips.forEach((item) => {
                item.classList.remove('bg-indigo-600', 'text-white');
                item.classList.add('bg-white', 'text-slate-700', 'border', 'border-slate-200');
            });

            chip.classList.add('bg-indigo-600', 'text-white');
            chip.classList.remove('bg-white', 'text-slate-700', 'border', 'border-slate-200');

            filterProducts();
        });
    });

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

    const totalAmount = Number('{{ $total }}');
    const paymentRows = document.getElementById('paymentRows');
    const addPayment = document.getElementById('addPayment');
    const discountAmount = document.getElementById('discountAmount');
    const estimatedDue = document.getElementById('estimatedDue');
    const paymentMethodOptions = @json($paymentMethods);
    let paymentIndex = {{ count($oldPayments) }};

    function money(value) {
        return new Intl.NumberFormat().format(Math.max(value, 0)) + ' RWF';
    }

    function currentDue() {
        const discount = Math.min(Number(discountAmount?.value || 0), totalAmount);
        return Math.max(totalAmount - discount, 0);
    }

    function syncEstimate() {
        const due = currentDue();

        if (estimatedDue) {
            estimatedDue.textContent = money(due);
        }
    }

    function optionMarkup(selected = 'CASH') {
        return Object.entries(paymentMethodOptions).map(([method, label]) => (
            `<option value="${method}" ${method === selected ? 'selected' : ''}>${label}</option>`
        )).join('');
    }

    function wirePaymentRow(row) {
        row.querySelector('.payment-method')?.addEventListener('change', (event) => {
            const amount = row.querySelector('.payment-amount');

            if (amount && event.target.value !== 'CASH' && event.target.value !== 'CREDIT') {
                amount.value = currentDue();
            }

            if (amount && event.target.value === 'CREDIT') {
                amount.value = 0;
            }
        });

        row.querySelector('.remove-payment')?.addEventListener('click', () => {
            if (paymentRows.querySelectorAll('.payment-row').length > 1) {
                row.remove();
            }
        });
    }

    addPayment?.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'payment-row grid gap-2 rounded-xl bg-white p-2 shadow-sm sm:grid-cols-[1fr_1fr_auto]';
        row.innerHTML = `
            <select name="payments[${paymentIndex}][method]" class="payment-method h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500">
                ${optionMarkup('CASH')}
            </select>
            <input type="number" step="0.01" min="0" name="payments[${paymentIndex}][amount]" value="0" placeholder="Amount" class="payment-amount h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500">
            <div class="grid grid-cols-[1fr_auto] gap-2 sm:contents">
                <input type="text" name="payments[${paymentIndex}][reference]" placeholder="Ref" class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2">
                <button type="button" class="remove-payment h-10 rounded-lg px-2 text-xs font-black text-rose-600" aria-label="Remove payment">X</button>
            </div>
        `;
        paymentIndex += 1;
        paymentRows.appendChild(row);
        wirePaymentRow(row);
    });

    paymentRows?.querySelectorAll('.payment-row').forEach(wirePaymentRow);
    discountAmount?.addEventListener('input', syncEstimate);
    syncEstimate();
</script>

@endsection
