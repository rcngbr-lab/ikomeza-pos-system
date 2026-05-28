@extends('layouts.app')

@section('content')

<div class="p-6">

    <div class="max-w-7xl mx-auto">

        <div class="mb-8">

            <h1 class="text-4xl font-bold text-slate-900">

                Welcome back,
                {{ auth()->user()->name }}

            </h1>

            <p class="text-slate-500 mt-2">

                Here's what's happening in your business today.

            </p>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- PRODUCTS -->

            <div class="bg-white rounded-2xl p-6 shadow-sm">

                <div class="text-slate-500 text-sm">

                    Products

                </div>

                <div class="mt-4 text-4xl font-bold text-slate-900">

                    {{ \App\Models\Product::count() }}

                </div>

            </div>

            <!-- SALES -->

            <div class="bg-white rounded-2xl p-6 shadow-sm">

                <div class="text-slate-500 text-sm">

                    Sales

                </div>

                <div class="mt-4 text-4xl font-bold text-slate-900">

                    {{ \App\Models\Sale::count() }}

                </div>

            </div>

            <!-- REVENUE -->

            <div class="bg-white rounded-2xl p-6 shadow-sm">

                <div class="text-slate-500 text-sm">

                    Revenue

                </div>

                <div class="mt-4 text-4xl font-bold text-green-600">

                    {{ number_format(
                        \App\Models\Sale::sum('grand_total')
                    ) }} Frw

                </div>

            </div>

            <!-- OPEN SHIFT -->

            <div class="bg-white rounded-2xl p-6 shadow-sm">

                <div class="text-slate-500 text-sm">

                    Current Shift

                </div>

                <div class="mt-4">

                    @php

                        $shift = \App\Models\Shift::where(
                            'user_id',
                            auth()->id()
                        )

                        ->where(
                            'is_open',
                            true
                        )

                        ->latest()

                        ->first();

                    @endphp

                    @if($shift)

                        <span class="text-green-600 font-bold">

                            OPEN

                        </span>

                    @else

                        <span class="text-red-600 font-bold">

                            CLOSED

                        </span>

                    @endif

                </div>

            </div>

        </div>

        <!-- QUICK ACTIONS -->

       <!-- QUICK ACTIONS -->

@if(
    in_array(
        auth()->user()->role,
        ['admin', 'manager']
    )
)

    <!-- MANAGER / ADMIN -->

    <div class="
        mt-10
        grid
        grid-cols-1
        md:grid-cols-2
        lg:grid-cols-3
        gap-6
    ">

        <a
            href="{{ route('shifts.current') }}"
            class="
                bg-blue-600
                hover:bg-blue-700
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Current Shift

        </a>

        <a
            href="{{ route('pos.index') }}"
            class="
                bg-green-600
                hover:bg-green-700
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Open POS

        </a>

        <a
            href="{{ route('reports.index') }}"
            class="
                bg-slate-900
                hover:bg-black
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Reports

        </a>

        <a
            href="{{ route('products.index') }}"
            class="
                bg-purple-600
                hover:bg-purple-700
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Products

        </a>

        <a
            href="{{ route('sales.index') }}"
            class="
                bg-red-600
                hover:bg-red-700
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Sales

        </a>

        <a
            href="{{ route('stock.movements') }}"
            class="
                bg-orange-500
                hover:bg-orange-600
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Stock Logs

        </a>

    </div>

@else

    <!-- CASHIER -->

    <div class="
        mt-10
        grid
        grid-cols-1
        md:grid-cols-2
        gap-6
    ">

        <a
            href="{{ route('shifts.current') }}"
            class="
                bg-blue-600
                hover:bg-blue-700
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Current Shift

        </a>

        <a
            href="{{ route('pos.index') }}"
            class="
                bg-green-600
                hover:bg-green-700
                text-white
                p-6
                rounded-2xl
                font-bold
                text-center
                transition
            "
        >

            Open POS

        </a>

    </div>

@endif

    </div>

</div>

@endsection