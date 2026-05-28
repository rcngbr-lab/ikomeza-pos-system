@extends('layouts.app')

@section('content')

<div class="p-6">

    <!-- HEADER -->

    <div class="mb-8">

        <h1 class="text-4xl font-black">

            Manager Dashboard

        </h1>

        <p class="text-gray-500 mt-2">

            Operations, inventory and sales monitoring

        </p>

    </div>

    <!-- STATS -->

    <div
        class="grid grid-cols-2
               md:grid-cols-4
               gap-5 mb-8"
    >

        <div class="bg-white rounded-2xl shadow p-5">

            <div class="text-gray-500 text-sm">

                Today's Revenue

            </div>

            <div class="text-3xl font-black mt-2">

                {{ number_format($todayRevenue) }} Frw

            </div>

        </div>

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

                Low Stock Products

            </div>

            <div class="text-3xl font-black mt-2 text-red-600">

                {{ $lowStock->count() }}

            </div>

        </div>

        <div class="bg-white rounded-2xl shadow p-5">

            <div class="text-gray-500 text-sm">

                Stock Status

            </div>

            <div class="text-2xl font-black mt-2 text-green-600">

                Active

            </div>

        </div>

    </div>

    <!-- QUICK ACTIONS -->

    <div
        class="bg-white rounded-2xl
               shadow p-6 mb-8"
    >

        <h2 class="text-2xl font-black mb-5">

            Quick Actions

        </h2>

        <div class="flex flex-wrap gap-4">

            <a
                href="{{ route('products.index') }}"
                class="bg-black hover:bg-gray-800
                       text-white text-sm font-bold
                       px-5 py-3 rounded-xl"
            >

                Products

            </a>

            <a
                href="{{ route('inventory.index') }}"
                class="bg-blue-600 hover:bg-blue-700
                       text-white text-sm font-bold
                       px-5 py-3 rounded-xl"
            >

                Stock Logs

            </a>

            <a
                href="{{ route('reports.index') }}"
                class="bg-green-600 hover:bg-green-700
                       text-white text-sm font-bold
                       px-5 py-3 rounded-xl"
            >

                Reports

            </a>

            <a
                href="{{ route('shifts.history') }}"
                class="bg-purple-600 hover:bg-purple-700
                       text-white text-sm font-bold
                       px-5 py-3 rounded-xl"
            >

                Shift History

            </a>

        </div>

    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-8">

        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-2xl font-black mb-5">
                Cashier Performance Today
            </h2>

            <div class="space-y-4">
                @forelse($cashierPerformance as $cashier)
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <div class="font-bold">
                                {{ $cashier->name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $cashier->transactions_today }} transactions
                            </div>
                        </div>

                        <div class="font-black">
                            {{ number_format($cashier->revenue_today ?? 0) }} Frw
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">
                        No cashier sales today
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-2xl font-black mb-5">
                Payment Breakdown
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
                        No payment data yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-8">

        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-black mb-4">
                Shift Differences
            </h2>

            <div class="space-y-3">
                @forelse($shiftDifferences as $shift)
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <div class="font-bold">
                                {{ $shift->user?->name ?? 'Unassigned' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $shift->shift_code }}
                            </div>
                        </div>

                        <div class="font-black {{ (float) $shift->difference === 0.0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($shift->difference) }}
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">
                        No closed shifts yet
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-black mb-4">
                Pending Refunds
            </h2>

            <div class="space-y-3">
                @forelse($pendingRefunds as $refund)
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <div class="font-bold">
                                {{ $refund->sale?->receipt_no ?? 'Refund' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $refund->user?->name ?? 'Unknown' }}
                            </div>
                        </div>

                        <div class="font-black">
                            {{ number_format($refund->amount) }} Frw
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">
                        No pending refunds
                    </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-black mb-4">
                Inventory Movements
            </h2>

            <div class="space-y-3">
                @forelse($recentMovements as $movement)
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <div class="font-bold">
                                {{ $movement->product?->name ?? 'Product' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $movement->type }}
                            </div>
                        </div>

                        <div class="font-black">
                            {{ number_format($movement->quantity) }}
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500">
                        No inventory movement
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- LOW STOCK -->

    <div
        class="bg-white rounded-2xl
               shadow p-6"
    >

        <div class="flex justify-between mb-6">

            <h2 class="text-2xl font-black">

                Low Stock Products

            </h2>

            <a
                href="{{ route('products.index') }}"
                class="text-blue-600 text-sm font-bold"
            >

                View Products

            </a>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead>

                    <tr class="border-b">

                        <th class="text-left py-3">

                            Product

                        </th>

                        <th class="text-left py-3">

                            Current Stock

                        </th>

                        <th class="text-left py-3">

                            Alert Level

                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($lowStock as $product)

                        <tr class="border-b">

                            <td class="py-3 font-bold">

                                {{ $product->name }}

                            </td>

                            <td class="py-3 text-red-600 font-bold">

                                {{ $product->stock }}

                            </td>

                            <td class="py-3">

                                {{ $product->alert_stock }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="3"
                                class="py-5 text-center
                                       text-gray-500"
                            >

                                No low stock products

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
