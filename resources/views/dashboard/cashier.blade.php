@extends('layouts.app')

@section('content')

<div class="p-6">

    <!-- HEADER -->

    <div class="mb-8">

        <h1 class="text-3xl font-black">

            Cashier Dashboard

        </h1>

        <p class="text-gray-500 mt-2">

            Sales and cashier operations

        </p>

    </div>

    <!-- QUICK ACTIONS -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">

        <a
            href="{{ route('pos.index') }}"
            class="bg-black hover:bg-gray-800
                   text-white rounded-2xl
                   p-6 transition"
        >

            <div class="text-2xl font-black">

                POS

            </div>

            <div class="text-sm opacity-80 mt-2">

                Start sale

            </div>

        </a>

        <a
            href="{{ route('shifts.current') }}"
            class="bg-green-600 hover:bg-green-700
                   text-white rounded-2xl
                   p-6 transition"
        >

            <div class="text-2xl font-black">

                Shift

            </div>

            <div class="text-sm opacity-80 mt-2">

                Current shift

            </div>

        </a>

        <a
            href="{{ route('sales.index') }}"
            class="bg-blue-600 hover:bg-blue-700
                   text-white rounded-2xl
                   p-6 transition"
        >

            <div class="text-2xl font-black">

                Sales

            </div>

            <div class="text-sm opacity-80 mt-2">

                My receipts

            </div>

        </a>

        <a
            href="{{ route('requisitions.index') }}"
            class="bg-amber-500 hover:bg-amber-600
                   text-white rounded-2xl
                   p-6 transition"
        >

            <div class="text-2xl font-black">

                Request

            </div>

            <div class="text-sm opacity-80 mt-2">

                Stock approval

            </div>

        </a>

    </div>

    <!-- STATS -->

    <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">

        <div class="bg-white rounded-2xl shadow p-5">

            <div class="text-gray-500 text-sm">

                Transactions

            </div>

            <div class="text-3xl font-black mt-2">

                {{ $todayTransactions }}

            </div>

        </div>

        <div class="bg-white rounded-2xl shadow p-5">

            <div class="text-gray-500 text-sm">

                Revenue

            </div>

            <div class="text-3xl font-black mt-2">

                {{ number_format($todayRevenue) }} Frw

            </div>

        </div>

        <div class="bg-white rounded-2xl shadow p-5">

            <div class="text-gray-500 text-sm">

                Shift Status

            </div>

            <div class="text-2xl font-black mt-2 text-green-600">

                {{ $activeShift ? 'OPEN' : 'CLOSED' }}

            </div>

        </div>

        <div class="bg-white rounded-2xl shadow p-5">

            <div class="text-gray-500 text-sm">

                Expected Cash

            </div>

            <div class="text-3xl font-black mt-2">

                {{ number_format(

                    $expectedCash

                ) }} Frw

            </div>

        </div>

    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-8">

        <div class="bg-white rounded-2xl shadow p-6">

            <h2 class="text-2xl font-black mb-5">
                My Payment Methods
            </h2>

            <div class="space-y-4">
                @forelse($paymentBreakdown as $payment)
                    @php
                        $method = \App\Models\Sale::normalizePaymentMethod($payment->payment_method);
                    @endphp

                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <div class="font-bold">
                                {{ \App\Models\Sale::PAYMENT_METHOD_LABELS[$method] ?? $method }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $payment->count }} transactions
                            </div>
                        </div>

                        <div class="font-black">
                            {{ number_format($payment->total) }} Frw
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">
                        No payments recorded today
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">

            <h2 class="text-2xl font-black mb-5">
                Low Stock Alerts
            </h2>

            <div class="space-y-4">
                @forelse($lowStockProducts as $product)
                    <div class="flex items-center justify-between border-b pb-3">
                        <div class="font-bold">
                            {{ $product->name }}
                        </div>

                        <div class="text-sm font-black text-red-600">
                            {{ number_format($product->stock) }}
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">
                        No low stock alerts
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- RECENT SALES -->

    <div class="bg-white rounded-2xl shadow p-6">

        <div class="flex justify-between mb-6">

            <h2 class="text-2xl font-black">

                Recent Sales

            </h2>

            <a
                href="{{ route('sales.index') }}"
                class="text-blue-600 text-sm font-bold"
            >

                View All

            </a>

        </div>

        <div class="space-y-4">

            @forelse($recentSales as $sale)

                <div
                    class="flex items-center
                           justify-between
                           border-b pb-4"
                >

                    <div>

                        <div class="font-bold">

                            {{ $sale->receipt_no }}

                        </div>

                        <div class="text-sm text-gray-500">

                            {{ $sale->payment_method }}

                        </div>

                    </div>

                    <div class="font-black">

                        {{ number_format(

                            $sale->grand_total

                        ) }} Frw

                    </div>

                </div>

            @empty

                <div class="text-gray-500">

                    No recent sales

                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection
