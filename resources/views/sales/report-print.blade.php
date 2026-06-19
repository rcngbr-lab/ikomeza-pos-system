<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isPersonalReport ? 'My Sales Report' : 'Sales Report' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 12mm;
        }

        .topline {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }

        .brand {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: .3px;
        }

        .muted {
            color: #64748b;
        }

        .title {
            margin-top: 12px;
            font-size: 26px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: .2px;
        }

        .meta-grid,
        .stat-grid,
        .payment-grid {
            display: grid;
            gap: 6px;
        }

        .meta-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 10px;
        }

        .stat-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-top: 10px;
        }

        .payment-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 8px;
        }

        .card {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 7px 8px;
            min-height: 42px;
            background: #f8fafc;
        }

        .label {
            color: #64748b;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .value {
            margin-top: 3px;
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
        }

        .value.green {
            color: #059669;
        }

        .value.red {
            color: #e11d48;
        }

        .value.amber {
            color: #d97706;
        }

        .section-title {
            margin-top: 13px;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 900;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            background: #020617;
            color: #fff;
            font-size: 8px;
            letter-spacing: .3px;
            padding: 6px 5px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        tr {
            page-break-inside: avoid;
        }

        .right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 6px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 8px;
            font-weight: 900;
        }

        .badge.ok {
            background: #d1fae5;
            color: #047857;
        }

        .badge.refund {
            background: #ffe4e6;
            color: #be123c;
        }

        .footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid #cbd5e1;
        }

        .signature {
            padding-top: 26px;
            border-top: 1px solid #94a3b8;
            font-size: 10px;
            font-weight: 900;
            color: #334155;
        }

        .print-bar {
            position: sticky;
            top: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: #0f172a;
        }

        .print-bar button,
        .print-bar a {
            border: 0;
            border-radius: 8px;
            padding: 8px 14px;
            background: #4f46e5;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .print-bar a {
            background: #fff;
            color: #0f172a;
        }

        @media print {
            body {
                background: #fff;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }

            .print-bar {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-bar">
        <button type="button" onclick="window.print()">Print A4 Report</button>
        <a href="{{ route('sales.index', request()->query()) }}">Back to Sales</a>
    </div>

    <main class="sheet">
        <header class="topline">
            <div>
                <div class="brand">FRONTIER POS</div>
                <div class="muted">Business operations control center</div>
            </div>
            <div style="text-align:right">
                <div><strong>Generated:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                <div><strong>Printed By:</strong> {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})</div>
            </div>
        </header>

        <h1 class="title">{{ $isPersonalReport ? 'MY SALES REPORT' : 'SALES REPORT' }}</h1>
        <p class="muted">
            {{ $isPersonalReport ? 'This report is limited to the cashier/waiter/server who printed it.' : 'This report follows the active branch, role, department, period, and search filters.' }}
        </p>

        <section class="meta-grid">
            <div class="card">
                <div class="label">Period</div>
                <div class="value">{{ $periodLabel }}</div>
            </div>
            <div class="card">
                <div class="label">Branch</div>
                <div class="value">{{ $branchLabel }}</div>
            </div>
            <div class="card">
                <div class="label">Department</div>
                <div class="value">{{ $selectedDepartment?->name ?? 'All Visible' }}</div>
            </div>
            <div class="card">
                <div class="label">Search</div>
                <div class="value">{{ $search ?: 'None' }}</div>
            </div>
        </section>

        <section class="stat-grid">
            <div class="card">
                <div class="label">Net Sales</div>
                <div class="value green">{{ number_format($totalSales) }}</div>
            </div>
            <div class="card">
                <div class="label">Gross Sales</div>
                <div class="value">{{ number_format($grossSales) }}</div>
            </div>
            <div class="card">
                <div class="label">Refunded</div>
                <div class="value red">{{ number_format($refundedSales) }}</div>
            </div>
            <div class="card">
                <div class="label">Net Txn</div>
                <div class="value">{{ number_format($totalTransactions) }}</div>
            </div>
            <div class="card">
                <div class="label">Refund Txn</div>
                <div class="value amber">{{ number_format($refundedTransactions) }}</div>
            </div>
        </section>

        <h2 class="section-title">Payment Breakdown</h2>
        <section class="payment-grid">
            @forelse($paymentBreakdown as $method => $amount)
                <div class="card">
                    <div class="label">{{ $method }}</div>
                    <div class="value">{{ number_format($amount) }}</div>
                </div>
            @empty
                <div class="card">
                    <div class="label">No Payment Data</div>
                    <div class="value">0</div>
                </div>
            @endforelse
        </section>

        <h2 class="section-title">Receipt Register</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:19%">Invoice</th>
                    <th style="width:13%">Date</th>
                    <th style="width:12%">Cashier</th>
                    <th style="width:14%">Customer</th>
                    <th style="width:13%">Dept</th>
                    <th style="width:10%">Status</th>
                    <th style="width:9%" class="right">Paid</th>
                    <th style="width:10%" class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    @php
                        $isRefunded = (bool) $sale->is_refunded || $sale->sale_status === 'REFUNDED';
                        $departments = $sale->items->pluck('department.name')->filter()->unique()->implode(', ');
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $sale->receipt_no ?? 'N/A' }}</strong><br>
                            <span class="muted">#{{ $sale->id }}</span>
                        </td>
                        <td>{{ optional($sale->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                        <td>{{ $sale->customer_name ?? $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td>{{ $departments ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $isRefunded ? 'refund' : 'ok' }}">
                                {{ $isRefunded ? 'REFUNDED' : ($sale->sale_status ?: 'COMPLETED') }}
                            </span>
                        </td>
                        <td class="right">{{ number_format($sale->amount_paid) }}</td>
                        <td class="right"><strong>{{ number_format($sale->grand_total) }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:18px; font-weight:900;">
                            No sales match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="footer">
            <div class="signature">Cashier / Staff Signature</div>
            <div class="signature">Manager Verification</div>
        </footer>
    </main>
</body>
</html>
