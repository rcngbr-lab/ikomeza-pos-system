<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift History Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; font-size: 10px; }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; }
        .brand { font-size: 18px; font-weight: 800; letter-spacing: .04em; }
        h1 { margin: 4px 0 0; font-size: 22px; }
        .muted { color: #64748b; }
        .summary { display: grid; grid-template-columns: repeat(6, 1fr); gap: 7px; margin-bottom: 12px; }
        .card { border: 1px solid #cbd5e1; border-radius: 7px; padding: 8px; }
        .label { color: #64748b; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .value { margin-top: 4px; font-size: 14px; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #0f172a; color: #fff; font-size: 9px; text-transform: uppercase; }
        .positive { color: #047857; font-weight: 800; }
        .negative { color: #be123c; font-weight: 800; }
        .status { font-weight: 800; }
        .footer { margin-top: 12px; display: flex; justify-content: space-between; color: #64748b; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div>
            <div class="brand">FRONTIER POS</div>
            <h1>Shift History Report</h1>
            <p class="muted">Period: {{ $periodLabel }} | Generated: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
        <div style="text-align: right;">
            <p><strong>{{ $canReviewAll ? 'All Authorized Staff' : auth()->user()->name }}</strong></p>
            <p class="muted">{{ $summary['count'] }} shift records</p>
        </div>
    </div>

    <div class="summary">
        <div class="card"><div class="label">Shifts</div><div class="value">{{ number_format($summary['count']) }}</div></div>
        <div class="card"><div class="label">Open</div><div class="value">{{ number_format($summary['open']) }}</div></div>
        <div class="card"><div class="label">Closed</div><div class="value">{{ number_format($summary['closed']) }}</div></div>
        <div class="card"><div class="label">Sales</div><div class="value">{{ number_format((float) $summary['sales']) }}</div></div>
        <div class="card"><div class="label">Cash</div><div class="value">{{ number_format((float) $summary['cash']) }}</div></div>
        <div class="card"><div class="label">Difference</div><div class="value {{ (float) $summary['difference'] < 0 ? 'negative' : 'positive' }}">{{ number_format((float) $summary['difference']) }}</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Shift</th>
                <th>Staff</th>
                <th>Status</th>
                <th>Opening</th>
                <th>Total Sales</th>
                <th>Cash</th>
                <th>MOMO</th>
                <th>Airtel</th>
                <th>VISA</th>
                <th>Master</th>
                <th>Bank</th>
                <th>Expected</th>
                <th>Closing</th>
                <th>Difference</th>
                <th>Opened</th>
                <th>Closed</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $shift)
                <tr>
                    <td>{{ $shift->shift_code ?: '#' . $shift->id }}</td>
                    <td>{{ $shift->user->name ?? 'N/A' }}</td>
                    <td class="status">{{ $shift->status }}</td>
                    <td>{{ number_format((float) $shift->opening_cash) }}</td>
                    <td>{{ number_format((float) $shift->total_sales) }}</td>
                    <td>{{ number_format((float) $shift->cash_sales) }}</td>
                    <td>{{ number_format((float) $shift->momo_sales) }}</td>
                    <td>{{ number_format((float) $shift->airtel_sales) }}</td>
                    <td>{{ number_format((float) $shift->visa_sales) }}</td>
                    <td>{{ number_format((float) $shift->mastercard_sales) }}</td>
                    <td>{{ number_format((float) $shift->bank_transfer_sales) }}</td>
                    <td>{{ number_format((float) $shift->expected_cash) }}</td>
                    <td>{{ number_format((float) $shift->closing_cash) }}</td>
                    <td class="{{ (float) $shift->difference < 0 ? 'negative' : 'positive' }}">{{ number_format((float) $shift->difference) }}</td>
                    <td>{{ $shift->opened_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $shift->closed_at?->format('Y-m-d H:i') ?? 'Open' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" style="text-align:center; padding:20px; color:#64748b;">No shifts found for this filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Prepared by: {{ auth()->user()->name }}</span>
        <span>FRONTIER POS Shift Accountability Report</span>
    </div>
</body>
</html>
