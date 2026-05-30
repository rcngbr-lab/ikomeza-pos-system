@extends('layouts.app')

@section('content')
@php
    $canApprove = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    $canReceive = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'STORE_KEEPER');
@endphp

<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950 md:text-4xl">Purchases & Receiving</h1>
            <p class="mt-2 text-sm text-slate-500">Create purchases, approve them, and receive physical supplier deliveries into a store.</p>
        </div>
        <a href="{{ route('store.suppliers') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">Suppliers</a>
    </div>

    @include('store._nav')
    @include('store._filters', ['action' => route('store.purchases')])

    @if(isset($errors) && $errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-black text-slate-950">Create Purchase</h2>
            <p class="text-sm text-slate-500">This does not increase stock. Stock increases after approval and physical receiving.</p>
        </div>

        <form method="POST" action="{{ route('store.purchases.store') }}" class="mt-4 grid gap-3 lg:grid-cols-4">
            @csrf
            <select name="supplier_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Select supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
            </select>

            <select name="store_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Destination store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>

            <select name="department_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Auto from product</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>

            <select name="requisition_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">No requisition linked</option>
                @foreach($approvedRequisitions as $requisition)
                    <option value="{{ $requisition->id }}" @selected(old('requisition_id') == $requisition->id)>
                        RQ-{{ str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT) }} - {{ $requisition->product->name ?? 'Item' }}
                    </option>
                @endforeach
            </select>

            <select name="product_id" required class="rounded-xl border-slate-200 bg-slate-50 text-sm lg:col-span-2">
                <option value="">Select product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                        {{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }}
                    </option>
                @endforeach
            </select>

            <input type="number" step="0.001" min="0.001" name="quantity_ordered" value="{{ old('quantity_ordered') }}" required placeholder="Quantity ordered" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" required placeholder="Unit cost" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="invoice_number" value="{{ old('invoice_number') }}" placeholder="Supplier invoice" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <select name="payment_status" required class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="UNPAID">Unpaid</option>
                <option value="PARTIALLY_PAID">Partially Paid</option>
                <option value="PAID">Paid</option>
                <option value="CREDIT">Credit</option>
            </select>
            <input type="number" step="0.01" min="0" name="tax" value="{{ old('tax', 0) }}" placeholder="Tax" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" placeholder="Discount" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <textarea name="notes" rows="2" placeholder="Notes" class="rounded-xl border-slate-200 bg-slate-50 text-sm lg:col-span-3">{{ old('notes') }}</textarea>
            <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white">Submit for Approval</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-xl font-black text-slate-950">Purchase Workflow</h2>
        </div>

        <div class="space-y-4 p-5">
            @forelse($purchases as $purchase)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-lg font-black text-slate-950">{{ $purchase->purchase_number }}</p>
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700">{{ str_replace('_', ' ', $purchase->status) }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ str_replace('_', ' ', $purchase->payment_status) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ $purchase->supplier->company_name ?? 'Supplier not set' }} -> {{ $purchase->store->name ?? 'Store not set' }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Requested by {{ $purchase->purchaser->name ?? '-' }}
                                @if($purchase->approver)
                                    | Approved by {{ $purchase->approver->name }}
                                @endif
                                @if($purchase->receiver)
                                    | Received by {{ $purchase->receiver->name }}
                                @endif
                            </p>
                        </div>

                        <div class="text-left xl:text-right">
                            <p class="text-sm font-semibold text-slate-500">Total Amount</p>
                            <p class="text-2xl font-black text-slate-950">{{ number_format((float) $purchase->total_amount) }} RWF</p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Ordered</th>
                                    <th class="px-4 py-3">Received</th>
                                    <th class="px-4 py-3">Damaged</th>
                                    <th class="px-4 py-3">Unit Cost</th>
                                    <th class="px-4 py-3">Difference</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($purchase->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 font-bold text-slate-900">{{ $item->product->name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ number_format((float) $item->quantity_ordered, 2) }}</td>
                                        <td class="px-4 py-3">{{ number_format((float) $item->quantity_received, 2) }}</td>
                                        <td class="px-4 py-3">{{ number_format((float) $item->damaged_quantity, 2) }}</td>
                                        <td class="px-4 py-3">{{ number_format((float) $item->unit_cost) }}</td>
                                        <td class="px-4 py-3 font-black {{ ((float) $item->quantity_received - (float) $item->quantity_ordered) < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ number_format((float) $item->quantity_received - (float) $item->quantity_ordered, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                        @if($canApprove && in_array($purchase->status, ['DRAFT', 'PENDING_APPROVAL'], true))
                            <form method="POST" action="{{ route('store.purchases.approve', $purchase) }}">
                                @csrf
                                <button class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Approve Purchase</button>
                            </form>
                        @endif

                        @if($canReceive && in_array($purchase->status, ['APPROVED', 'ORDERED', 'PARTIALLY_RECEIVED'], true))
                            <form method="POST" action="{{ route('store.purchases.receive', $purchase) }}" class="grid flex-1 gap-3 rounded-2xl bg-slate-50 p-3 md:grid-cols-5">
                                @csrf
                                @foreach($purchase->items as $item)
                                    <input type="number" step="0.001" min="0" name="received[{{ $item->id }}]" placeholder="Received {{ $item->product->name ?? 'item' }}" class="rounded-xl border-slate-200 bg-white text-sm">
                                    <input type="number" step="0.001" min="0" name="damaged[{{ $item->id }}]" placeholder="Damaged" class="rounded-xl border-slate-200 bg-white text-sm">
                                    <input name="batch_number[{{ $item->id }}]" placeholder="Batch" class="rounded-xl border-slate-200 bg-white text-sm">
                                    <input type="date" name="expiry_date[{{ $item->id }}]" class="rounded-xl border-slate-200 bg-white text-sm">
                                @endforeach
                                <input name="receiving_note" placeholder="Receiving note" class="rounded-xl border-slate-200 bg-white text-sm">
                                <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white md:col-span-5">Confirm Physical Receiving</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm font-semibold text-slate-400">
                    No purchases found.
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 p-5">{{ $purchases->links() }}</div>
    </section>
</div>
@endsection
