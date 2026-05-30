@extends('layouts.app')

@section('content')
@php $canApprove = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'); @endphp

<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div>
        <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950 md:text-4xl">Damage Management</h1>
        <p class="mt-2 text-sm text-slate-500">Record breakage, expiry, spoilage, and operational losses for approval.</p>
    </div>

    @include('store._nav')
    @include('store._filters', ['action' => route('store.damages')])

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-black text-slate-950">Record Damage</h2>
        <form method="POST" action="{{ route('store.damages.store') }}" class="mt-4 grid gap-3 md:grid-cols-5">
            @csrf
            <select name="store_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="product_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <input type="number" step="0.001" min="0.001" name="quantity" required placeholder="Quantity" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="reason" required placeholder="Reason" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <button class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-black text-white">Record Damage</button>
            <input name="notes" placeholder="Notes" class="rounded-xl border-slate-200 bg-slate-50 text-sm md:col-span-5">
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-5 py-4">Reference</th>
                        <th class="px-5 py-4">Product</th>
                        <th class="px-5 py-4">Store</th>
                        <th class="px-5 py-4">Qty</th>
                        <th class="px-5 py-4">Reason</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($damages as $damage)
                        <tr class="text-sm">
                            <td class="px-5 py-4 font-black">{{ $damage->damage_number }}</td>
                            <td class="px-5 py-4">{{ $damage->product->name ?? '-' }}</td>
                            <td class="px-5 py-4">{{ $damage->store->name ?? '-' }}</td>
                            <td class="px-5 py-4 font-bold">{{ number_format((float) $damage->quantity, 2) }}</td>
                            <td class="px-5 py-4">{{ $damage->reason }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">{{ str_replace('_', ' ', $damage->status) }}</span></td>
                            <td class="px-5 py-4">
                                @if($canApprove && $damage->status === 'PENDING_APPROVAL')
                                    <form method="POST" action="{{ route('store.damages.approve', $damage) }}">
                                        @csrf
                                        <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white">Approve</button>
                                    </form>
                                @else
                                    <span class="text-xs font-bold text-slate-400">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm font-semibold text-slate-400">No damage records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-5">{{ $damages->links() }}</div>
    </section>
</div>
@endsection
