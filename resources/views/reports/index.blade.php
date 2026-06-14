@extends('layouts.app')

@section('content')
@php
    $paymentRows = [
        ['method' => 'Cash', 'amount' => $cashSales],
        ['method' => 'MOMO', 'amount' => $momoSales],
        ['method' => 'Airtel', 'amount' => $airtelSales],
        ['method' => 'VISA', 'amount' => $visaSales],
        ['method' => 'Mastercard', 'amount' => $masterSales],
        ['method' => 'Bank', 'amount' => $bankSales],
    ];
@endphp

<style>
    .print-report { display: none; }
    @media print {
        body * { visibility: hidden !important; }
        .print-report, .print-report * { visibility: visible !important; }
        .print-report {
            display: block !important;
            position: absolute;
            inset: 0;
            width: 100%;
            padding: 16mm;
            background: white;
            color: #0f172a;
            font-family: Arial, sans-serif;
            font-size: 11px;
        }
        .no-print { display: none !important; }
        .print-report table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .print-report th, .print-report td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
        .print-report th { background: #0f172a; color: white; }
        .print-number { text-align: right !important; }
    }
</style>

<div class="dense-page no-print">
    <div class="dense-header">
        <div>
            <p class="dense-eyebrow">Business Intelligence</p>
            <h1 class="dense-title">Reports</h1>
            <p class="dense-subtitle">Table-first sales, payment, department, and product analytics.</p>
        </div>

        <button type="button" onclick="window.print()" class="dense-btn-dark">Print A4</button>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="dense-toolbar">
        <select name="department_id" class="dense-select md:w-52">
            <option value="">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <select name="filter" class="dense-select md:w-40">
            <option value="daily" @selected($filter === 'daily')>Today</option>
            <option value="weekly" @selected($filter === 'weekly')>Weekly</option>
            <option value="monthly" @selected($filter === 'monthly')>Monthly</option>
            <option value="yearly" @selected($filter === 'yearly')>Yearly</option>
            <option value="range" @selected($filter === 'range')>Custom</option>
        </select>

        <input type="date" name="start_date" value="{{ request('start_date') }}" class="dense-input md:w-40">
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="dense-input md:w-40">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Receipt, cashier, payment..." class="dense-input min-w-0 flex-1">

        <div class="grid grid-cols-2 gap-2 md:flex">
            <button class="dense-btn-dark">Apply</button>
            <a href="{{ route('reports.index') }}" class="dense-btn-soft">Reset</a>
        </div>
    </form>

    <div class="dense-stat-row xl:grid-cols-6">
        <div class="dense-stat">
            <p class="dense-stat-label">Net Revenue</p>
            <p class="dense-stat-value text-emerald-600">{{ number_format($totalRevenue) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Gross Revenue</p>
            <p class="dense-stat-value">{{ number_format($grossRevenue) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Refunds</p>
            <p class="dense-stat-value text-rose-600">{{ number_format($refundedRevenue) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Profit</p>
            <p class="dense-stat-value text-indigo-600">{{ number_format($profit) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Transactions</p>
            <p class="dense-stat-value">{{ number_format($totalTransactions) }}</p>
        </div>
        <div class="dense-stat">
            <p class="dense-stat-label">Refunded Txn</p>
            <p class="dense-stat-value text-amber-600">{{ number_format($refundedTransactions) }}</p>
        </div>
    </div>

    <section class="dense-card">
        <div class="dense-card-header">
            <div>
                <h2 class="text-sm font-black text-slate-950">Sales Transactions</h2>
                <p class="text-xs text-slate-500">{{ $reportPeriod }} / {{ $reportDepartment }}</p>
            </div>
            <p class="text-xs font-bold text-slate-500">{{ $sales->total() }} records</p>
        </div>

        <div class="dense-table-wrap">
            <table class="dense-table min-w-[960px]">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Cashier</th>
                        <th>Payment</th>
                        <th>Department</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @php
                            $isRefunded = (bool) $sale->is_refunded || $sale->sale_status === 'REFUNDED';
                        @endphp
                        <tr>
                            <td class="font-black text-slate-950">{{ $sale->receipt_no }}</td>
                            <td>{{ optional($sale->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $sale->user->name ?? 'N/A' }}</td>
                            <td><span class="dense-badge bg-slate-100 text-slate-700">{{ $sale->payment_method }}</span></td>
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
                                    {{ $isRefunded ? 'REFUNDED' : ($sale->sale_status ?: 'COMPLETED') }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('sales.receipt', $sale->id) }}" class="dense-btn-soft">Receipt</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="dense-empty">No sales found for this report filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            {{ $sales->onEachSide(1)->links() }}
        </div>
    </section>

    <div class="grid gap-3 xl:grid-cols-3">
        <section class="dense-card xl:col-span-1">
            <div class="dense-card-header">
                <h2 class="text-sm font-black text-slate-950">Payment Mix</h2>
            </div>
            <div class="dense-table-wrap">
                <table class="dense-table min-w-[360px]">
                    <thead><tr><th>Method</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @foreach($paymentRows as $row)
                            <tr>
                                <td class="font-bold">{{ $row['method'] }}</td>
                                <td class="text-right font-black">{{ number_format($row['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dense-card xl:col-span-1">
            <div class="dense-card-header">
                <h2 class="text-sm font-black text-slate-950">Departments</h2>
            </div>
            <div class="dense-table-wrap">
                <table class="dense-table min-w-[460px]">
                    <thead>
                        <tr><th>Department</th><th class="text-right">Revenue</th><th class="text-right">Profit</th><th class="text-right">Units</th></tr>
                    </thead>
                    <tbody>
                        @forelse($departmentBreakdown as $departmentMetric)
                            <tr>
                                <td class="font-black">{{ $departmentMetric->department->name ?? 'Unassigned' }}</td>
                                <td class="text-right">{{ number_format($departmentMetric->revenue) }}</td>
                                <td class="text-right font-black text-emerald-600">{{ number_format($departmentMetric->profit) }}</td>
                                <td class="text-right">{{ number_format($departmentMetric->units_sold) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="dense-empty">No department activity.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dense-card xl:col-span-1">
            <div class="dense-card-header">
                <h2 class="text-sm font-black text-slate-950">Top Products</h2>
            </div>
            <div class="dense-table-wrap">
                <table class="dense-table min-w-[420px]">
                    <thead><tr><th>Product</th><th class="text-right">Units</th><th class="text-right">Revenue</th></tr></thead>
                    <tbody>
                        @forelse($topProducts as $productMetric)
                            <tr>
                                <td class="font-black">{{ $productMetric->product->name ?? 'Unknown' }}</td>
                                <td class="text-right">{{ number_format($productMetric->units_sold) }}</td>
                                <td class="text-right font-black">{{ number_format($productMetric->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="dense-empty">No product performance yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<section class="print-report">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0f172a;padding-bottom:8px;">
        <div>
            <h1 style="font-size:22px;margin:0;">IKOMEZA POS REPORT</h1>
            <p style="margin:4px 0 0;">Generated: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
        <div style="text-align:right;">
            <strong>{{ $reportPeriod }}</strong><br>
            {{ $reportDepartment }}
        </div>
    </div>

    <table>
        <thead><tr><th>Metric</th><th class="print-number">Value</th><th>Metric</th><th class="print-number">Value</th></tr></thead>
        <tbody>
            <tr><td>Net Revenue</td><td class="print-number">{{ number_format($totalRevenue) }}</td><td>Gross Revenue</td><td class="print-number">{{ number_format($grossRevenue) }}</td></tr>
            <tr><td>Refunds</td><td class="print-number">{{ number_format($refundedRevenue) }}</td><td>Profit</td><td class="print-number">{{ number_format($profit) }}</td></tr>
            <tr><td>Transactions</td><td class="print-number">{{ number_format($totalTransactions) }}</td><td>Refunded</td><td class="print-number">{{ number_format($refundedTransactions) }}</td></tr>
        </tbody>
    </table>

    <h2>Department Summary</h2>
    <table>
        <thead><tr><th>Department</th><th class="print-number">Revenue</th><th class="print-number">Profit</th><th class="print-number">Units</th></tr></thead>
        <tbody>
            @forelse($departmentBreakdown as $departmentMetric)
                <tr>
                    <td>{{ $departmentMetric->department->name ?? 'Unassigned' }}</td>
                    <td class="print-number">{{ number_format($departmentMetric->revenue) }}</td>
                    <td class="print-number">{{ number_format($departmentMetric->profit) }}</td>
                    <td class="print-number">{{ number_format($departmentMetric->units_sold) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No department data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Sales Transactions</h2>
    <table>
        <thead><tr><th>Receipt</th><th>Cashier</th><th>Payment</th><th>Department</th><th class="print-number">Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
            @forelse($printSales as $sale)
                <tr>
                    <td>{{ $sale->receipt_no }}</td>
                    <td>{{ $sale->user->name ?? 'N/A' }}</td>
                    <td>{{ $sale->payment_method }}</td>
                    <td>{{ $sale->items->pluck('department.name')->filter()->unique()->join(', ') ?: '-' }}</td>
                    <td class="print-number">{{ number_format($sale->grand_total) }}</td>
                    <td>{{ $sale->sale_status }}</td>
                    <td>{{ optional($sale->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No sales found.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
