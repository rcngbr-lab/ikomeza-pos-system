@extends('layouts.app')

@section('content')

<div class="p-4 md:p-6 space-y-6 bg-slate-100 min-h-screen pb-32">

    <!-- PAGE HEADER -->

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>

            <h1 class="text-4xl font-black text-slate-900">
                Inventory
            </h1>

            <p class="text-slate-500 mt-1">
                Stock management & tracking
            </p>

        </div>

        <div class="flex gap-3 flex-wrap">

            <a href="{{ route('inventory.index') }}"
               class="px-4 py-3 rounded-2xl bg-slate-900 text-white font-bold text-sm">

                All

            </a>

            <a href="?filter=today"
               class="px-4 py-3 rounded-2xl bg-indigo-600 text-white font-bold text-sm">

                Today

            </a>

            <a href="?filter=week"
               class="px-4 py-3 rounded-2xl bg-emerald-600 text-white font-bold text-sm">

                Weekly

            </a>

            <a href="?filter=month"
               class="px-4 py-3 rounded-2xl bg-orange-500 text-white font-bold text-sm">

                Monthly

            </a>

            <a href="?filter=year"
               class="px-4 py-3 rounded-2xl bg-red-500 text-white font-bold text-sm">

                Yearly

            </a>

        </div>

    </div>

    <form method="GET" action="{{ route('inventory.index') }}" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[220px_220px_auto]">
            <select name="department_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>

            <select name="filter" class="rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold">
                <option value="">All Time</option>
                <option value="today" @selected(request('filter') === 'today')>Today</option>
                <option value="week" @selected(request('filter') === 'week')>This Week</option>
                <option value="month" @selected(request('filter') === 'month')>This Month</option>
                <option value="year" @selected(request('filter') === 'year')>This Year</option>
            </select>

            <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white">
                Filter Inventory
            </button>
        </div>
    </form>

    <!-- STATS -->

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">

            <p class="text-slate-500 text-sm">
                Total Products
            </p>

            <h2 class="text-5xl font-black mt-3 text-slate-900">
                {{ $totalProducts }}
            </h2>

        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">

            <p class="text-slate-500 text-sm">
                Low Stock
            </p>

            <h2 class="text-5xl font-black mt-3 text-orange-500">
                {{ $lowStockProducts->count() }}
            </h2>

        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">

            <p class="text-slate-500 text-sm">
                Out Of Stock
            </p>

            <h2 class="text-5xl font-black mt-3 text-red-500">
                {{ $outOfStockProducts->count() }}
            </h2>

        </div>

        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm">

            <p class="text-slate-500 text-sm">
                Inventory Value
            </p>

            <h2 class="text-4xl font-black mt-3 text-emerald-600">
                {{ number_format($inventoryValue) }} RWF
            </h2>

        </div>

    </div>

    <!-- STOCK ACTIONS -->

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

        <form
            method="POST"
            action="{{ route('inventory.stockin') }}"
            class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm"
        >
            @csrf

            <h2 class="text-2xl font-black text-slate-900">
                Stock In
            </h2>

            <p class="text-slate-500 text-sm mt-1">
                Receive new stock and update the inventory ledger.
            </p>

            <div class="grid gap-3 mt-5 md:grid-cols-3">
                <select name="product_id" required class="rounded-xl border-slate-200 bg-slate-50">
                    <option value="">Select product</option>
                    @foreach($allProducts as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }}
                        </option>
                    @endforeach
                </select>

                <input type="number" min="1" name="quantity" required placeholder="Quantity" class="rounded-xl border-slate-200 bg-slate-50">

                <input type="text" name="note" placeholder="Supplier / note" class="rounded-xl border-slate-200 bg-slate-50">
            </div>

            <button class="mt-4 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">
                Receive Stock
            </button>

            <a
                href="{{ route('inventory.print', ['type' => 'stock_in', 'department_id' => $selectedDepartmentId, 'filter' => request('filter')]) }}"
                target="_blank"
                class="ml-3 inline-flex rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700"
            >
                Stock In Report
            </a>
        </form>

        <form
            method="POST"
            action="{{ route('inventory.damage') }}"
            class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm"
        >
            @csrf

            <h2 class="text-2xl font-black text-slate-900">
                Damaged Stock
            </h2>

            <p class="text-slate-500 text-sm mt-1">
                Record breakage, expiry, leakage, or operational losses.
            </p>

            <div class="grid gap-3 mt-5 md:grid-cols-3">
                <select name="product_id" required class="rounded-xl border-slate-200 bg-slate-50">
                    <option value="">Select product</option>
                    @foreach($allProducts as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }}
                        </option>
                    @endforeach
                </select>

                <input type="number" min="1" name="quantity" required placeholder="Quantity" class="rounded-xl border-slate-200 bg-slate-50">

                <input type="text" name="note" placeholder="Reason" class="rounded-xl border-slate-200 bg-slate-50">
            </div>

            <button class="mt-4 rounded-xl bg-rose-600 px-5 py-3 text-sm font-black text-white">
                Record Damage
            </button>

            <a
                href="{{ route('inventory.print', ['type' => 'damage', 'department_id' => $selectedDepartmentId, 'filter' => request('filter')]) }}"
                target="_blank"
                class="ml-3 inline-flex rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-black text-rose-700"
            >
                Damaged Stock Report
            </a>
        </form>
    </div>

    <!-- CURRENT STOCK -->

    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">

        <div class="p-6 border-b border-slate-100">

            <h2 class="text-2xl font-black text-slate-900">
                Current Stock
            </h2>

            <p class="text-slate-500 text-sm mt-1">
                Available products in inventory
            </p>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-[900px] w-full">

                <thead class="bg-slate-100 text-slate-700">

                    <tr>

                        <th class="px-4 py-4 text-left font-bold">
                            Product
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Category
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Department
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Buy Price
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Sell Price
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Stock
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Alert
                        </th>

                        <th class="px-4 py-4 text-left font-bold">
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($products as $product)

                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition">

                        <td class="px-4 py-4 font-bold text-slate-900">
                            {{ $product->name }}
                        </td>

                        <td class="px-4 py-4 text-slate-600">
                            {{ $product->category->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ ($product->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $product->department->name ?? 'Unassigned' }}
                            </span>
                        </td>

                        <td class="px-4 py-4">
                            {{ number_format($product->buy_price) }}
                        </td>

                        <td class="px-4 py-4">
                            {{ number_format($product->selling_price) }}
                        </td>

                        <td class="px-4 py-4 font-bold">
                            {{ $product->stock }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $product->alert_stock }}
                        </td>

                        <td class="px-4 py-4">

                            @if($product->stock <= 0)

                                <span class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-xs font-bold">
                                    OUT
                                </span>

                            @elseif($product->stock <= $product->alert_stock)

                                <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-600 text-xs font-bold">
                                    LOW
                                </span>

                            @else

                                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold">
                                    OK
                                </span>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="8"
                            class="text-center py-10 text-slate-400">

                            No products found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-6 border-t border-slate-100">

            {{ $products->links() }}

        </div>

    </div>

    <!-- LOW STOCK -->

    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">

        <div class="p-6 border-b border-slate-100">

            <h2 class="text-2xl font-black text-orange-600">
                Low Stock Alerts
            </h2>

            <p class="text-slate-500 text-sm mt-1">
                Products reaching critical level
            </p>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-[700px] w-full">

                <thead class="bg-slate-100">

                    <tr>

                        <th class="px-4 py-4 text-left">
                            Product
                        </th>

                        <th class="px-4 py-4 text-left">
                            Current Stock
                        </th>

                        <th class="px-4 py-4 text-left">
                            Department
                        </th>

                        <th class="px-4 py-4 text-left">
                            Alert Level
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($lowStockProducts as $product)

                    <tr class="border-t border-slate-100">

                        <td class="px-4 py-4 font-semibold">
                            {{ $product->name }}
                        </td>

                        <td class="px-4 py-4 font-bold text-red-500">
                            {{ $product->stock }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $product->department->name ?? 'Unassigned' }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $product->alert_stock }}
                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="4"
                            class="text-center py-8 text-slate-400">

                            No low stock products

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <!-- STOCK HISTORY -->

    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">

        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <div>

                <h2 class="text-2xl font-black text-slate-900">
                    Stock History
                </h2>

                <p class="text-slate-500 text-sm mt-1">
                    Inventory stock movements
                </p>

            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('inventory.print', ['department_id' => $selectedDepartmentId, 'filter' => request('filter')]) }}"
                   target="_blank"
                   class="px-5 py-3 rounded-2xl bg-indigo-600 text-white font-bold text-center">
                    Print History
                </a>

                <a href="{{ route('inventory.print', ['type' => 'stock_in', 'department_id' => $selectedDepartmentId, 'filter' => request('filter')]) }}"
                   target="_blank"
                   class="px-5 py-3 rounded-2xl bg-emerald-600 text-white font-bold text-center">
                    Stock In
                </a>

                <a href="{{ route('inventory.print', ['type' => 'damage', 'department_id' => $selectedDepartmentId, 'filter' => request('filter')]) }}"
                   target="_blank"
                   class="px-5 py-3 rounded-2xl bg-rose-600 text-white font-bold text-center">
                    Damaged
                </a>
            </div>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-[1000px] w-full">

                <thead class="bg-slate-100">

                    <tr>

                        <th class="px-4 py-4 text-left">
                            Product
                        </th>

                        <th class="px-4 py-4 text-left">
                            Type
                        </th>

                        <th class="px-4 py-4 text-left">
                            Department
                        </th>

                        <th class="px-4 py-4 text-left">
                            Qty
                        </th>

                        <th class="px-4 py-4 text-left">
                            Before
                        </th>

                        <th class="px-4 py-4 text-left">
                            After
                        </th>

                        <th class="px-4 py-4 text-left">
                            User
                        </th>

                        <th class="px-4 py-4 text-left">
                            Date
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($stockHistory as $history)

                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition">

                        <td class="px-4 py-4 font-semibold">
                            {{ $history->product->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4">

                            <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">

                                {{ strtoupper($history->type) }}

                            </span>

                        </td>

                        <td class="px-4 py-4">
                            {{ $history->department->name ?? $history->product?->department?->name ?? 'Unassigned' }}
                        </td>

                        <td class="px-4 py-4 font-bold">
                            {{ $history->quantity }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $history->before_stock }}
                        </td>

                        <td class="px-4 py-4 font-bold text-indigo-600">
                            {{ $history->after_stock }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $history->user->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4 text-sm text-slate-500">
                            {{ $history->created_at->format('Y-m-d H:i') }}
                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="8"
                            class="text-center py-10 text-slate-400">

                            No stock history found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="p-6 border-t border-slate-100">

            {{ $stockHistory->links() }}

        </div>

    </div>

</div>

@endsection
