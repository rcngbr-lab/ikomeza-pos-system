@extends('layouts.mobile-app')

@section('content')

<div class="p-4">

    <div class="mb-6">

        <h1 class="text-4xl font-black">

            Current Shift

        </h1>

        <p class="text-slate-500 mt-2">

            Cashier reconciliation overview.

        </p>

    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">

        <div class="bg-white rounded-3xl p-5 shadow-sm">

            <div class="text-slate-500 text-sm">
                Opening Cash
            </div>

            <div class="mt-3 text-3xl font-black text-green-600">

                {{ number_format($shift->opening_cash) }}

            </div>

        </div>

        <div class="bg-white rounded-3xl p-5 shadow-sm">

            <div class="text-slate-500 text-sm">
                Total Sales
            </div>

            <div class="mt-3 text-3xl font-black text-indigo-600">

                {{ number_format($totalSales) }}

            </div>

        </div>

    </div>

    <div class="bg-white rounded-3xl p-5 shadow-sm mb-6">

        <div class="text-lg font-bold mb-5">

            Payment Summary

        </div>

        <div class="space-y-4">

            <div class="flex justify-between">
                <span>Cash</span>
                <span>{{ number_format($cashSales) }}</span>
            </div>

            <div class="flex justify-between">
                <span>MoMo</span>
                <span>{{ number_format($momoSales) }}</span>
            </div>

            <div class="flex justify-between">
                <span>Visa</span>
                <span>{{ number_format($visaSales) }}</span>
            </div>

            <div class="flex justify-between">
                <span>Bank</span>
                <span>{{ number_format($bankSales) }}</span>
            </div>

        </div>

    </div>

    <div class="bg-white rounded-3xl p-5 shadow-sm">

        <div class="text-slate-500 text-sm">

            Expected Cash

        </div>

        <div class="mt-3 text-5xl font-black text-indigo-600">

            {{ number_format($expectedCash) }}

        </div>

        <form
            method="POST"
            action="{{ route('shifts.close') }}"
            class="mt-6"
        >

            @csrf

            <input
                type="number"
                name="closing_cash"
                required
                placeholder="Enter closing cash"
                class="w-full border border-slate-200
                       rounded-2xl px-5 py-4 mb-4"
            >

            <button
                type="submit"
                class="w-full bg-red-500 hover:bg-red-600
                       text-white font-bold py-4
                       rounded-2xl"
            >

                Close Shift

            </button>

        </form>

    </div>

</div>

@endsection
