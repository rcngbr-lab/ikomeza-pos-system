@extends('layouts.app')

@section('content')
@php $canApprove = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'); @endphp

<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div>
        <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950 md:text-4xl">Returns</h1>
        <p class="mt-2 text-sm text-slate-500">Track department returns, supplier returns, refund returns, and unused stock returns.</p>
    </div>

    @include('store._nav')
    @include('store._filters', ['action' => route('store.returns')])

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-black text-slate-950">Record Return</h2>
        <form method="POST" action="{{ route('store.returns.store') }}" class="mt-4 grid gap-3 md:grid-cols-5">
            @csrf
            <select name="return_type" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="DEPARTMENT_RETURN">Department return</option>
                <option value="SUPPLIER_RETURN">Supplier return</option>
                <option value="CUSTOMER_REFUND_RETURN">Customer refund return</option>
                <option value="UNUSED_STOCK_RETURN">Unused stock return</option>
                <option value="DAMAGED_ITEM_RETURN">Damaged item return</option>
            </select>
            <select name="product_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
            <select name="from_store_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">From store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="to_store_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">To store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <input type="number" step="0.001" min="0.001" name="quantity" required placeholder="Quantity" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <select name="supplier_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm md:col-span-2">
                <option value="">Supplier if applicable</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                @endforeach
            </select>
            <input name="reason" placeholder="Reason" class="rounded-xl border-slate-200 bg-slate-50 text-sm md:col-span-2">
            <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white">Record Return</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-5 py-4">Reference</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4">Product</th>
                        <th class="px-5 py-4">Route</th>
                        <th class="px-5 py-4">Qty</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Date</th>
                        <th class="px-5 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($returns as $return)
                        <tr class="text-sm">
                            <td class="px-5 py-4 font-black">{{ $return->return_number }}</td>
                            <td class="px-5 py-4">{{ str_replace('_', ' ', $return->return_type) }}</td>
                            <td class="px-5 py-4">{{ $return->product->name ?? '-' }}</td>
                            <td class="px-5 py-4">{{ $return->fromStore->name ?? '-' }} -> {{ $return->toStore->name ?? $return->supplier->company_name ?? '-' }}</td>
                            <td class="px-5 py-4 font-bold">{{ number_format((float) $return->quantity, 2) }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">{{ str_replace('_', ' ', $return->status) }}</span></td>
                            <td class="px-5 py-4 text-slate-500">{{ $return->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-5 py-4">
                                @if($canApprove && $return->status === 'PENDING_APPROVAL')
                                    <form method="POST" action="{{ route('store.returns.approve', $return) }}">
                                        @csrf
                                        <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white">Approve</button>
                                    </form>
                                @else
                                    <span class="text-xs font-bold text-slate-400">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-sm font-semibold text-slate-400">No returns found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-5">{{ $returns->links() }}</div>
    </section>
</div>
@endsection
