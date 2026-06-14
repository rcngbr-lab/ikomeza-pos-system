@extends('layouts.app')

@section('content')
@php
    $period = request('filter', '');
    $query = request()->query();
    $sortUrl = function (string $column) use ($query, $sort, $direction) {
        return route('inventory.index', array_merge($query, [
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]));
    };
    $sortMark = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'up' : 'down') : '';
    $statusFor = function ($product) {
        if ((float) $product->stock <= 0) {
            return ['label' => 'OUT', 'class' => 'bg-rose-100 text-rose-700'];
        }

        if ((float) $product->stock <= (float) $product->alert_stock) {
            return ['label' => 'LOW', 'class' => 'bg-amber-100 text-amber-700'];
        }

        return ['label' => 'OK', 'class' => 'bg-emerald-100 text-emerald-700'];
    };
    $periodChips = [
        '' => 'All',
        'today' => 'Today',
        'week' => 'Weekly',
        'month' => 'Monthly',
        'year' => 'Yearly',
    ];
@endphp

<div class="dense-page">
    <div class="dense-header">
        <div>
            <p class="dense-eyebrow">Stock Control</p>
            <h1 class="dense-title">Inventory</h1>
            <p class="dense-subtitle">Dense stock view, requests, valuation, and movement history.</p>
        </div>

        <div class="touch-scroll flex gap-1.5 overflow-x-auto pb-1">
            @foreach($periodChips as $value => $label)
                <a
                    href="{{ route('inventory.index', array_merge($query, ['filter' => $value, 'page' => null])) }}"
                    class="dense-chip {{ $period === $value ? 'dense-chip-active' : '' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error') || ($errors->any() ?? false))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">
            {{ session('error') ?: $errors->first() }}
        </div>
    @endif

    <form method="GET" action="{{ route('inventory.index') }}" class="dense-toolbar">
        <select name="department_id" class="dense-select md:w-52">
            <option value="">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <select name="filter" class="dense-select md:w-40">
            <option value="">All Time</option>
            <option value="today" @selected($period === 'today')>Today</option>
            <option value="week" @selected($period === 'week')>This Week</option>
            <option value="month" @selected($period === 'month')>This Month</option>
            <option value="year" @selected($period === 'year')>This Year</option>
        </select>

        <input
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="Search product, barcode, category..."
            class="dense-input min-w-0 flex-1"
        >

        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <div class="grid grid-cols-2 gap-2 md:flex">
            <button class="dense-btn-dark">Apply</button>
            <a href="{{ route('inventory.index') }}" class="dense-btn-soft">Reset</a>
        </div>
    </form>

    <div class="dense-stat-row">
        <div class="dense-stat">
            <p class="dense-stat-label">Products</p>
            <p class="dense-stat-value">{{ number_format($totalProducts) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Low Stock</p>
            <p class="dense-stat-value text-amber-600">{{ number_format($lowStockProducts->count()) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Out of Stock</p>
            <p class="dense-stat-value text-rose-600">{{ number_format($outOfStockProducts->count()) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Inventory Value</p>
            <p class="dense-stat-value text-emerald-600">{{ number_format($inventoryValue ?? 0) }} RWF</p>
        </div>
    </div>

    <section class="dense-card">
        <div class="dense-card-header">
            <div>
                <h2 class="text-sm font-black text-slate-950">Inventory Table</h2>
                <p class="text-xs text-slate-500">Search, sort, and paginate products with value and stock status.</p>
            </div>
            <p class="text-xs font-bold text-slate-500">{{ $products->total() }} matched</p>
        </div>

        <div class="dense-table-wrap">
            <table class="dense-table min-w-[980px]">
                <thead>
                    <tr>
                        <th><a href="{{ $sortUrl('name') }}">Product {{ $sortMark('name') }}</a></th>
                        <th><a href="{{ $sortUrl('category') }}">Category {{ $sortMark('category') }}</a></th>
                        <th>Department</th>
                        <th class="text-right"><a href="{{ $sortUrl('stock') }}">Stock {{ $sortMark('stock') }}</a></th>
                        <th class="text-right"><a href="{{ $sortUrl('cost') }}">Cost {{ $sortMark('cost') }}</a></th>
                        <th class="text-right"><a href="{{ $sortUrl('price') }}">Selling {{ $sortMark('price') }}</a></th>
                        <th class="text-right"><a href="{{ $sortUrl('value') }}">Value {{ $sortMark('value') }}</a></th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $status = $statusFor($product);
                            $value = (float) $product->buy_price * (float) $product->stock;
                        @endphp
                        <tr>
                            <td>
                                <p class="font-black text-slate-950">{{ $product->name }}</p>
                                <p class="text-[11px] text-slate-500">{{ $product->barcode ?: $product->product_code ?: 'No barcode' }}</p>
                            </td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>
                                <span class="dense-badge {{ ($product->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                    {{ $product->department->name ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td class="text-right font-black">{{ number_format((float) $product->stock, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $product->buy_price) }}</td>
                            <td class="text-right font-bold">{{ number_format((float) $product->selling_price) }}</td>
                            <td class="text-right font-black">{{ number_format($value) }}</td>
                            <td><span class="dense-badge {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="dense-empty">No products match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            {{ $products->onEachSide(1)->links() }}
        </div>
    </section>

    <details class="dense-card">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 text-sm font-black text-slate-950 [&::-webkit-details-marker]:hidden">
            Stock Requests and Printable Stock Reports
            <span class="text-[11px] font-bold text-slate-500">Stock in and damage remain approval-based</span>
        </summary>

        <div class="grid gap-2 border-t border-slate-100 p-3 xl:grid-cols-2">
            <form method="POST" action="{{ route('inventory.stockin') }}" class="grid gap-2 rounded-lg border border-emerald-100 bg-emerald-50/50 p-2 md:grid-cols-[1fr_110px_1fr_auto]">
                @csrf
                <select name="product_id" required class="dense-select bg-white">
                    <option value="">Product to receive</option>
                    @foreach($allProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }}</option>
                    @endforeach
                </select>
                <input type="number" min="1" name="quantity" required placeholder="Qty" class="dense-input bg-white">
                <input type="text" name="note" placeholder="Supplier / note" class="dense-input bg-white">
                <button class="dense-btn bg-emerald-600 text-white hover:bg-emerald-700">Request In</button>
            </form>

            <form method="POST" action="{{ route('inventory.damage') }}" class="grid gap-2 rounded-lg border border-rose-100 bg-rose-50/50 p-2 md:grid-cols-[1fr_110px_1fr_auto]">
                @csrf
                <select name="product_id" required class="dense-select bg-white">
                    <option value="">Damaged product</option>
                    @foreach($allProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }}</option>
                    @endforeach
                </select>
                <input type="number" min="1" name="quantity" required placeholder="Qty" class="dense-input bg-white">
                <input type="text" name="note" placeholder="Reason" class="dense-input bg-white">
                <button class="dense-btn-danger">Request Damage</button>
            </form>

            <div class="flex flex-wrap gap-2 xl:col-span-2">
                <a href="{{ route('inventory.print', ['department_id' => $selectedDepartmentId, 'filter' => $period]) }}" target="_blank" class="dense-btn-soft">Print History</a>
                <a href="{{ route('inventory.print', ['type' => 'stock_in', 'department_id' => $selectedDepartmentId, 'filter' => $period]) }}" target="_blank" class="dense-btn-soft">Stock In Report</a>
                <a href="{{ route('inventory.print', ['type' => 'damage', 'department_id' => $selectedDepartmentId, 'filter' => $period]) }}" target="_blank" class="dense-btn-soft">Damaged Stock Report</a>
            </div>
        </div>
    </details>

    <div class="grid gap-3 xl:grid-cols-2">
        <section class="dense-card">
            <div class="dense-card-header">
                <div>
                    <h2 class="text-sm font-black text-amber-700">Low Stock Alerts</h2>
                    <p class="text-xs text-slate-500">Products near reorder level.</p>
                </div>
            </div>
            <div class="dense-table-wrap">
                <table class="dense-table min-w-[620px]">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Department</th>
                            <th class="text-right">Stock</th>
                            <th class="text-right">Alert</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockProducts as $product)
                            <tr>
                                <td class="font-black text-slate-950">{{ $product->name }}</td>
                                <td>{{ $product->department->name ?? 'Unassigned' }}</td>
                                <td class="text-right font-black text-amber-600">{{ number_format((float) $product->stock, 2) }}</td>
                                <td class="text-right">{{ number_format((float) $product->alert_stock, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="dense-empty">Stock levels are healthy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dense-card">
            <div class="dense-card-header">
                <div>
                    <h2 class="text-sm font-black text-slate-950">Stock History</h2>
                    <p class="text-xs text-slate-500">Latest inventory movement records.</p>
                </div>
            </div>
            <div class="dense-table-wrap">
                <table class="dense-table min-w-[760px]">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Dept</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Before</th>
                            <th class="text-right">After</th>
                            <th>User</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockHistory as $history)
                            <tr>
                                <td class="font-black text-slate-950">{{ $history->product->name ?? '-' }}</td>
                                <td><span class="dense-badge bg-slate-100 text-slate-700">{{ strtoupper($history->type) }}</span></td>
                                <td>{{ $history->department->name ?? $history->product?->department?->name ?? '-' }}</td>
                                <td class="text-right font-black">{{ number_format((float) $history->quantity, 2) }}</td>
                                <td class="text-right">{{ number_format((float) $history->before_stock, 2) }}</td>
                                <td class="text-right font-black text-indigo-600">{{ number_format((float) $history->after_stock, 2) }}</td>
                                <td>{{ $history->user->name ?? '-' }}</td>
                                <td>{{ optional($history->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="dense-empty">No stock history found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-3 py-2">
                {{ $stockHistory->onEachSide(1)->links() }}
            </div>
        </section>
    </div>
</div>
@endsection
