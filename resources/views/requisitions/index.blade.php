@extends('layouts.app')

@php
    use App\Models\StockRequisition;

    $types = [
        StockRequisition::TYPE_STOCK_IN => 'Stock In',
        StockRequisition::TYPE_DAMAGED => 'Damaged Stock',
        StockRequisition::TYPE_STOCK_OUT => 'Stock Out',
    ];

    $statuses = [
        StockRequisition::STATUS_PENDING => 'Pending',
        StockRequisition::STATUS_APPROVED => 'Approved',
        StockRequisition::STATUS_REJECTED => 'Rejected',
    ];
@endphp

@section('content')
<div class="min-h-screen space-y-6 bg-slate-100 p-4 pb-32 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">
                Stock request and approval
            </p>
            <h1 class="mt-2 text-4xl font-black text-slate-950">
                Requisitions
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Staff submit stock requests, managers approve, and inventory changes are recorded automatically.
            </p>
        </div>

        @if($canApprove)
            <a
                href="{{ route('inventory.index') }}"
                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm"
            >
                Inventory
            </a>
        @endif
    </div>

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

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Pending Approval</p>
            <p class="mt-3 text-4xl font-black text-amber-500">{{ $summary['pending'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Approved</p>
            <p class="mt-3 text-4xl font-black text-emerald-600">{{ $summary['approved'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Rejected</p>
            <p class="mt-3 text-4xl font-black text-rose-600">{{ $summary['rejected'] }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Requested Units</p>
            <p class="mt-3 text-4xl font-black text-indigo-600">{{ number_format($summary['quantity']) }}</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[420px_1fr]">
        <form
            method="POST"
            action="{{ route('requisitions.store') }}"
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf

            <h2 class="text-2xl font-black text-slate-950">
                New Requisition
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Request stock in, stock out, or damaged stock approval.
            </p>

            <div class="mt-5 space-y-4">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">Product</span>
                    <select name="product_id" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-semibold">
                        <option value="">Select product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((int) old('product_id') === (int) $product->id)>
                                {{ $product->name }} - {{ $product->department->name ?? 'Unassigned' }} - Stock {{ $product->stock }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">Request Type</span>
                    <select name="type" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-semibold">
                        @foreach($types as $type => $label)
                            <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">Quantity</span>
                    <input
                        type="number"
                        min="1"
                        name="quantity"
                        value="{{ old('quantity') }}"
                        required
                        class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm font-semibold"
                        placeholder="Enter units"
                    >
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-wide text-slate-500">Reason</span>
                    <textarea
                        name="reason"
                        rows="4"
                        class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm"
                        placeholder="Supplier, damage reason, missing stock note, or operational explanation"
                    >{{ old('reason') }}</textarea>
                </label>
            </div>

            <button class="mt-5 w-full rounded-2xl bg-indigo-600 px-5 py-4 text-sm font-black text-white shadow-lg shadow-indigo-900/20">
                Submit For Approval
            </button>
        </form>

        <div class="space-y-5">
            <form method="GET" action="{{ route('requisitions.index') }}" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-4">
                    <select name="department_id" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-semibold">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-semibold">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status => $label)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="type" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-semibold">
                        <option value="">All Types</option>
                        @foreach($types as $type => $label)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">
                        Filter
                    </button>
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="text-2xl font-black text-slate-950">
                        Approval Queue
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Pending requests are not applied to stock until approved.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px]">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Request</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Product</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Department</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Requester</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Type</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Qty</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Status</th>
                                <th class="px-4 py-4 text-left text-xs font-black uppercase tracking-wide">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requisitions as $requisition)
                                <tr class="border-t border-slate-100 align-top hover:bg-slate-50">
                                    <td class="px-4 py-4">
                                        <p class="font-black text-slate-950">#{{ $requisition->id }}</p>
                                        <p class="text-xs text-slate-500">{{ $requisition->created_at->format('Y-m-d H:i') }}</p>
                                        @if($requisition->reason)
                                            <p class="mt-2 max-w-[240px] text-xs text-slate-500">{{ $requisition->reason }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 font-bold text-slate-900">
                                        {{ $requisition->product->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ ($requisition->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                            {{ $requisition->department->name ?? 'Unassigned' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-900">{{ $requisition->requester->name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $requisition->requester?->roleLabel() }}</p>
                                    </td>
                                    <td class="px-4 py-4 font-bold text-slate-700">
                                        {{ $requisition->typeLabel() }}
                                    </td>
                                    <td class="px-4 py-4 text-lg font-black text-slate-950">
                                        {{ number_format($requisition->quantity) }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <span @class([
                                            'rounded-full px-3 py-1 text-xs font-black',
                                            'bg-amber-100 text-amber-700' => $requisition->status === StockRequisition::STATUS_PENDING,
                                            'bg-emerald-100 text-emerald-700' => $requisition->status === StockRequisition::STATUS_APPROVED,
                                            'bg-rose-100 text-rose-700' => $requisition->status === StockRequisition::STATUS_REJECTED,
                                        ])">
                                            {{ $requisition->statusLabel() }}
                                        </span>
                                        @if($requisition->approver)
                                            <p class="mt-2 text-xs text-slate-500">
                                                by {{ $requisition->approver->name }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($canApprove && $requisition->isPending())
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('requisitions.approve', $requisition) }}">
                                                    @csrf
                                                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('requisitions.reject', $requisition) }}">
                                                    @csrf
                                                    <button class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-black text-white">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs font-semibold text-slate-400">No action</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-sm font-semibold text-slate-400">
                                        No requisitions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-5">
                    {{ $requisitions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
