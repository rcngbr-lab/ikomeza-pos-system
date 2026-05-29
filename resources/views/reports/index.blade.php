@extends('layouts.app')

@section('content')

<style>

/*
|--------------------------------------------------------------------------
| GLOBAL
|--------------------------------------------------------------------------
*/

body {

    zoom: 90%;
}

/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

    .report-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 10px;
    }

    .report-card {

        padding: 12px;
        border-radius: 14px;
    }

    .report-card h2 {

        font-size: 18px;
    }

    .report-card p {

        font-size: 11px;
    }

    .compact-title {

        font-size: 28px;
    }

    .small-btn {

        padding: 10px 14px;
        font-size: 12px;
        border-radius: 12px;
    }

    .table-wrapper {

        overflow-x: auto;
    }

    table {

        font-size: 12px;
    }

    th,
    td {

        padding: 10px;
    }
}

/*
|--------------------------------------------------------------------------
| PRINT
|--------------------------------------------------------------------------
*/

@media print {

    body {

        background: white !important;
        zoom: 80%;
    }

    aside,
    nav,
    header,
    form,
    button,
    .sidebar,
    .topbar,
    .no-print {

        display: none !important;
    }

    .print-container {

        width: 100%;
        margin: 0;
        padding: 0;
    }

    .print-header {

        display: block !important;
        margin-bottom: 20px;
    }

    .report-grid {

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 8px;
    }

    .report-card {

        padding: 10px;
        border: 1px solid #ddd;
        box-shadow: none;
    }

    .report-card h2 {

        font-size: 16px;
    }

    .report-card p {

        font-size: 10px;
    }

    table {

        width: 100%;
        font-size: 10px;
    }

    th,
    td {

        padding: 6px;
    }

    .page-break {

        page-break-inside: avoid;
    }
}

.print-header {

    display: none;
}

</style>

<!-- PRINT HEADER -->

<div class="print-header">

    <h1
        style="
            font-size:32px;
            font-weight:900;
        "
    >
        BAR POS REPORT
    </h1>

    <p>
        Generated:
        {{ now()->format('d/m/Y H:i') }}
    </p>

    <hr style="margin-top:10px;">

</div>

<div class="print-container p-4 md:p-6">

    <!-- HEADER -->

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">

        <div>

            <h1 class="compact-title text-3xl md:text-4xl font-black text-slate-900">
                Reports
            </h1>

            <p class="text-slate-500 text-sm mt-1">
                Sales analytics and transaction history
            </p>

        </div>

        <button
            onclick="window.print()"
            class="
                small-btn
                bg-green-600
                hover:bg-green-700
                text-white
                px-4
                py-2
                rounded-xl
                font-bold
            "
        >
            PRINT REPORT
        </button>

    </div>

    <!-- FILTER -->

   <!-- FILTER -->

<form
    method="GET"
    action="{{ route('reports.index') }}"
    class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-5 no-print"
>

    <!-- SEARCH -->

    <input
        type="text"
        name="search"
        value="{{ request('search') }}"
        placeholder="Search receipt, cashier or payment..."
        class="
            border
            rounded-xl
            px-4
            py-3
            bg-white
        "
    >

    <!-- FILTER -->

    <select
        name="filter"
        class="
            border
            rounded-xl
            px-4
            py-3
            bg-white
        "
    >

        <option value="daily" {{ $filter == 'daily' ? 'selected' : '' }}>
            Daily
        </option>

        <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>
            Weekly
        </option>

        <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>
            Monthly
        </option>

        <option value="yearly" {{ $filter == 'yearly' ? 'selected' : '' }}>
            Yearly
        </option>

        <option value="range" {{ $filter == 'range' ? 'selected' : '' }}>
            Custom Range
        </option>

    </select>

    <select
        name="department_id"
        class="
            border
            rounded-xl
            px-4
            py-3
            bg-white
        "
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

    <!-- FROM DATE -->

    <input
        type="date"
        name="start_date"
        value="{{ request('start_date') }}"
        class="
            border
            rounded-xl
            px-4
            py-3
            bg-white
        "
    >

    <!-- TO DATE -->

    <input
        type="date"
        name="end_date"
        value="{{ request('end_date') }}"
        class="
            border
            rounded-xl
            px-4
            py-3
            bg-white
        "
    >

    <!-- BUTTON -->

    <button
        type="submit"
        class="
            bg-slate-900
            text-white
            rounded-xl
            px-4
            py-3
            font-bold
        "
    >
        APPLY
    </button>

