@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Inventory Control</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Physical Stock Counts</h1>
        <p class="mt-1 text-sm text-slate-500">Submit counted quantities for approval before stock is adjusted.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ session('error') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-black text-slate-950">New Count</h2>
        <form method="POST" action="{{ route('store.stock-counts.store') }}" class="mt-4 grid gap-3 lg:grid-cols-[1fr_1fr_160px_1fr_auto]">
            @csrf
            <select name="store_id" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="product_id" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}{{ $product->barcode ? ' - ' . $product->barcode : '' }}</option>
                @endforeach
            </select>
            <input name="counted_quantity" type="number" step="0.001" min="0" placeholder="Counted qty" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="reason" placeholder="Reason / note" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <button class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-black text-white">Submit</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Count</th>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">System</th>
                        <th class="px-4 py-3">Counted</th>
                        <th class="px-4 py-3">Variance</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($counts as $count)
                        @php $item = $count->items->first(); @endphp
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $count->count_number }}</td>
                            <td class="px-4 py-3">{{ $item?->product?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ number_format((float) ($item?->system_quantity ?? 0), 3) }}</td>
                            <td class="px-4 py-3">{{ number_format((float) ($item?->counted_quantity ?? 0), 3) }}</td>
                            <td class="px-4 py-3 font-black {{ (float) ($item?->variance_quantity ?? 0) < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ number_format((float) ($item?->variance_quantity ?? 0), 3) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $count->status === \App\Models\StockCount::STATUS_SUBMITTED ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ str($count->status)->headline() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($count->status === \App\Models\StockCount::STATUS_SUBMITTED && auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'))
                                    <form method="POST" action="{{ route('store.stock-counts.approve', $count) }}">
                                        @csrf
                                        <button class="h-9 rounded-lg bg-emerald-600 px-3 text-xs font-black text-white">Approve</button>
                                    </form>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm font-semibold text-slate-500">No stock counts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $counts->links() }}</div>
    </section>
</div>

@endsection
