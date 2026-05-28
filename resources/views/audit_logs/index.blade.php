@extends('layouts.app')

@section('content')

<style>

    .audit-container{
        padding: 25px;
    }

    .audit-card{
        background: #ffffff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .audit-title{
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #111827;
    }

    .audit-summary{
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .summary-box{
        background: #f3f4f6;
        padding: 18px;
        border-radius: 10px;
        min-width: 200px;
    }

    .summary-box h4{
        margin: 0;
        font-size: 14px;
        color: #6b7280;
    }

    .summary-box p{
        margin-top: 8px;
        font-size: 24px;
        font-weight: bold;
        color: #111827;
    }

    .table-responsive{
        overflow-x: auto;
    }

    .audit-table{
        width: 100%;
        border-collapse: collapse;
    }

    .audit-table thead{
        background: #111827;
        color: white;
    }

    .audit-table th,
    .audit-table td{
        padding: 14px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        font-size: 14px;
    }

    .audit-table tbody tr:hover{
        background: #f9fafb;
    }

    .badge{
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }

    .badge-create{
        background: #16a34a;
    }

    .badge-update{
        background: #2563eb;
    }

    .badge-delete{
        background: #dc2626;
    }

    .badge-login{
        background: #4f46e5;
    }

    .badge-logout{
        background: #ea580c;
    }

    .badge-default{
        background: #6b7280;
    }

    .pagination-wrapper{
        margin-top: 20px;
    }




/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

.pagination-wrapper{
    margin-top:30px;
    display:flex;
    justify-content:center;
}

.pagination-wrapper nav{
    width:100%;
    display:flex;
    justify-content:center;
}

.pagination-wrapper .flex{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.pagination-wrapper a,
.pagination-wrapper span{
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
    transition:0.2s ease;
}

.pagination-wrapper a{
    background:white;
    color:#1e293b;
    border:1px solid #d1d5db;
}

.pagination-wrapper a:hover{
    background:#2563eb;
    color:white;
    border-color:#2563eb;
}

.pagination-wrapper span[aria-current="page"] span{
    background:#2563eb;
    color:white;
    border:none;
}

.pagination-wrapper svg{
    width:18px;
    height:18px;
}

.pagination-wrapper p{
    margin-top:12px;
    text-align:center;
    color:#64748b;
    font-size:14px;
}





/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

.audit-filter-form{
    display:flex;
    gap:15px;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:25px;
}

.filter-select,
.filter-input{
    height:46px;
    padding:0 15px;
    border:1px solid #d1d5db;
    border-radius:10px;
    background:white;
    font-size:14px;
    min-width:220px;
}

.filter-select:focus,
.filter-input:focus{
    outline:none;
    border-color:#2563eb;
}

.filter-btn{
    height:46px;
    padding:0 24px;
    border:none;
    border-radius:10px;
    background:#16a34a;
    color:white;
    font-weight:700;
    cursor:pointer;
    transition:0.2s ease;
}

.filter-btn:hover{
    background:#15803d;
}













</style>

<div class="audit-container">

    <div class="audit-card">
<form
    method="GET"
    action="{{ route('audit.logs') }}"
    class="audit-filter-form"
>

    <select
        name="period"
        class="filter-select"
    >
        <option value="">
            All Time
        </option>

        <option
            value="daily"
            {{ request('period') == 'daily' ? 'selected' : '' }}
        >
            Daily
        </option>

        <option
            value="weekly"
            {{ request('period') == 'weekly' ? 'selected' : '' }}
        >
            Weekly
        </option>

        <option
            value="monthly"
            {{ request('period') == 'monthly' ? 'selected' : '' }}
        >
            Monthly
        </option>

        <option
            value="yearly"
            {{ request('period') == 'yearly' ? 'selected' : '' }}
        >
            Yearly
        </option>

    </select>

    <input
        type="text"
        name="search"
        class="filter-input"
        placeholder="Search user or action..."
        value="{{ request('search') }}"
    >

    <button
        type="submit"
        class="filter-btn"
    >
        Filter Logs
    </button>

</form>

        <div class="audit-summary">

            <div class="summary-box">

                <h4>Total Logs</h4>

                <p>

                    {{ $logs->total() }}

                </p>

            </div>

        </div>

        <div class="table-responsive">

            <table class="audit-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>User</th>

                        <th>Action</th>

                        <th>Model</th>

                        <th>Description</th>

                        <th>Date</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($logs as $log)

                        <tr>

                            <td>

                                {{ $log->id }}

                            </td>

                            <td>

                                {{ $log->user->name ?? 'N/A' }}

                            </td>

                            <td>

                                @php

                                    $class = 'badge-default';

                                    if(str_contains(strtolower($log->action), 'create')){
                                        $class = 'badge-create';
                                    }
                                    elseif(str_contains(strtolower($log->action), 'update')){
                                        $class = 'badge-update';
                                    }
                                    elseif(str_contains(strtolower($log->action), 'delete')){
                                        $class = 'badge-delete';
                                    }
                                    elseif(str_contains(strtolower($log->action), 'login')){
                                        $class = 'badge-login';
                                    }
                                    elseif(str_contains(strtolower($log->action), 'logout')){
                                        $class = 'badge-logout';
                                    }

                                @endphp

                                <span class="badge {{ $class }}">

                                    {{ $log->action }}

                                </span>

                            </td>

                            <td>

                                {{ $log->model ?? 'N/A' }}

                            </td>

                            <td>

                                {{ $log->details ?? '-' }}

                            </td>

                            <td>

                                {{ $log->created_at }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6">

                                No audit logs found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="pagination-wrapper">

          <div class="pagination-wrapper">

    {{ $logs->onEachSide(1)->links() }}

</div>

        </div>

    </div>

</div>

@endsection