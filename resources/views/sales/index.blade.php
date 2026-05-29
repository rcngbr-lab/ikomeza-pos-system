@extends('layouts.app')

@section('content')

<style>

    /*
    |--------------------------------------------------------------------------
    | PAGE HEADER
    |--------------------------------------------------------------------------
    */

    .page-header{
        margin-bottom:25px;
    }

    .page-header h1{
        margin-bottom:5px;
        font-size:48px;
        font-weight:800;
        color:#0f172a;
    }

    .page-header p{
        color:#64748b;
        font-size:18px;
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY CARDS
    |--------------------------------------------------------------------------
    */

    .summary-grid{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:20px;
        margin-bottom:25px;
    }

    .summary-card{
        background:white;
        padding:25px;
        border-radius:12px;
        box-shadow:0 2px 8px rgba(0,0,0,0.08);
    }

    .summary-card h3{
        margin:0;
        color:#64748b;
        font-size:16px;
        font-weight:600;
    }

    .summary-card h1{
        margin-top:10px;
        font-size:42px;
        font-weight:800;
        color:#111827;
    }

    .summary-card.net h1{
        color:#16a34a;
    }

    .summary-card.refund h1{
        color:#dc2626;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER SECTION
    |--------------------------------------------------------------------------
    */

    .sales-filter-bar{
        background:white;
        padding:20px;
        border-radius:12px;
        margin-bottom:25px;
        box-shadow:0 2px 8px rgba(0,0,0,0.08);
    }

    .sales-filter-form{
        display:flex;
        align-items:center;
        gap:15px;
        flex-wrap:wrap;
    }

    .filter-select{
        height:48px;
        min-width:200px;
        border:1px solid #d1d5db;
        border-radius:10px;
        padding:0 15px;
        background:white;
        font-size:15px;
        outline:none;
    }

    .filter-input{
        height:48px;
        min-width:300px;
        border:1px solid #d1d5db;
        border-radius:10px;
        padding:0 15px;
        font-size:15px;
        outline:none;
    }

    .filter-input:focus,
    .filter-select:focus{
        border-color:#2563eb;
    }

    .filter-btn{
        height:48px;
        padding:0 24px;
        background:#16a34a;
        color:white;
        border:none;
        border-radius:10px;
        font-weight:700;
        cursor:pointer;
        transition:0.2s;
    }

    .filter-btn:hover{
        background:#15803d;
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    .table-wrapper{
        background:white;
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 2px 8px rgba(0,0,0,0.08);
    }

    table{
        width:100%;
        border-collapse:collapse;
    }

    th{
        background:#1e293b;
        color:white;
        padding:18px;
        text-align:left;
        font-size:15px;
        font-weight:700;
    }

    td{
        padding:18px;
        border-bottom:1px solid #e5e7eb;
        font-size:15px;
    }

    tr:hover{
        background:#f8fafc;
    }

    .status{
        background:#dcfce7;
        color:#166534;
        padding:6px 12px;
        border-radius:20px;
        font-size:12px;
        font-weight:700;
        display:inline-block;
        text-transform:uppercase;
    }

    .status-refunded{
        background:#fee2e2;
        color:#991b1b;
    }

    .status-completed{
        background:#dcfce7;
        color:#166534;
    }

    .status-note{
        margin-top:6px;
        color:#64748b;
        font-size:12px;
        font-weight:700;
    }

    .print-btn{
        background:#16a34a;
        color:white;
        padding:10px 18px;
        border-radius:8px;
        text-decoration:none;
        font-size:14px;
        font-weight:600;
        display:inline-block;
        transition:0.2s;
    }

    .print-btn:hover{
        background:#15803d;
    }

    .pagination{
        margin-top:25px;

    }


.refund-btn{

    background:#dc2626;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
    font-weight:700;

}

.refund-btn:hover{

    background:#b91c1c;

}

.refunded-badge{

    background:#fee2e2;
    color:#991b1b;
    padding:8px 12px;
    border-radius:8px;
    font-weight:700;

}

.stock-restored-badge{

    background:#dcfce7;
    color:#166534;
    padding:8px 12px;
    border-radius:8px;
    font-weight:700;

}

.refund-meta{
    margin-top:6px;
    color:#64748b;
    font-size:12px;
    font-weight:600;
}

.sale-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.sale-actions form{
    margin:0;
}









/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

.pagination{
    margin-top:30px;
}

.pagination nav{
    display:flex;
    justify-content:center;
}

.pagination .flex{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.pagination span,
.pagination a{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:42px;
    height:42px;
    padding:0 14px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
    font-size:14px;
    transition:0.2s;
}

.pagination a{
    background:white;
    color:#1e293b;
    border:1px solid #d1d5db;
}

.pagination a:hover{
    background:#2563eb;
    color:white;
    border-color:#2563eb;
}

.pagination span[aria-current="page"] span{
    background:#2563eb;
    color:white;
    border:none;
}

.pagination svg{
    width:18px;
    height:18px;
}

.pagination p{
    margin-top:12px;
    text-align:center;
    color:#64748b;
    font-size:14px;
}

@media (max-width: 768px){
    .page-header{
        margin-bottom:18px;
    }

    .page-header h1{
        font-size:32px;
        line-height:1.05;
    }

    .page-header p{
        font-size:14px;
    }

    .summary-grid{
        grid-template-columns:1fr;
        gap:12px;
        margin-bottom:16px;
    }

    .summary-card{
        padding:18px;
        border-radius:18px;
    }

    .summary-card h1{
        font-size:32px;
    }

    .sales-filter-bar{
        padding:14px;
        border-radius:18px;
        margin-bottom:16px;
    }

    .sales-filter-form{
        display:grid;
        grid-template-columns:1fr;
        gap:10px;
    }

    .filter-select,
    .filter-input,
    .filter-btn{
        width:100%;
        min-width:0;
        border-radius:14px;
    }

    .table-wrapper{
        background:transparent;
        border-radius:0;
        overflow:visible;
        box-shadow:none;
    }

    .table-wrapper table,
    .table-wrapper thead,
    .table-wrapper tbody,
    .table-wrapper th,
    .table-wrapper td,
    .table-wrapper tr{
        display:block;
        width:100%;
    }

    .table-wrapper thead{
        display:none;
    }

    .table-wrapper tbody{
        display:grid;
        gap:12px;
    }

    .table-wrapper tr{
        overflow:hidden;
        border:1px solid #e2e8f0;
        border-radius:20px;
        background:white;
        box-shadow:0 10px 25px rgba(15,23,42,0.08);
    }

    .table-wrapper tr:hover{
        background:white;
    }

    .table-wrapper td{
        display:grid;
        grid-template-columns:minmax(92px, 34%) minmax(0, 1fr);
        align-items:center;
        gap:12px;
        padding:12px 14px;
        border-bottom:1px solid #f1f5f9;
        font-size:14px;
    }

    .table-wrapper td::before{
        content:attr(data-label);
        color:#64748b;
        font-size:11px;
        font-weight:900;
        letter-spacing:.04em;
        text-transform:uppercase;
    }

    .table-wrapper td:last-child{
        display:block;
        border-bottom:0;
        background:#f8fafc;
    }

    .table-wrapper td:last-child::before{
        display:block;
        margin-bottom:10px;
    }

    .sale-actions{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:10px;
    }

    .sale-actions form{
        width:100%;
    }

    .print-btn,
    .refund-btn,
    .refunded-badge,
    .stock-restored-badge{
        display:flex;
        min-height:44px;
        width:100%;
        align-items:center;
        justify-content:center;
        border-radius:14px;
        padding:10px 12px;
        text-align:center;
        font-size:13px;
    }

    .refund-meta{
        grid-column:1 / -1;
        margin-top:0;
        text-align:center;
    }
}








</style>

<div class="page-header">

    <h1>
        Sales History
    </h1>

    <p>
        Daily, weekly, monthly and yearly transaction reports
    </p>

</div>

{{-- SUMMARY SECTION --}}

<div class="summary-grid">

    <div class="summary-card net">

        <h3>
            Net Sales Amount
        </h3>

        <h1>
            {{ number_format($totalSales) }}
        </h1>

    </div>

    <div class="summary-card">

        <h3>
            Gross Sales
        </h3>

        <h1>
            {{ number_format($grossSales) }}
        </h1>

    </div>

    <div class="summary-card refund">

        <h3>
            Refunded Amount
        </h3>

        <h1>
            {{ number_format($refundedSales) }}
        </h1>

    </div>

    <div class="summary-card">

        <h3>
            Completed Transactions
        </h3>

        <h1>
            {{ $totalTransactions }}
        </h1>

    </div>

</div>

{{-- FILTER SECTION --}}

<div class="sales-filter-bar">

    <form
        method="GET"
        action="{{ route('sales.index') }}"
        class="sales-filter-form"
    >

        <select
            name="filter"
            class="filter-select"
        >

            <option
                value="all"
                {{ $filter == 'all' ? 'selected' : '' }}
            >
                All Time
            </option>

            <option
                value="daily"
                {{ $filter == 'daily' ? 'selected' : '' }}
            >
                Daily
            </option>

            <option
                value="weekly"
                {{ $filter == 'weekly' ? 'selected' : '' }}
            >
                Weekly
            </option>

            <option
                value="monthly"
                {{ $filter == 'monthly' ? 'selected' : '' }}
            >
                Monthly
            </option>

            <option
                value="yearly"
                {{ $filter == 'yearly' ? 'selected' : '' }}
            >
                Yearly
            </option>

        </select>

        <select
            name="department_id"
            class="filter-select"
        >
            <option value="">
                All Departments
            </option>

            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search invoice..."
            class="filter-input"
        >

        <button
            type="submit"
            class="filter-btn"
        >
            Filter Report
        </button>

    </form>

</div>

{{-- TABLE SECTION --}}

<div class="table-wrapper">

    <table>

        <thead>

            <tr>

                <th>ID</th>
                <th>Invoice</th>
                <th>Cashier</th>
                <th>Departments</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>

            </tr>

        </thead>

        <tbody>

            @forelse($sales as $sale)
                @php
                    $isRefunded = (bool) $sale->is_refunded || $sale->sale_status === 'REFUNDED';
                    $statusLabel = $isRefunded
                        ? 'REFUNDED'
                        : ($sale->sale_status ?: 'COMPLETED');
                @endphp

                <tr>

                    <td data-label="ID">
                        {{ $sale->id }}
                    </td>

                    <td data-label="Invoice">
                        {{ $sale->receipt_no ?? 'N/A' }}
                    </td>

                    <td data-label="Cashier">
                        {{ $sale->user?->name }}
                    </td>

                    <td data-label="Departments">
                        <div style="display:flex; gap:4px; flex-wrap:wrap;">
                            @foreach($sale->items->pluck('department.name')->filter()->unique() as $departmentName)
                                <span class="status" style="background:#eef2ff; color:#3730a3;">
                                    {{ $departmentName }}
                                </span>
                            @endforeach
                        </div>
                    </td>

                    <td data-label="Total">
                        {{ number_format($sale->grand_total) }}
                    </td>

                    <td data-label="Status">

                        <span class="status {{ $isRefunded ? 'status-refunded' : 'status-completed' }}">
                            {{ str_replace('_', ' ', $statusLabel) }}
                        </span>

                        @if($isRefunded)
                            <div class="status-note">
                                Stock restored
                            </div>
                        @endif

                    </td>

                    <td data-label="Date">
                        {{ $sale->created_at }}
                    </td>

                    <td data-label="Action">

<div class="sale-actions">

    <a
        href="{{ route('sales.print', $sale->id) }}"
        target="_blank"
        class="print-btn"
    >
        Print
    </a>

    @if($isRefunded)

        <span class="refunded-badge">
            Refunded
        </span>

        <span class="stock-restored-badge">
            Stock restored
        </span>

        @if($sale->refunded_at)
            <div class="refund-meta">
                {{ $sale->refunded_at->format('d/m/Y H:i') }}
            </div>
        @endif

    @elseif(auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'))

        <form
            action="{{ route('sales.refund', $sale->id) }}"
            method="POST"
            onsubmit="return confirm('Refund this sale?')"
        >

            @csrf

            <input
                type="hidden"
                name="refund_reason"
                value="Customer refund"
            >

            <button
                type="submit"
                class="refund-btn"
            >
                Refund
            </button>

        </form>

    @endif

</div>

</td>

                </tr>

            @empty

                <tr>

                    <td colspan="8">
                        No sales found
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination">

    {{ $sales->links() }}

</div>

@endsection
