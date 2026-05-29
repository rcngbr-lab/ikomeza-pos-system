@extends('layouts.app')

@section('content')

@php
    $kpis = [
        ['label' => 'Today Net Revenue', 'value' => number_format($todayRevenue) . ' RWF', 'tone' => 'text-emerald-600'],
        ['label' => 'Monthly Net Revenue', 'value' => number_format($monthRevenue) . ' RWF', 'tone' => 'text-indigo-600'],
        ['label' => 'Refunds', 'value' => number_format($totalRefunds ?? 0) . ' RWF', 'tone' => 'text-rose-600'],
        ['label' => 'Gross Profit', 'value' => number_format($profit) . ' RWF', 'tone' => 'text-slate-950'],
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

<div class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">
                Executive Dashboard
            </p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                Business Command Center
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                Real-time revenue, stock, employee activity, payment mix, and operational risk signals.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:flex">
            <a href="{{ route('pos.index') }}" class="rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-black text-white shadow-lg shadow-indigo-600/20">
                Open POS
            </a>
            <a href="{{ route('requisitions.index') }}" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm font-black text-amber-700 shadow-sm">
                Requisitions
            </a>
            <a href="{{ route('reports.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-black text-slate-700 shadow-sm">
                Reports
            </a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $kpi)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $kpi['label'] }}</p>
                <p class="mt-3 text-2xl font-black tracking-tight {{ $kpi['tone'] }}">
                    {{ $kpi['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($ops as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $item['label'] }}</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ $item['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Revenue Trend</h2>
                    <p class="mt-1 text-sm text-slate-500">Daily, weekly, monthly, and yearly sales movement.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">
                    Live
                </span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
                @foreach([
                    'Today' => $todayRevenue,
                    'Week' => $weekRevenue,
                    'Month' => $monthRevenue,
                    'Year' => $yearRevenue,
                ] as $label => $amount)
                    @php $width = $yearRevenue > 0 ? max(8, ($amount / $yearRevenue) * 100) : 8; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-bold text-slate-700">{{ $label }}</span>
                            <span class="font-black text-slate-950">{{ number_format($amount) }}</span>
                        </div>
                        <div class="mt-3 h-3 rounded-full bg-slate-100">
                            <div class="h-3 rounded-full bg-indigo-600" style="width: {{ min($width, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Payment Mix</h2>
            <p class="mt-1 text-sm text-slate-500">Tender performance across completed sales.</p>

            <div class="mt-5 space-y-4">
                @forelse($paymentBreakdown as $payment)
                    @php
                        $method = \App\Models\Sale::normalizePaymentMethod($payment->payment_method);
                        $width = max(6, ((float) $payment->total / $maxPayment) * 100);
                    @endphp

                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-bold text-slate-700">{{ \App\Models\Sale::PAYMENT_METHOD_LABELS[$method] ?? $method }}</span>
                            <span class="font-black text-slate-950">{{ number_format($payment->total) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($width, 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-500">
                        No payment activity yet.
                    </p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Recent Sales</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest completed transactions.</p>
                </div>
                <a href="{{ route('sales.index') }}" class="text-sm font-black text-indigo-600">View all</a>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[680px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-black uppercase tracking-wider text-slate-400">
                            <th class="py-3">Receipt</th>
                            <th class="py-3">Cashier</th>
                            <th class="py-3">Payment</th>
                            <th class="py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                            <tr class="border-b border-slate-100">
                                <td class="py-4 text-sm font-black text-slate-950">{{ $sale->receipt_no }}</td>
                                <td class="py-4 text-sm text-slate-600">{{ $sale->user->name ?? 'N/A' }}</td>
                                <td class="py-4 text-sm text-slate-600">{{ $sale->paymentMethodLabel() }}</td>
                                <td class="py-4 text-right text-sm font-black text-emerald-600">{{ number_format($sale->grand_total) }} RWF</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-sm font-semibold text-slate-500">
                                    No sales recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Low Stock Alerts</h2>
            <p class="mt-1 text-sm text-slate-500">Products that need attention before service is affected.</p>

            <div class="mt-5 space-y-3">
                @forelse($lowStockProducts as $product)
                    <div class="rounded-xl bg-amber-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-slate-950">{{ $product->name }}</p>
                                <p class="mt-1 text-xs text-amber-700">Alert level {{ number_format($product->alert_stock) }}</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-amber-700">
                                {{ number_format($product->stock) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-500">
                        Stock levels are healthy.
                    </p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-950">Top-Selling Products</h2>
                <p class="mt-1 text-sm text-slate-500">Fast movers for stocking and menu decisions.</p>
            </div>
            <a href="{{ route('inventory.index') }}" class="text-sm font-black text-indigo-600">Inventory</a>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-5">
            @forelse($topProducts as $product)
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="truncate text-sm font-black text-slate-950">{{ $product->product->name ?? 'Deleted product' }}</p>
                    <p class="mt-3 text-2xl font-black text-indigo-600">{{ number_format($product->units_sold) }}</p>
                    <p class="text-xs font-semibold text-slate-500">units sold</p>
                </div>
            @empty
                <p class="md:col-span-5 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-500">
                    No product performance data yet.
                </p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Cashier Performance</h2>
            <div class="mt-5 space-y-3">
                @forelse($cashierPerformance as $cashier)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-950">{{ $cashier->name }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $cashier->transactions_today }} transactions today</p>
                        </div>
                        <span class="text-sm font-black text-emerald-600">{{ number_format($cashier->revenue_today ?? 0) }}</span>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-500">
                        No cashier performance data yet.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Shift Differences</h2>
            <div class="mt-5 space-y-3">
                @forelse($shiftDifferences as $shift)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-950">{{ $shift->user?->name ?? 'Unassigned' }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $shift->shift_code }}</p>
                        </div>
                        <span class="text-sm font-black {{ (float) $shift->difference === 0.0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($shift->difference) }}
                        </span>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-500">
                        No closed shifts yet.
                    </p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Audit Activity</h2>
            <div class="mt-5 space-y-3">
                @forelse($recentAuditLogs as $log)
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="truncate text-sm font-black text-slate-950">{{ $log->event ?? 'Activity' }}</p>
                        <p class="mt-1 line-clamp-2 text-xs font-semibold text-slate-500">
                            {{ $log->description ?? $log->model ?? 'System activity' }}
                        </p>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm font-semibold text-slate-500">
                        No audit logs yet.
                    </p>
                @endforelse
            </div>
        </section>
    </div>
</div>

@endsection
