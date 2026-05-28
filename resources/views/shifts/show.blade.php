@extends('layouts.app')

@section('content')

<h1>
    Shift Details #{{ $shift->id }}
</h1>

<div class="summary-grid">

    <div class="summary-card">

        <h3>Cashier</h3>

        <p>
            {{ $shift->user->name ?? 'N/A' }}
        </p>

    </div>

    <div class="summary-card">

        <h3>Opening Balance</h3>

        <p>
            {{ number_format($shift->opening_balance) }} RWF
        </p>

    </div>

    <div class="summary-card">

        <h3>Expected Cash</h3>

        <p>
            {{ number_format($shift->expected_cash) }} RWF
        </p>

    </div>

    <div class="summary-card">

        <h3>Closing Balance</h3>

        <p>
            {{ number_format($shift->closing_balance) }} RWF
        </p>

    </div>

    <div class="summary-card">

        <h3>Difference</h3>

        <p>
            {{ number_format($shift->difference) }} RWF
        </p>

    </div>

</div>

<h2 style="margin-top:40px;">
    Shift Sales
</h2>

<table class="table">

    <thead>

        <tr>

            <th>Receipt</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>

        </tr>

    </thead>

    <tbody>

        @forelse($sales as $sale)

            <tr>

                <td>
                    {{ $sale->receipt_no }}
                </td>
                <td>
                    {{ number_format($sale->grand_total) }} RWF
                </td>

                <td>
                    {{ $sale->payment_status }}
                </td>

                <td>
                    {{ $sale->created_at }}
                </td>

            </tr>

        @empty

            <tr>

                <td colspan="4">
                    No sales found.
                </td>

            </tr>

        @endforelse

    </tbody>

</table>

@endsection