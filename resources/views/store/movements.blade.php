@extends('layouts.app')

@section('content')
<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950 md:text-4xl">Stock Movements</h1>
            <p class="mt-2 text-sm text-slate-500">Every stock in, stock out, sale, refund, transfer, damage, and receiving action.</p>
        </div>
        <button onclick="window.print()" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">Print</button>
    </div>

    @include('store._nav')
    @include('store._filters', ['action' => route('store.movements')])

    <form method="GET" action="{{ route('store.movements') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-4">
            @foreach(request()->except(['movement_type', 'per_page']) as $name => $value)
                @if(is_scalar($value))
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <select name="movement_type" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">All movement types</option>
                @foreach(['PURCHASE_RECEIVED','STORE_TRANSFER','STORE_ISSUE','SALE','REFUND','DAMAGE','RETURN','STOCK_IN','STOCK_OUT','STOCK_ADJUSTMENT'] as $type)
                    <option value="{{ $type }}" @selected(request('movement_type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                @endforeach
            </select>
            <select name="per_page" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="20" @selected((int) request('per_page', 20) === 20)>20 per page</option>
                <option value="50" @selected((int) request('per_page') === 50)>50 per page</option>
                <option value="100" @selected((int) request('per_page') === 100)>100 per page</option>
            </select>
            <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white">Filter Movements</button>
        </div>
    </form>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px]">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-5 py-4">Date</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4">Product</th>
                        <th class="px-5 py-4">Store Route</th>
                        <th class="px-5 py-4">Qty</th>
                        <th class="px-5 py-4">Before</th>
                        <th class="px-5 py-4">After</th>
                        <th class="px-5 py-4">User</th>
                        <th class="px-5 py-4">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $movement)
                        <tr class="text-sm">
                            <td class="px-5 py-4 text-slate-500">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">
                                    {{ str_replace('_', ' ', $movement->movement_type ?: $movement->type) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-black text-slate-950">{{ $movement->product->name ?? '-' }}</td>
                            <td class="px-5 py-4">{{ $movement->fromStore->name ?? '-' }} -> {{ $movement->toStore->name ?? '-' }}</td>
                            <td class="px-5 py-4 font-black">{{ number_format((float) $movement->quantity, 2) }}</td>
                            <td class="px-5 py-4">{{ number_format((float) ($movement->quantity_before ?? $movement->before_stock), 2) }}</td>
                            <td class="px-5 py-4">{{ number_format((float) ($movement->quantity_after ?? $movement->after_stock), 2) }}</td>
                            <td class="px-5 py-4">{{ $movement->user->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $movement->notes ?: $movement->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-sm font-semibold text-slate-400">No stock movements match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-5">{{ $movements->links() }}</div>
    </section>
</div>
@endsection
