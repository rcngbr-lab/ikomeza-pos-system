@extends('layouts.app')

@section('content')

<style>

.page-container{
    padding:30px;
}

.page-header{
    margin-bottom:30px;
}

.page-title{
    font-size:58px;
    font-weight:800;
    color:#071133;
    margin-bottom:10px;
}

.page-subtitle{
    font-size:18px;
    color:#64748b;
}

.table-card{
    background:white;
    border-radius:18px;
    padding:30px;
    box-shadow:0 2px 14px rgba(0,0,0,0.06);
    overflow-x:auto;
}

.custom-table{
    width:100%;
    border-collapse:collapse;
}

.custom-table thead{
    background:#071133;
}

.custom-table thead th{
    color:white;
    padding:18px;
    font-size:15px;
    font-weight:700;
    text-align:left;
}

.custom-table tbody td{
    padding:18px;
    border-bottom:1px solid #e5e7eb;
    font-size:15px;
    color:#111827;
}

.custom-table tbody tr:hover{
    background:#f9fafb;
}

.status-open{
    background:#dcfce7;
    color:#166534;
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
}

.status-closed{
    background:#fee2e2;
    color:#b91c1c;
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
}

.amount-positive{
    color:#16a34a;
    font-weight:700;
}

.amount-negative{
    color:#dc2626;
    font-weight:700;
}

.action-btn{
    display:inline-block;
    padding:10px 16px;
    border-radius:10px;
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    transition:0.2s;
}

.print-btn{
    background:#2563eb;
    color:white;
}

.print-btn:hover{
    background:#1d4ed8;
}

.empty-row{
    text-align:center;
    padding:40px !important;
    color:#6b7280;
    font-weight:600;
}

</style>

<div class="page-container">

    <div class="page-header">

        <h1 class="page-title">
            Shift History
        </h1>

        <p class="page-subtitle">
            Financial reconciliation and cashier shift history
        </p>

    </div>

    <div class="table-card">

        <table class="custom-table">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Cashier</th>
                    <th>Status</th>
                    <th>Opening</th>
                    <th>Total Sales</th>
                    <th>Expected</th>
                    <th>Closing</th>
                    <th>Difference</th>
                    <th>Opened</th>
                    <th>Closed</th>
                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

                @forelse($shifts as $shift)

                    <tr>

                        <td>
                            #{{ $shift->id }}
                        </td>

                        <td>
                            {{ $shift->user->name ?? 'N/A' }}
                        </td>

                        <td>

                            @if($shift->status == 'OPEN')

                                <span class="status-open">
                                    OPEN
                                </span>

                            @else

                                <span class="status-closed">
                                    CLOSED
                                </span>

                            @endif

                        </td>

                        <td>
                            {{ number_format($shift->opening_balance) }} RWF
                        </td>

                        <td>
                            {{ number_format($shift->total_sales) }} RWF
                        </td>

                        <td>
                            {{ number_format($shift->expected_cash) }} RWF
                        </td>

                        <td>
                            {{ number_format($shift->closing_balance) }} RWF
                        </td>

                        <td>

                            @if($shift->difference < 0)

                                <span class="amount-negative">

                                    {{ number_format($shift->difference) }} RWF

                                </span>

                            @else

                                <span class="amount-positive">

                                    {{ number_format($shift->difference) }} RWF

                                </span>

                            @endif

                        </td>

                        <td>
                            {{ $shift->opened_at }}
                        </td>

                        <td>

                            @if($shift->closed_at)

                                {{ $shift->closed_at }}

                            @else

                                Still Open

                            @endif

                        </td>

                        <td>

                            <a
                                href="{{ route('shifts.print', $shift->id) }}"
                                target="_blank"
                                class="action-btn print-btn"
                            >
                                Print
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="11"
                            class="empty-row"
                        >
                            No shifts found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection