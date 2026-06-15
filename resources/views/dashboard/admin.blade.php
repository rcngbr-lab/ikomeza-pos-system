@extends('layouts.app')

@section('content')

@php
    $kpis = [
        ['label' => $dateLabel . ' Net Revenue', 'value' => number_format($todayRevenue) . ' RWF', 'tone' => 'text-emerald-600'],
        ['label' => 'Monthly Net Revenue', 'value' => number_format($monthRevenue) . ' RWF', 'tone' => 'text-indigo-600'],
        ['label' => $dateLabel . ' Refunds', 'value' => number_format($totalRefunds ?? 0) . ' RWF', 'tone' => 'text-rose-600'],
        ['label' => $dateLabel . ' Gross Profit', 'value' => number_format($profit) . ' RWF', 'tone' => 'text-slate-950'],
        ['label' => 'Inventory Value', 'value' => number_format($inventoryValue) . ' RWF', 'tone' => 'text-amber-600'],
    ];

    $ops = [
        ['label' => 'Products', 'value' => $totalProducts],
        ['label' => 'Stock Units', 'value' => number_format($totalStock)],
        ['label' => 'Transactions', 'value' => number_format($totalSales)],
        ['label' => 'Open Shifts', 'value' => number_format($openShifts)],
    ];

    $maxPayment = max((float) $paymentBreakdown->max('total'), 1);
@endphp

