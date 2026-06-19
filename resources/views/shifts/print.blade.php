<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Report {{ $shift->shift_code ?: '#' . $shift->id }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; font-size: 12px; }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 20px; font-weight: 800; letter-spacing: .04em; }
        .muted { color: #64748b; }
        h1 { margin: 6px 0 0; font-size: 24px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 999px; background: #e0e7ff; color: #3730a3; font-weight: 800; font-size: 11px; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 18px; }
        .card { border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; }
        .label { color: #64748b; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .value { margin-top: 5px; font-size: 17px; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 9px; text-align: left; vertical-align: top; }
        th { background: #0f172a; color: #fff; width: 34%; }
        .positive { color: #047857; font-weight: 800; }
        .negative { color: #be123c; font-weight: 800; }
        .footer { margin-top: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
        .signature { border-top: 1px solid #94a3b8; padding-top: 8px; color: #475569; }
        @media print { .no-print { display: none; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div>
            <div class="brand">FRONTIER POS</div>
            <h1>Shift Financial Report</h1>
            <p class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
        <div style="text-align: right;">
            <span class="badge">{{ $shift->status }}</span>
            <p><strong>{{ $shift->shift_code ?: '#' . $shift->id }}</strong></p>
            <p class="muted">{{ $shift->branch->name ?? 'Main Branch' }}</p>
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="label">Opening Cash</div>
            <div class="value">{{ number_format((float) $shift->opening_cash) }}</div>
        </div>
        <div class="card">
            <div class="label">Total Sales</div>
            <div class="value">{{ number_format((float) $shift->total_sales) }}</div>
        </div>
        <div class="card">
            <div class="label">Expected Cash</div>
            <div class="value">{{ number_format((float) $shift->expected_cash) }}</div>
        </div>
        <div class="card">
            <div class="label">Difference</div>
            <div class="value {{ (float) $shift->difference < 0 ? 'negative' : 'positive' }}">{{ number_format((float) $shift->difference) }}</div>
        </div>
    </div>

    <table>
        <tr><th>Shift ID</th><td>#{{ $shift->id }}</td></tr>
        <tr><th>Shift Code</th><td>{{ $shift->shift_code ?: '-' }}</td></tr>
        <tr><th>Staff</th><td>{{ $shift->user->name ?? 'N/A' }} ({{ $shift->user?->roleLabel() ?? '-' }})</td></tr>
        <tr><th>Status</th><td>{{ $shift->status }}</td></tr>
        <tr><th>Opened At</th><td>{{ $shift->opened_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
        <tr><th>Closed At</th><td>{{ $shift->closed_at?->format('Y-m-d H:i') ?? 'Still open' }}</td></tr>
        <tr><th>Opening Cash</th><td>{{ number_format((float) $shift->opening_cash) }} RWF</td></tr>
        <tr><th>Total Sales</th><td>{{ number_format((float) $shift->total_sales) }} RWF</td></tr>
        <tr><th>Cash Sales</th><td>{{ number_format((float) $shift->cash_sales) }} RWF</td></tr>
        <tr><th>MOMO Sales</th><td>{{ number_format((float) $shift->momo_sales) }} RWF</td></tr>
        <tr><th>Airtel Money Sales</th><td>{{ number_format((float) $shift->airtel_sales) }} RWF</td></tr>
        <tr><th>VISA Sales</th><td>{{ number_format((float) $shift->visa_sales) }} RWF</td></tr>
        <tr><th>Mastercard Sales</th><td>{{ number_format((float) $shift->mastercard_sales) }} RWF</td></tr>
        <tr><th>Bank Transfer Sales</th><td>{{ number_format((float) $shift->bank_transfer_sales) }} RWF</td></tr>
        <tr><th>Expected Cash</th><td>{{ number_format((float) $shift->expected_cash) }} RWF</td></tr>
        <tr><th>Closing Cash</th><td>{{ number_format((float) $shift->closing_cash) }} RWF</td></tr>
        <tr>
            <th>Difference</th>
            <td class="{{ (float) $shift->difference < 0 ? 'negative' : 'positive' }}">
                {{ number_format((float) $shift->difference) }} RWF
            </td>
        </tr>
    </table>

    <div class="footer">
        <div class="signature">Staff Signature</div>
        <div class="signature">Manager Signature</div>
    </div>
</body>
</html>
