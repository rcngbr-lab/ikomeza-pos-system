@extends('layouts.app')

@section('content')
@php
    $canApprove = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    $canReceive = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'STORE_KEEPER');
    $statusClass = function (string $status) {
        return match ($status) {
            'RECEIVED' => 'bg-emerald-100 text-emerald-700',
            'APPROVED', 'ORDERED' => 'bg-indigo-100 text-indigo-700',
            'PARTIALLY_RECEIVED' => 'bg-amber-100 text-amber-700',
            'CANCELLED' => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-100 text-slate-700',
        };
    };
@endphp

<div class="dense-page">
    <div class="dense-header">
        <div>
            <p class="dense-eyebrow">Store Management</p>
            <h1 class="dense-title">Purchases & Receiving</h1>
            <p class="dense-subtitle">Purchase register, approvals, supplier delivery verification, and physical receiving.</p>
        </div>
        <a href="{{ route('store.suppliers') }}" class="dense-btn-soft">Suppliers</a>
    </div>

    @include('store._nav')
    @include('store._filters', ['action' => route('store.purchases')])

    @if(isset($errors) && $errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    <details class="dense-card">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 text-sm font-black text-slate-950 [&::-webkit-details-marker]:hidden">
            Create Purchase
            <span class="text-[11px] font-bold text-slate-500">Stock increases only after approval and physical receiving</span>
        </summary>

        <form method="POST" action="{{ route('store.purchases.store') }}" class="grid gap-2 border-t border-slate-100 p-3 md:grid-cols-4">
            @csrf
            <select name="supplier_id" required class="dense-select">
                <option value="">Supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
            </select>

            <select name="store_id" required class="dense-select">
                <option value="">Destination store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>

            <select name="department_id" class="dense-select">
                <option value="">Auto department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>

            <select name="requisition_id" class="dense-select">
                <option value="">No requisition</option>
                @foreach($approvedRequisitions as $requisition)
                    <option value="{{ $requisition->id }}" @selected(old('requisition_id') == $requisition->id)>
                        RQ-{{ str_pad((string) $requisition->id, 6, '0', STR_PAD_LEFT) }} - {{ $requisition->product->name ?? 'Item' }}
                    </option>
                @endforeach
            </select>

            <select name="product_id" required class="dense-select md:col-span-2">
                <option value="">Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                        {{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }}
                    </option>
                @endforeach
            </select>

            <input type="number" step="0.001" min="0.001" name="quantity_ordered" value="{{ old('quantity_ordered') }}" required placeholder="Ordered qty" class="dense-input">
            <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" required placeholder="Unit cost" class="dense-input">
            <input name="invoice_number" value="{{ old('invoice_number') }}" placeholder="Supplier invoice" class="dense-input">
            <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="dense-input">
            <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="dense-input">
            <select name="payment_status" required class="dense-select">
                <option value="UNPAID">Unpaid</option>
                <option value="PARTIALLY_PAID">Partially Paid</option>
                <option value="PAID">Paid</option>
                <option value="CREDIT">Credit</option>
            </select>
            <input type="number" step="0.01" min="0" name="tax" value="{{ old('tax', 0) }}" placeholder="Tax" class="dense-input">
            <input type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" placeholder="Discount" class="dense-input">
            <textarea name="notes" rows="1" placeholder="Notes" class="dense-input h-9 md:col-span-3">{{ old('notes') }}</textarea>
            <button class="dense-btn-primary">Submit</button>
        </form>
    </details>

    <section class="dense-card">
        <div class="dense-card-header">
            <div>
                <h2 class="text-sm font-black text-slate-950">Purchase Register</h2>
                <p class="text-xs text-slate-500">Requester, approver, receiver, ordered vs received, damage, and supplier status.</p>
            </div>
            <p class="text-xs font-bold text-slate-500">{{ $purchases->total() }} records</p>
        </div>

        <div class="dense-table-wrap">
            <table class="dense-table min-w-[1180px]">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th>Store</th>
                        <th class="text-right">Ordered</th>
                        <th class="text-right">Received</th>
                        <th class="text-right">Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        @php
                            $ordered = $purchase->items->sum(fn ($item) => (float) $item->quantity_ordered);
                            $received = $purchase->items->sum(fn ($item) => (float) $item->quantity_received);
                            $damaged = $purchase->items->sum(fn ($item) => (float) $item->damaged_quantity);
                        @endphp
                        <tr>
                            <td>
                                <p class="font-black text-slate-950">{{ $purchase->supplier->company_name ?? 'Supplier not set' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $purchase->invoice_number ?: 'No invoice' }}</p>
                            </td>
                            <td class="font-black">{{ $purchase->purchase_number }}</td>
                            <td>{{ optional($purchase->purchase_date)->format('Y-m-d') }}</td>
                            <td>{{ $purchase->store->name ?? '-' }}</td>
                            <td class="text-right font-black">{{ number_format($ordered, 2) }}</td>
                            <td class="text-right">
                                <span class="font-black text-emerald-600">{{ number_format($received, 2) }}</span>
                                @if($damaged > 0)
                                    <span class="text-rose-600">/ {{ number_format($damaged, 2) }} dmg</span>
                                @endif
                            </td>
                            <td class="text-right font-black">{{ number_format((float) $purchase->total_amount) }}</td>
                            <td><span class="dense-badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $purchase->payment_status) }}</span></td>
                            <td><span class="dense-badge {{ $statusClass($purchase->status) }}">{{ str_replace('_', ' ', $purchase->status) }}</span></td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    @if($canApprove && in_array($purchase->status, ['DRAFT', 'PENDING_APPROVAL'], true))
                                        <form method="POST" action="{{ route('store.purchases.approve', $purchase) }}">
                                            @csrf
                                            <button class="dense-btn bg-emerald-600 text-white hover:bg-emerald-700">Approve</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="10" class="bg-slate-50">
                                <details class="rounded-lg border border-slate-200 bg-white">
                                    <summary class="cursor-pointer list-none px-3 py-2 text-xs font-black text-slate-700 [&::-webkit-details-marker]:hidden">
                                        Expand {{ $purchase->purchase_number }} lines, responsibility chain, and receiving
                                    </summary>

                                    <div class="border-t border-slate-100 p-3">
                                        <div class="mb-2 grid gap-2 text-[11px] md:grid-cols-3">
                                            <p><span class="font-black text-slate-500">Purchased by:</span> {{ $purchase->purchaser->name ?? '-' }}</p>
                                            <p><span class="font-black text-slate-500">Approved by:</span> {{ $purchase->approver->name ?? '-' }}</p>
                                            <p><span class="font-black text-slate-500">Received by:</span> {{ $purchase->receiver->name ?? '-' }}</p>
                                        </div>

                                        <div class="overflow-x-auto">
                                            <table class="w-full min-w-[760px] text-xs">
                                                <thead class="bg-slate-100 text-left text-[10px] uppercase tracking-wide text-slate-500">
                                                    <tr>
                                                        <th class="px-2 py-1.5">Product</th>
                                                        <th class="px-2 py-1.5 text-right">Ordered</th>
                                                        <th class="px-2 py-1.5 text-right">Received</th>
                                                        <th class="px-2 py-1.5 text-right">Damaged</th>
                                                        <th class="px-2 py-1.5 text-right">Unit Cost</th>
                                                        <th class="px-2 py-1.5 text-right">Diff</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($purchase->items as $item)
                                                        <tr class="border-t border-slate-100">
                                                            <td class="px-2 py-1.5 font-black">{{ $item->product->name ?? '-' }}</td>
                                                            <td class="px-2 py-1.5 text-right">{{ number_format((float) $item->quantity_ordered, 2) }}</td>
                                                            <td class="px-2 py-1.5 text-right">{{ number_format((float) $item->quantity_received, 2) }}</td>
                                                            <td class="px-2 py-1.5 text-right">{{ number_format((float) $item->damaged_quantity, 2) }}</td>
                                                            <td class="px-2 py-1.5 text-right">{{ number_format((float) $item->unit_cost) }}</td>
                                                            <td class="px-2 py-1.5 text-right font-black {{ ((float) $item->quantity_received - (float) $item->quantity_ordered) < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                                {{ number_format((float) $item->quantity_received - (float) $item->quantity_ordered, 2) }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        @if($canReceive && in_array($purchase->status, ['APPROVED', 'ORDERED', 'PARTIALLY_RECEIVED'], true))
                                            <form method="POST" action="{{ route('store.purchases.receive', $purchase) }}" class="mt-3 grid gap-2 rounded-lg bg-slate-50 p-2 md:grid-cols-5">
                                                @csrf
                                                @foreach($purchase->items as $item)
                                                    <input type="number" step="0.001" min="0" name="received[{{ $item->id }}]" placeholder="Received {{ $item->product->name ?? 'item' }}" class="dense-input bg-white">
                                                    <input type="number" step="0.001" min="0" name="damaged[{{ $item->id }}]" placeholder="Damaged" class="dense-input bg-white">
                                                    <input name="batch_number[{{ $item->id }}]" placeholder="Batch" class="dense-input bg-white">
                                                    <input type="date" name="expiry_date[{{ $item->id }}]" class="dense-input bg-white">
                                                @endforeach
                                                <input name="receiving_note" placeholder="Receiving note" class="dense-input bg-white">
                                                <button class="dense-btn-dark md:col-span-5">Confirm Physical Receiving</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="dense-empty">No purchases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            {{ $purchases->onEachSide(1)->links() }}
        </div>
    </section>
</div>
@endsection
