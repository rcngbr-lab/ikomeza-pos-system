@extends('layouts.app')

@section('content')

@php
    $paymentRows = collect($paymentBreakdown);
    $totalSalesValue = (float) $totalSales;
    $expectedCashValue = (float) $expectedCash;
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-black tracking-tight text-slate-950 lg:text-4xl">
                    Current Shift
                </h1>

                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-700">
                    {{ $shift->status }}
                </span>
            </div>

            <p class="mt-2 text-sm font-medium text-slate-500">
                {{ $shift->shift_code ?? 'Shift #' . $shift->id }} opened {{ optional($shift->opened_at)->format('M d, Y H:i') }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('pos.index') }}"
                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700"
            >
                POS Terminal
            </a>

            <a
                href="{{ route('shifts.history') }}"
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
            >
                Shift History
            </a>
        </div>
    </div>

    @if(session('success') || session('error') || $errors->any())
        <div class="space-y-3">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Opening Cash</p>
            <p class="mt-3 text-3xl font-black text-slate-950">
                {{ number_format((float) $shift->opening_cash) }}
                <span class="text-sm font-bold text-slate-400">Frw</span>
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Total Sales</p>
            <p class="mt-3 text-3xl font-black text-indigo-600">
                {{ number_format($totalSalesValue) }}
                <span class="text-sm font-bold text-slate-400">Frw</span>
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Transactions</p>
            <p class="mt-3 text-3xl font-black text-slate-950">
                {{ number_format($transactionCount) }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Expected Cash</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">
                {{ number_format($expectedCashValue) }}
                <span class="text-sm font-bold text-slate-400">Frw</span>
            </p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Payment Summary</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500">Shift revenue by payment channel</p>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @foreach($paymentRows as $method => $row)
                    @php
                        $amount = (float) $row['amount'];
                        $percent = $totalSalesValue > 0 ? min(100, ($amount / $totalSalesValue) * 100) : 0;
                    @endphp

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-[11px] font-black text-slate-600">
                                    {{ str($row['label'])->substr(0, 2)->upper() }}
                                </span>
                                <span class="font-bold text-slate-800">{{ $row['label'] }}</span>
                            </div>
                            <span class="font-black text-slate-950">{{ number_format($amount) }} Frw</span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-indigo-600"
                                style="width: {{ $percent }}%;"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div
            x-data="{ expectedCash: {{ $expectedCashValue }}, closingCash: '' }"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6"
        >
            <h2 class="text-xl font-black text-slate-950">Close Shift</h2>
            <p class="mt-1 text-sm font-medium text-slate-500">Cash drawer reconciliation</p>

            <div class="mt-6 rounded-2xl bg-slate-50 p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-500">Expected Cash</span>
                    <span class="text-lg font-black text-emerald-600">{{ number_format($expectedCashValue) }} Frw</span>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-500">Difference Preview</span>
                    <span
                        class="text-lg font-black"
                        :class="Number(closingCash || 0) - expectedCash < 0 ? 'text-rose-600' : 'text-emerald-600'"
                        x-text="new Intl.NumberFormat().format(Number(closingCash || 0) - expectedCash) + ' Frw'"
                    ></span>
                </div>
            </div>

            <form method="POST" action="{{ route('shifts.close') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">
                        Closing Cash
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="closing_cash"
                        x-model="closingCash"
                        required
                        placeholder="Enter counted cash"
                        class="w-full rounded-2xl border border-slate-300 px-5 py-4 text-base font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-rose-600 px-5 py-4 text-base font-black text-white transition hover:bg-rose-700"
                >
                    Close Shift
                </button>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between lg:p-6">
            <div>
                <h2 class="text-xl font-black text-slate-950">Recent Receipts</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Latest completed sales in this shift</p>
            </div>

            <a
                href="{{ route('sales.index') }}"
                class="text-sm font-black text-indigo-600 hover:text-indigo-700"
            >
                View Sales
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-5 py-4">Receipt</th>
                        <th class="px-5 py-4">Customer</th>
                        <th class="px-5 py-4">Payment</th>
                        <th class="px-5 py-4">Total</th>
                        <th class="px-5 py-4">Time</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentSales as $sale)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black text-slate-950">
                                {{ $sale->receipt_no }}
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $sale->customer_name ?: 'Walk-in' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                    {{ $sale->paymentMethodLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-black text-slate-950">
                                {{ number_format((float) $sale->grand_total) }} Frw
                            </td>
                            <td class="px-5 py-4 text-slate-500">
                                {{ $sale->created_at->format('H:i') }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('sales.receipt', $sale) }}"
                                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-black text-white transition hover:bg-slate-800"
                                >
                                    Receipt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center font-bold text-slate-500">
                                No sales recorded on this shift yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
