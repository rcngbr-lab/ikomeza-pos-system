@extends('layouts.app')

@section('content')
@php
    $periodChips = [
        'all' => 'All',
        'daily' => 'Today',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ];
    $query = request()->query();
    $printQuery = collect($query)->except('page')->all();
    $canRefund = auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    $isPersonalSales = auth()->user()->hasOperationalRole('CASHIER', 'WAITER', 'SERVER');
@endphp

<div class="dense-page">
    <div class="dense-header">
        <div>
            <p class="dense-eyebrow">Sales Control</p>
            <h1 class="dense-title">Sales History</h1>
            <p class="dense-subtitle">Receipts, status, refunds, and cashier accountability in one dense table.</p>
        </div>

        <div class="flex flex-col gap-2 sm:items-end">
            <div class="touch-scroll flex gap-1.5 overflow-x-auto pb-1">
                @foreach($periodChips as $value => $label)
                    <a
                        href="{{ route('sales.index', array_merge($query, ['filter' => $value, 'page' => null])) }}"
                        class="dense-chip {{ $filter === $value ? 'dense-chip-active' : '' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <a
                href="{{ route('sales.report.print', $printQuery) }}"
                target="_blank"
                class="dense-btn-dark w-full justify-center sm:w-auto"
            >
                {{ $isPersonalSales ? 'Print My Sales A4' : 'Print Report A4' }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error') || ($errors->any() ?? false))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">
            {{ session('error') ?: $errors->first() }}
        </div>
    @endif

    <form method="GET" action="{{ route('sales.index') }}" class="dense-toolbar">
        <select name="department_id" class="dense-select md:w-52">
            <option value="">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <select name="filter" class="dense-select md:w-40">
            <option value="all" @selected($filter === 'all')>All Time</option>
            <option value="daily" @selected($filter === 'daily')>Today</option>
            <option value="weekly" @selected($filter === 'weekly')>This Week</option>
            <option value="monthly" @selected($filter === 'monthly')>This Month</option>
            <option value="yearly" @selected($filter === 'yearly')>This Year</option>
        </select>

        <input type="search" name="search" value="{{ $search }}" placeholder="Invoice, cashier, status..." class="dense-input min-w-0 flex-1">

        <div class="grid grid-cols-2 gap-2 md:flex">
            <button class="dense-btn-dark">Apply</button>
            <a href="{{ route('sales.index') }}" class="dense-btn-soft">Reset</a>
        </div>
    </form>

    <div class="dense-stat-row xl:grid-cols-5">
        <div class="dense-stat">
            <p class="dense-stat-label">Net Sales</p>
            <p class="dense-stat-value text-emerald-600">{{ number_format($totalSales) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Gross Sales</p>
            <p class="dense-stat-value">{{ number_format($grossSales) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Refunded</p>
            <p class="dense-stat-value text-rose-600">{{ number_format($refundedSales) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Transactions</p>
            <p class="dense-stat-value">{{ number_format($totalTransactions) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Refund Txn</p>
            <p class="dense-stat-value text-amber-600">{{ number_format($refundedTransactions) }}</p>
        </div>
    </div>

    <section class="dense-card">
        <div class="dense-card-header">
            <div>
                <h2 class="text-sm font-black text-slate-950">Receipt Register</h2>
                <p class="text-xs text-slate-500">Refunded receipts stay visible with status and timestamp.</p>
            </div>
            <p class="text-xs font-bold text-slate-500">{{ $sales->total() }} records</p>
        </div>

        <div class="dense-table-wrap">
            <table class="dense-table min-w-[1050px]">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Customer</th>
                        <th>Department</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Refunded At</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @php
                            $isRefunded = (bool) $sale->is_refunded || $sale->sale_status === 'REFUNDED';
                            $statusLabel = $isRefunded ? 'REFUNDED' : ($sale->sale_status ?: 'COMPLETED');
                        @endphp
                        <tr>
                            <td>
                                <p class="font-black text-slate-950">{{ $sale->receipt_no ?? 'N/A' }}</p>
                                <p class="text-[11px] text-slate-500">#{{ $sale->id }}</p>
                            </td>
                            <td>{{ optional($sale->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                            <td>{{ $sale->customer_name ?? $sale->customer?->name ?? 'Walk-in' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($sale->items->pluck('department.name')->filter()->unique() as $departmentName)
                                        <span class="dense-badge bg-indigo-50 text-indigo-700">{{ $departmentName }}</span>
                                    @empty
                                        <span>-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-right font-black">{{ number_format($sale->grand_total) }}</td>
                            <td>
                                <span class="dense-badge {{ $isRefunded ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ str_replace('_', ' ', $statusLabel) }}
                                </span>
                            </td>
                            <td>{{ $sale->refunded_at ? $sale->refunded_at->format('Y-m-d H:i') : '-' }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('sales.print', $sale->id) }}" target="_blank" class="dense-btn-soft">Print</a>
                                    @if($isRefunded)
                                        <span class="dense-btn bg-rose-50 text-rose-700">Refunded</span>
                                    @elseif($canRefund)
                                        <form method="POST" action="{{ route('sales.refund', $sale->id) }}">
                                            @csrf
                                            <input type="hidden" name="refund_reason" value="Customer refund">
                                            <button class="dense-btn-danger" onclick="return confirm('Request refund for this receipt?')">Refund</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="dense-empty">No sales match the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            {{ $sales->onEachSide(1)->links() }}
        </div>
    </section>
</div>
@endsection