<div class="dashboard-shell">
    <div class="dashboard-header">
        <div>
            <p class="dashboard-eyebrow">
                Executive Dashboard
            </p>
            <h1 class="dashboard-title">
                Business Command Center
            </h1>
            <p class="dashboard-subtitle">
                Real-time revenue, stock, employee activity, payment mix, and operational risk signals.
            </p>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('pos.index') }}" class="dashboard-action-primary">
                Open POS
            </a>
            <a href="{{ route('requisitions.index') }}" class="dashboard-action-warn">
                Requisitions
            </a>
            <a href="{{ route('reports.index') }}" class="dashboard-action">
                Reports
            </a>
        </div>
    </div>

    @include('dashboard._date_filter')

    <div class="dashboard-stat-grid xl:grid-cols-5">
        @foreach($kpis as $kpi)
            <div class="dashboard-stat-card">
                <p class="dashboard-stat-label">{{ $kpi['label'] }}</p>
                <p class="dashboard-stat-value {{ $kpi['tone'] }}">
                    {{ $kpi['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="dashboard-stat-grid xl:grid-cols-4">
        @foreach($ops as $item)
            <div class="dashboard-stat-card">
                <p class="dashboard-stat-label">{{ $item['label'] }}</p>
                <p class="dashboard-stat-value">{{ $item['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="dashboard-panel">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="dashboard-panel-title">Revenue Trend</h2>
                    <p class="dashboard-panel-subtitle">Daily, weekly, monthly, and yearly sales movement.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">
                    Live
                </span>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-4">
                @foreach([
                    'Today' => $todayRevenue,
                    'Week' => $weekRevenue,
                    'Month' => $monthRevenue,
                    'Year' => $yearRevenue,
                ] as $label => $amount)
                    @php $width = $yearRevenue > 0 ? max(8, ($amount / $yearRevenue) * 100) : 8; @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">{{ $label }}</span>
                            <span class="font-black text-slate-950">{{ number_format($amount) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ min($width, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="dashboard-panel">
            <h2 class="dashboard-panel-title">Payment Mix</h2>
            <p class="dashboard-panel-subtitle">Tender performance across completed sales.</p>

            <div class="mt-3 space-y-2">
                @forelse($paymentBreakdown as $payment)
                    @php
                        $method = \App\Models\Sale::normalizePaymentMethod($payment->payment_method);
                        $width = max(6, ((float) $payment->total / $maxPayment) * 100);
                    @endphp

                    <div>
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="font-bold text-slate-700">{{ \App\Models\Sale::PAYMENT_METHOD_LABELS[$method] ?? $method }}</span>
                            <span class="font-black text-slate-950">{{ number_format($payment->total) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($width, 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="dashboard-empty">
                        No payment activity yet.
                    </p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-3 xl:grid-cols-3">
        <section class="dashboard-panel xl:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="dashboard-panel-title">Recent Sales</h2>
                    <p class="dashboard-panel-subtitle">Latest completed transactions.</p>
                </div>
                <a href="{{ route('sales.index') }}" class="text-xs font-black text-indigo-600">View all</a>
            </div>

            <div class="mt-2 overflow-x-auto">
                <table class="dense-table min-w-[680px]">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Cashier</th>
                            <th>Payment</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr>
                                <td class="font-black text-slate-950">{{ $sale->receipt_no }}</td>
                                <td>{{ $sale->user->name ?? 'N/A' }}</td>
                                <td>{{ $sale->paymentMethodLabel() }}</td>
                                <td class="text-right font-black text-emerald-600">{{ number_format($sale->grand_total) }} RWF</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="dense-empty">
                                    No sales recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel">
            <h2 class="dashboard-panel-title">Low Stock Alerts</h2>
            <p class="dashboard-panel-subtitle">Products that need attention before service is affected.</p>

            <div class="dashboard-list">
                @forelse($lowStockProducts as $product)
                    <div class="py-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="dashboard-row-title">{{ $product->name }}</p>
                                <p class="dashboard-row-meta text-amber-700">Alert {{ number_format($product->alert_stock) }}</p>
                            </div>
                            <span class="rounded-full bg-amber-50 px-2 py-1 text-[11px] font-black text-amber-700">
                                {{ number_format($product->stock) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="dashboard-empty">
                        Stock levels are healthy.
                    </p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="dashboard-panel">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="dashboard-panel-title">Top-Selling Products</h2>
                <p class="dashboard-panel-subtitle">Fast movers for stocking and menu decisions.</p>
            </div>
            <a href="{{ route('inventory.index') }}" class="text-xs font-black text-indigo-600">Inventory</a>
        </div>

        <div class="mt-3 grid gap-2 md:grid-cols-5">
            @forelse($topProducts as $product)
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <p class="truncate text-xs font-black text-slate-950">{{ $product->product->name ?? 'Deleted product' }}</p>
                    <p class="mt-1 text-lg font-black text-indigo-600">{{ number_format($product->units_sold) }}</p>
                    <p class="text-[11px] font-semibold text-slate-500">units sold</p>
                </div>
            @empty
                <p class="dashboard-empty md:col-span-5">
                    No product performance data yet.
                </p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-3 xl:grid-cols-3">
        <section class="dashboard-panel">
            <h2 class="dashboard-panel-title">Cashier Performance</h2>
            <div class="dashboard-list">
                @forelse($cashierPerformance as $cashier)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $cashier->name }}</p>
                            <p class="dashboard-row-meta">{{ $cashier->transactions_today }} transactions</p>
                        </div>
                        <span class="text-xs font-black text-emerald-600">{{ number_format($cashier->revenue_today ?? 0) }}</span>
                    </div>
                @empty
                    <p class="dashboard-empty">
                        No cashier performance data yet.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <h2 class="dashboard-panel-title">Shift Differences</h2>
            <div class="dashboard-list">
                @forelse($shiftDifferences as $shift)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $shift->user?->name ?? 'Unassigned' }}</p>
                            <p class="dashboard-row-meta">{{ $shift->shift_code }}</p>
                        </div>
                        <span class="text-xs font-black {{ (float) $shift->difference === 0.0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($shift->difference) }}
                        </span>
                    </div>
                @empty
                    <p class="dashboard-empty">
                        No closed shifts yet.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <h2 class="dashboard-panel-title">Audit Activity</h2>
            <div class="dashboard-list">
                @forelse($recentAuditLogs as $log)
                    <div class="py-2">
                        <p class="dashboard-row-title">{{ $log->event ?? 'Activity' }}</p>
                        <p class="mt-0.5 line-clamp-2 text-[11px] font-semibold text-slate-500">
                            {{ $log->description ?? $log->model ?? 'System activity' }}
                        </p>
                    </div>
                @empty
                    <p class="dashboard-empty">
                        No audit logs yet.
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</div>

@endsection
