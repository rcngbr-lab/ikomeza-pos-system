@extends('layouts.app')

@section('content')

<div class="dashboard-shell">
    <div class="dashboard-header">
        <div>
            <p class="dashboard-eyebrow">Cashier Dashboard</p>
            <h1 class="dashboard-title">My Sales Desk</h1>
            <p class="dashboard-subtitle">Own sales, current shift, payment methods, and receipts for {{ $dateLabel }}.</p>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('pos.index') }}" class="dashboard-action-primary">Open POS</a>
            <a href="{{ route('shifts.current') }}" class="dashboard-action">Shift</a>
            <a href="{{ route('sales.index') }}" class="dashboard-action">My Sales</a>
            <a href="{{ route('requisitions.index') }}" class="dashboard-action-warn">Request Stock</a>
        </div>
    </div>

    @include('dashboard._date_filter')

    <div class="dashboard-stat-grid xl:grid-cols-4">
        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">{{ $dateLabel }} Transactions</p>
            <p class="dashboard-stat-value">{{ number_format($todayTransactions) }}</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">{{ $dateLabel }} Net Revenue</p>
            <p class="dashboard-stat-value text-emerald-600">{{ number_format($todayRevenue) }} RWF</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">Shift Status</p>
            <p class="dashboard-stat-value {{ $activeShift ? 'text-emerald-600' : 'text-slate-500' }}">{{ $activeShift ? 'OPEN' : 'CLOSED' }}</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">Expected Cash</p>
            <p class="dashboard-stat-value">{{ number_format($expectedCash) }} RWF</p>
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-2">
        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">My Payment Methods</h2>
                    <p class="dashboard-panel-subtitle">Tender mix from your own receipts.</p>
                </div>
            </div>

            <div class="dashboard-list">
                @forelse($paymentBreakdown as $payment)
                    @php
                        $method = \App\Models\Sale::normalizePaymentMethod($payment->payment_method);
                    @endphp

                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ \App\Models\Sale::PAYMENT_METHOD_LABELS[$method] ?? $method }}</p>
                            <p class="dashboard-row-meta">{{ number_format($payment->count) }} transactions</p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-slate-950">{{ number_format($payment->total) }} RWF</span>
                    </div>
                @empty
                    <p class="dashboard-empty">No payments recorded for this period.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">Low Stock Alerts</h2>
                    <p class="dashboard-panel-subtitle">Only items useful for sales awareness.</p>
                </div>
            </div>

            <div class="dashboard-list">
                @forelse($lowStockProducts as $product)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $product->name }}</p>
                            <p class="dashboard-row-meta">Alert {{ number_format($product->alert_stock) }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-rose-600">{{ number_format($product->stock) }}</span>
                    </div>
                @empty
                    <p class="dashboard-empty">No low stock alerts.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="dashboard-panel">
        <div class="dashboard-panel-header">
            <div>
                <h2 class="dashboard-panel-title">Recent Receipts</h2>
                <p class="dashboard-panel-subtitle">Latest sales assigned to you.</p>
            </div>
            <a href="{{ route('sales.index') }}" class="text-xs font-black text-indigo-600">View All</a>
        </div>

        <div class="mt-2 overflow-x-auto">
            <table class="dense-table min-w-[620px]">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                        <tr>
                            <td class="font-black text-slate-950">{{ $sale->receipt_no }}</td>
                            <td>{{ $sale->paymentMethodLabel() }}</td>
                            <td>{{ optional($sale->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-right font-black text-emerald-600">{{ number_format($sale->grand_total) }} RWF</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="dense-empty">No sales found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@endsection
