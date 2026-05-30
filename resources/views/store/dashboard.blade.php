@extends('layouts.app')

@section('content')
<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 md:text-4xl">Store Control Center</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                Supplier receiving, stock custody, store issues, damages, returns, and inventory valuation.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('store.purchases') }}" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-200">
                New Purchase
            </a>
            <a href="{{ route('store.movements') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">
                Movements
            </a>
        </div>
    </div>

    @include('store._nav')

    @include('store._filters', ['action' => route('store.dashboard')])

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Total Store Value</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['total_value']) }} RWF</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Low Stock Items</p>
            <p class="mt-3 text-3xl font-black text-amber-600">{{ number_format($summary['low_stock']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Pending Requisitions</p>
            <p class="mt-3 text-3xl font-black text-indigo-600">{{ number_format($summary['pending_requisitions']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Pending Deliveries</p>
            <p class="mt-3 text-3xl font-black text-rose-600">{{ number_format($summary['pending_deliveries']) }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Out of Stock</p>
            <p class="mt-3 text-3xl font-black text-rose-600">{{ number_format($summary['out_of_stock']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Stock Received - {{ $storeDateLabel ?? 'All Time' }}</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">{{ number_format($summary['received_today']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Stock Issued - {{ $storeDateLabel ?? 'All Time' }}</p>
            <p class="mt-3 text-3xl font-black text-blue-600">{{ number_format($summary['issued_today']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Damaged / Returned - {{ $storeDateLabel ?? 'All Time' }}</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['damaged_stock']) }} / {{ number_format($summary['returned_stock']) }}</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Store Balances</h2>
                    <p class="mt-1 text-sm text-slate-500">Independent stock by Main, Kitchen, and Bar Store.</p>
                </div>
                <a href="{{ route('store.movements') }}" class="text-sm font-black text-indigo-600">History</a>
            </div>

            <div class="grid gap-3 p-5 md:grid-cols-3">
                @foreach($stores as $store)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">{{ $store->type }}</p>
                        <p class="mt-2 font-black text-slate-950">{{ $store->name }}</p>
                        <p class="mt-3 text-2xl font-black text-indigo-600">{{ number_format((float) ($storeValues[$store->id] ?? 0)) }} RWF</p>
                    </div>
                @endforeach
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead class="bg-slate-950 text-left text-xs uppercase tracking-wider text-white">
                        <tr>
                            <th class="px-5 py-4">Product</th>
                            <th class="px-5 py-4">Store</th>
                            <th class="px-5 py-4">Department</th>
                            <th class="px-5 py-4">Quantity</th>
                            <th class="px-5 py-4">Alert</th>
                            <th class="px-5 py-4">Value</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($storeStocks as $stock)
                            <tr class="text-sm">
                                <td class="px-5 py-4">
                                    <p class="font-black text-slate-950">{{ $stock->product->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $stock->product->product_code ?? $stock->product->barcode ?? '' }}</p>
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-700">{{ $stock->store->name ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $stock->department->name ?? $stock->product?->department?->name ?? '-' }}</td>
                                <td class="px-5 py-4 font-black text-slate-950">{{ number_format((float) $stock->quantity, 2) }}</td>
                                <td class="px-5 py-4">{{ number_format((float) $stock->alert_stock, 2) }}</td>
                                <td class="px-5 py-4 font-black">{{ number_format((float) $stock->total_value) }}</td>
                                <td class="px-5 py-4">
                                    @if($stock->quantity <= 0)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">OUT</span>
                                    @elseif($stock->quantity <= $stock->alert_stock)
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">LOW</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm font-semibold text-slate-400">
                                    No store stock records match these filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 p-5">
                {{ $storeStocks->links() }}
            </div>
        </section>

        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black text-slate-950">Pending Control Work</h2>
                <div class="mt-4 space-y-3">
                    @forelse($pendingPurchases as $purchase)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-950">{{ $purchase->purchase_number }}</p>
                                    <p class="text-sm text-slate-500">{{ $purchase->supplier->company_name ?? 'Supplier not set' }}</p>
                                </div>
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">{{ str_replace('_', ' ', $purchase->status) }}</span>
                            </div>
                            <p class="mt-3 text-sm font-bold text-slate-700">{{ number_format((float) $purchase->total_amount) }} RWF</p>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-400">
                            No pending purchase or receiving work.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black text-slate-950">Recent Movements</h2>
                <div class="mt-4 space-y-3">
                    @forelse($recentMovements as $movement)
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                            <div class="min-w-0">
                                <p class="truncate font-black text-slate-950">{{ $movement->product->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $movement->fromStore->name ?? 'In' }} -> {{ $movement->toStore->name ?? 'Out' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-slate-950">{{ number_format((float) $movement->quantity, 2) }}</p>
                                <p class="text-xs font-bold text-slate-500">{{ $movement->type }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-400">
                            No recent stock movement.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
