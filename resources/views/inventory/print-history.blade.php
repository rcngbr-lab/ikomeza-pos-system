<!DOCTYPE html>
<html>
<head>
    <title>{{ $reportTitle ?? 'Inventory Report' }}</title>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .sheet {
            width: 277mm;
            min-height: 190mm;
            margin: 16px auto;
            background: #ffffff;
            padding: 10mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        }

        .actions {
            width: 277mm;
            margin: 16px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .actions button,
        .actions a {
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .actions button {
            background: #111827;
            color: #ffffff;
        }

        .actions a {
            background: #e5e7eb;
            color: #111827;
        }

        .report-header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .brand {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .title {
            margin-top: 6px;
            font-size: 24px;
            font-weight: 900;
        }

        .meta {
            text-align: right;
            line-height: 1.7;
            color: #334155;
            font-weight: 700;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px;
        }

        .summary-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 5px;
            font-size: 18px;
            font-weight: 900;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #111827;
            color: #ffffff;
            padding: 7px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #dbe3ef;
            padding: 7px;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }

        .number {
            text-align: right;
            font-weight: 800;
        }

        .type {
            font-weight: 900;
            text-transform: uppercase;
        }

        .type-stock_in {
            color: #047857;
        }

        .type-damage {
            color: #be123c;
        }

        .footer {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .actions {
                display: none !important;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="actions">
        <a href="{{ route('inventory.index') }}">Back</a>
        <button onclick="window.print()">Print Report</button>
    </div>

    <main class="sheet">
        <header class="report-header">
            <div>
                <div class="brand">FRONTIER POS</div>
                <div class="title">{{ $reportTitle ?? 'Inventory Stock History Report' }}</div>
            </div>

            <div class="meta">
                <div>Generated: {{ now()->format('Y-m-d H:i') }}</div>
                <div>Period: {{ $reportPeriod ?? 'All Time' }}</div>
                <div>Department: {{ $reportDepartment ?? 'All Departments' }}</div>
            </div>
        </header>

        <section class="summary">
            <div class="summary-box">
                <div class="summary-label">Records</div>
                <div class="summary-value">{{ number_format($totalRecords ?? $stockHistory->count()) }}</div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Total Quantity</div>
                <div class="summary-value">{{ number_format($totalQuantity ?? $stockHistory->sum('quantity')) }}</div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Report Type</div>
                <div class="summary-value">{{ str_replace('_', ' ', strtoupper($reportType ?: 'ALL')) }}</div>
            </div>

            <div class="summary-box">
                <div class="summary-label">Prepared By</div>
                <div class="summary-value">{{ auth()->user()->name ?? 'System' }}</div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 20%;">Product</th>
                    <th style="width: 12%;">Department</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 8%;">Before</th>
                    <th style="width: 8%;">After</th>
                    <th style="width: 12%;">User</th>
                    <th>Note / Reason</th>
                </tr>
            </thead>

            <tbody>
                @forelse($stockHistory as $history)
                    <tr>
                        <td>{{ $history->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $history->product->name ?? '-' }}</td>
                        <td>{{ $history->department->name ?? $history->product?->department?->name ?? '-' }}</td>
                        <td class="type type-{{ strtolower($history->type) }}">{{ strtoupper(str_replace('_', ' ', $history->type)) }}</td>
                        <td class="number">{{ number_format($history->quantity) }}</td>
                        <td class="number">{{ number_format($history->before_stock) }}</td>
                        <td class="number">{{ number_format($history->after_stock) }}</td>
                        <td>{{ $history->user->name ?? '-' }}</td>
                        <td>{{ $history->note ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 18px; font-weight: 800;">
                            No records found for this report.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="footer">
            <span>FRONTIER POS SYSTEM</span>
            <span>Authorized inventory report</span>
        </footer>
    </main>
</body>
</html>
