@extends('layouts.app')

@section('content')
@php $canApprove = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'); @endphp

<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div>
        <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950 md:text-4xl">Store Issues & Transfers</h1>
        <p class="mt-2 text-sm text-slate-500">Move approved stock from Main Store into Kitchen Store or Bar Store.</p>
    </div>

    @include('store._nav')

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-black text-slate-950">Request Store Issue</h2>
        <form method="POST" action="{{ route('store.issues.store') }}" class="mt-4 grid gap-3 md:grid-cols-5">
            @csrf
            <select name="from_store_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">From store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="to_store_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">To store</option>
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
            <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white">Request Issue</button>
            <input name="notes" placeholder="Notes" class="rounded-xl border-slate-200 bg-slate-50 text-sm md:col-span-5">
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-xl font-black text-slate-950">Issue History</h2>
        </div>

        <div class="grid gap-4 p-5 lg:grid-cols-2">
            @forelse($issues as $issue)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-slate-950">{{ $issue->issue_number }}</p>
                            <p class="text-sm text-slate-500">{{ $issue->fromStore->name ?? '-' }} -> {{ $issue->toStore->name ?? '-' }}</p>
                        </div>
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">{{ str_replace('_', ' ', $issue->status) }}</span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        @foreach($issue->items as $item)
                            <div class="flex justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                <span class="font-bold text-slate-800">{{ $item->product->name ?? '-' }}</span>
                                <span>{{ number_format((float) $item->quantity_requested, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if($canApprove && $issue->status === 'PENDING_APPROVAL')
                        <form method="POST" action="{{ route('store.issues.approve', $issue) }}" class="mt-4">
                            @csrf
                            <button class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Approve & Transfer</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-400 lg:col-span-2">
                    No store issues found.
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 p-5">{{ $issues->links() }}</div>
    </section>
</div>
@endsection
