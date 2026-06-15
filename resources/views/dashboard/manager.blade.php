@extends('layouts.app')

@section('content')

<div class="dashboard-shell">
    <div class="dashboard-header">
        <div>
            <p class="dashboard-eyebrow">Manager Dashboard</p>
            <h1 class="dashboard-title">Operations Control</h1>
            <p class="dashboard-subtitle">Sales, payments, shifts, refunds, and stock alerts for {{ $dateLabel }}.</p>
        </div>

        <div class="dashboard-actions">
            <a href="{{ route('pos.index') }}" class="dashboard-action-primary">Open POS</a>
            <a href="{{ route('requisitions.index') }}" class="dashboard-action-warn">Requisitions</a>
            <a href="{{ route('reports.index') }}" class="dashboard-action">Reports</a>
            <a href="{{ route('shifts.history') }}" class="dashboard-action">Shifts</a>
        </div>
    </div>

    @include('dashboard._date_filter')

    <div class="dashboard-stat-grid xl:grid-cols-5">
        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">{{ $dateLabel }} Net Revenue</p>
            <p class="dashboard-stat-value text-emerald-600">{{ number_format($todayRevenue) }} RWF</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">Transactions</p>
            <p class="dashboard-stat-value">{{ number_format($todayTransactions) }}</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">Low Stock</p>
            <p class="dashboard-stat-value text-rose-600">{{ number_format($lowStock->count()) }}</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">Pending Refunds</p>
            <p class="dashboard-stat-value text-amber-600">{{ number_format($pendingRefunds->count()) }}</p>
        </div>

        <div class="dashboard-stat-card">
            <p class="dashboard-stat-label">Stock Status</p>
            <p class="dashboard-stat-value text-emerald-600">Active</p>
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-2">
        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">Cashier Performance</h2>
                    <p class="dashboard-panel-subtitle">{{ $dateLabel }} revenue by cashier/server.</p>
                </div>
            </div>

            <div class="dashboard-list">
                @forelse($cashierPerformance as $cashier)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $cashier->name }}</p>
                            <p class="dashboard-row-meta">{{ number_format($cashier->transactions_today) }} transactions</p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-emerald-600">{{ number_format($cashier->revenue_today ?? 0) }} RWF</span>
                    </div>
                @empty
                    <p class="dashboard-empty">No cashier sales for this period.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">Payment Breakdown</h2>
                    <p class="dashboard-panel-subtitle">Tender totals and transaction counts.</p>
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
                    <p class="dashboard-empty">No payment data for this period.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-3 xl:grid-cols-3">
        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">Shift Differences</h2>
                    <p class="dashboard-panel-subtitle">Closed-shift cash variance.</p>
                </div>
            </div>

            <div class="dashboard-list">
                @forelse($shiftDifferences as $shift)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $shift->user?->name ?? 'Unassigned' }}</p>
                            <p class="dashboard-row-meta">{{ $shift->shift_code }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-black {{ (float) $shift->difference === 0.0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ number_format($shift->difference) }}
                        </span>
                    </div>
                @empty
                    <p class="dashboard-empty">No closed shifts for this period.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">Pending Refunds</h2>
                    <p class="dashboard-panel-subtitle">Refunds waiting for approval.</p>
                </div>
            </div>

            <div class="dashboard-list">
                @forelse($pendingRefunds as $refund)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $refund->sale?->receipt_no ?? 'Refund' }}</p>
                            <p class="dashboard-row-meta">{{ $refund->user?->name ?? 'Unknown' }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-rose-600">{{ number_format($refund->amount) }} RWF</span>
                    </div>
                @empty
                    <p class="dashboard-empty">No pending refunds for this period.</p>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="dashboard-panel-header">
                <div>
                    <h2 class="dashboard-panel-title">Inventory Movements</h2>
                    <p class="dashboard-panel-subtitle">Recent stock operations.</p>
                </div>
            </div>

            <div class="dashboard-list">
                @forelse($recentMovements as $movement)
                    <div class="dashboard-list-row">
                        <div class="min-w-0">
                            <p class="dashboard-row-title">{{ $movement->product?->name ?? 'Product' }}</p>
                            <p class="dashboard-row-meta">{{ $movement->type }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-black text-slate-950">{{ number_format($movement->quantity) }}</span>
                    </div>
                @empty
                    <p class="dashboard-empty">No inventory movement for this period.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="dashboard-panel">
        <div class="dashboard-panel-header">
            <div>
                <h2 class="dashboard-panel-title">Low Stock Products</h2>
                <p class="dashboard-panel-subtitle">Items below alert level.</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-xs font-black text-indigo-600">View Products</a>
        </div>

        <div class="mt-2 overflow-x-auto">
            <table class="dense-table min-w-[560px]">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Alert Level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowStock as $product)
                        <tr>
                            <td class="font-black text-slate-950">{{ $product->name }}</td>
                            <td class="font-black text-rose-600">{{ number_format($product->stock) }}</td>
                            <td>{{ number_format($product->alert_stock) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="dense-empty">No low stock products.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@endsection
