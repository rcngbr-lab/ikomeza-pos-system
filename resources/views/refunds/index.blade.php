@extends('layouts.app')

@section('content')

<style>

.page-container{
    padding:25px;
}

.page-title{
    font-size:30px;
    font-weight:800;
    margin-bottom:25px;
    color:#111827;
}

.refund-card{
    background:white;
    border-radius:14px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.table-wrapper{
    overflow-x:auto;
}

.refund-table{
    width:100%;
    border-collapse:collapse;
}

.refund-table thead{
    background:#111827;
    color:white;
}

.refund-table th,
.refund-table td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
}

.refund-table tbody tr:hover{
    background:#f9fafb;
}

.amount{
    font-weight:700;
    color:#dc2626;
}

.status-badge{
    background:#dc2626;
    color:white;
    padding:6px 10px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
    text-transform:uppercase;
}

.empty-box{
    padding:40px;
    text-align:center;
    color:#6b7280;
}

</style>

<div class="page-container">

    <h1 class="page-title">

        Refund History

    </h1>

    <div class="refund-card">

        @if($refunds->count())

            <div class="table-wrapper">

                <table class="refund-table">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Receipt</th>

                            <th>Refunded By</th>

                            <th>Amount</th>

                            <th>Reason</th>

                            <th>Status</th>

                            <th>Date</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($refunds as $refund)

                            <tr>

                                <td>
                                    {{ $refund->id }}
                                </td>

                                <td>

                                    {{ $refund->sale->receipt_no ?? 'N/A' }}

                                </td>

                                <td>

                                    {{ $refund->user->name ?? 'Unknown' }}

                                </td>

                                <td class="amount">

                                    {{ number_format($refund->amount ?? 0, 0) }} RWF

                                </td>

                                <td>

                                    {{ $refund->reason ?? '-' }}

                                </td>

                                <td>

                                    <span class="status-badge">

                                        {{ $refund->status ?? 'UNKNOWN' }}

                                    </span>

                                </td>

                                <td>

                                    @if($refund->refunded_at)

                                        {{
                                            \Carbon\Carbon::parse(
                                                $refund->refunded_at
                                            )->format('d M Y H:i')
                                        }}

                                    @else

                                        -

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="empty-box">

                No refunds found.

            </div>

        @endif

    </div>

</div>

@endsection