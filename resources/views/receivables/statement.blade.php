<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement - {{ $customer->name }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-family: Arial, sans-serif; font-size: 11px; }
        .toolbar { margin-bottom: 10px; }
        .btn { border: 0; border-radius: 7px; background: #0f172a; color: #fff; cursor: pointer; font-weight: 800; padding: 8px 12px; }
        .sheet { width: 100%; }
        .header { align-items: flex-start; border-bottom: 2px solid #0f172a; display: flex; justify-content: space-between; gap: 16px; padding-bottom: 10px; }
        .brand { align-items: center; display: flex; gap: 10px; }
        .brand img { height: 42px; max-width: 42px; object-fit: contain; }
        .brand-name { font-size: 20px; font-weight: 900; letter-spacing: .04em; }
        .muted { color: #64748b; }
        h1 { font-size: 20px; margin: 14px 0 6px; }
        .grid { display: grid; gap: 8px; grid-template-columns: repeat(4, 1fr); margin: 12px 0; }
        .box { border: 1px solid #cbd5e1; border-radius: 9px; padding: 8px; }
        .label { color: #64748b; font-size: 9px; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: 900; margin-top: 3px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #0f172a; color: white; font-size: 9px; padding: 7px 6px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e2e8f0; padding: 7px 6px; vertical-align: top; }
        .right { text-align: right; }
        .totals { margin-left: auto; margin-top: 12px; width: 280px; }
        .totals .row { border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; padding: 6px 0; }
        .totals .final { border-bottom: 0; font-size: 14px; font-weight: 900; }
        .signature { display: grid; gap: 24px; grid-template-columns: repeat(2, 1fr); margin-top: 38px; }
        .line { border-top: 1px solid #0f172a; padding-top: 5px; }
        @media print {
            .toolbar { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn" onclick="window.print()">Print Statement</button>
    </div>

    <main class="sheet">
        <header class="header">
            <div class="brand">
                <img src="{{ asset('images/frontier-logo.png') }}" alt="Frontier logo">
                <div>
                    <div class="brand-name">FRONTIER POS</div>
                    <div class="muted">Frontier Shop · Accounts Receivable</div>
                </div>
            </div>
            <div class="right">
                <div class="label">Statement Period</div>
                <div class="value">{{ $start->format('Y-m-d') }} to {{ $end->format('Y-m-d') }}</div>
                <div class="muted">Generated {{ now()->format('Y-m-d H:i') }}</div>
            </div>
        </header>

        <h1>Customer Statement</h1>
        <div class="muted">{{ $customer->customer_code }} · {{ $customer->phone ?: 'No phone' }} · {{ $customer->email ?: 'No email' }}</div>

        <section class="grid">
            <div class="box">
                <div class="label">Account</div>
                <div class="value">{{ $account->account_number }}</div>
            </div>
            <div class="box">
                <div class="label">Credit Limit</div>
                <div class="value">{{ number_format($account->credit_limit) }} RWF</div>
            </div>
            <div class="box">
                <div class="label">Risk</div>
                <div class="value">{{ $account->risk_level }}</div>
            </div>
            <div class="box">
                <div class="label">Status</div>
                <div class="value">{{ $account->status }}</div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Document</th>
                    <th>Description</th>
                    <th class="right">Debit</th>
                    <th class="right">Credit</th>
                    <th class="right">Balance</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $start->format('Y-m-d') }}</td>
                    <td>OPENING</td>
                    <td>Opening balance</td>
                    <td class="right">-</td>
                    <td class="right">-</td>
                    <td class="right">{{ number_format($opening) }}</td>
                    <td>-</td>
                </tr>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date?->format('Y-m-d') }}</td>
                        <td>{{ $transaction->document_number ?: $transaction->transaction_number }}</td>
                        <td>{{ $transaction->description ?: $transaction->transaction_type }}</td>
                        <td class="right">{{ $transaction->debit > 0 ? number_format($transaction->debit) : '-' }}</td>
                        <td class="right">{{ $transaction->credit > 0 ? number_format($transaction->credit) : '-' }}</td>
                        <td class="right">{{ number_format($transaction->balance_after) }}</td>
                        <td>{{ $transaction->due_date?->format('Y-m-d') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding: 18px; text-align: center;">No transactions in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <section class="totals">
            <div class="row"><span>Opening</span><strong>{{ number_format($opening) }} RWF</strong></div>
            <div class="row"><span>Debit Total</span><strong>{{ number_format($debitTotal) }} RWF</strong></div>
            <div class="row"><span>Credit Total</span><strong>{{ number_format($creditTotal) }} RWF</strong></div>
            <div class="row final"><span>Closing Balance</span><strong>{{ number_format($closing) }} RWF</strong></div>
        </section>

        <section class="signature">
            <div class="line">Prepared by</div>
            <div class="line">Customer signature</div>
        </section>
    </main>
</body>
</html>