</form>
    <!-- STATS -->

    <div class="report-grid grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-5">

        <!-- TOTAL -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                Revenue
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-green-600">
                {{ number_format($totalRevenue) }}
            </h2>

        </div>

        <!-- TRANSACTIONS -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                Transactions
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2">
                {{ $totalTransactions }}
            </h2>

        </div>

        <!-- CASH -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                Cash
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-blue-600">
                {{ number_format($cashSales) }}
            </h2>

        </div>

        <!-- MOMO -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                MOMO
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-yellow-600">
                {{ number_format($momoSales) }}
            </h2>

        </div>

        <!-- AIRTEL -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                Airtel
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-red-600">
                {{ number_format($airtelSales) }}
            </h2>

        </div>

        <!-- VISA -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                VISA
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-indigo-600">
                {{ number_format($visaSales) }}
            </h2>

        </div>

        <!-- MASTER CARD -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                Master
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-purple-600">
                {{ number_format($masterSales) }}
            </h2>

        </div>

        <!-- BANK -->

        <div class="report-card bg-white rounded-2xl shadow-sm border p-3">

            <p class="text-slate-500 text-xs">
                Bank
            </p>

            <h2 class="text-xl md:text-2xl font-black mt-2 text-emerald-600">
                {{ number_format($bankSales) }}
            </h2>

        </div>

    </div>

    <div class="grid gap-3 md:grid-cols-2 mb-5">
        @forelse($departmentBreakdown as $departmentMetric)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-slate-500">
                            {{ $departmentMetric->department->name ?? 'Unassigned' }} Department
                        </p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">
                            {{ number_format($departmentMetric->revenue) }} Frw
                        </h2>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ ($departmentMetric->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                        {{ $departmentMetric->department->code ?? 'N/A' }}
                    </span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-slate-500">Profit</p>
                        <p class="font-black text-emerald-600">{{ number_format($departmentMetric->profit) }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-slate-500">Units Sold</p>
                        <p class="font-black text-slate-950">{{ number_format($departmentMetric->units_sold) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm font-bold text-slate-500 md:col-span-2">
                No department sales in this period.
            </div>
        @endforelse
    </div>

    

    <!-- SALES TABLE -->

    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden page-break">

        <div class="p-4 border-b">

            <h2 class="text-xl font-black">
                Recent Sales
            </h2>

        </div>

        <div class="table-wrapper overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-900 text-white">

                    <tr>

                        <th class="p-3 text-left">
                            Receipt
                        </th>

                        <th class="p-3 text-left">
                            Cashier
                        </th>

                        <th class="p-3 text-left">
                            Payment
                        </th>

                        <th class="p-3 text-left">
                            Departments
                        </th>

                        <th class="p-3 text-left">
                            Amount
                        </th>

                        <th class="p-3 text-left">
                            Date
                        </th>
                        <th class="p-3 text-left">
                         Action
                           </th>


                    </tr>

                </thead>

                <tbody>

                    @forelse($sales as $sale)

                        <tr class="border-b hover:bg-slate-50">

                            <td class="p-3 font-semibold">
                                {{ $sale->receipt_no }}
                            </td>

                            <td class="p-3">
                                {{ $sale->user->name ?? 'N/A' }}
                            </td>

                            <td class="p-3">
                                {{ $sale->payment_method }}
                            </td>

                            <td class="p-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($sale->items->pluck('department.name')->filter()->unique() as $departmentName)
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-black text-slate-700">
                                            {{ $departmentName }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="p-3 font-bold text-green-600">
                                {{ number_format($sale->grand_total) }} Frw
                            </td>

                            <td class="p-3 text-slate-500">
                                {{ $sale->created_at->format('d/m/Y H:i') }}
                            </td>


                          <td class="p-4 text-center">

    <a
        href="{{ route('sales.receipt', $sale->id) }}"
        target="_blank"
        class="inline-flex items-center justify-center
               px-3 py-1.5
               rounded-lg
               bg-green-600
               hover:bg-green-700
               text-white
               text-xs
               font-bold
               transition"
    >

        PRINT

    </a>

</td>




                        </tr>
                        

                    @empty

                        <tr>

                            <td colspan="7" class="p-6 text-center text-slate-500">

                                No sales found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->

        <div class="p-4 border-t bg-slate-50 no-print">

            {{ $sales->links() }}

        </div>

    </div>

</div>

@endsection
