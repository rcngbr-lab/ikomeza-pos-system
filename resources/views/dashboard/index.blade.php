@extends('layouts.mobile-app')

@section('content')

<div class="p-4">

    <div class="mb-6">

        <h1 class="text-4xl font-black text-slate-900">

            Hello,
            {{ auth()->user()->name }} 👋

        </h1>

        <p class="text-slate-500 mt-2">

            Today business overview.

        </p>

    </div>

    <div class="grid grid-cols-2 gap-4">

        <div class="bg-white rounded-3xl p-5 shadow-sm">

            <div class="text-slate-500 text-sm">
                Completed Sales
            </div>

            <div class="mt-3 text-4xl font-black text-indigo-600">

                {{ \App\Models\Sale::revenueBearing()->count() }}

            </div>

        </div>

        <div class="bg-white rounded-3xl p-5 shadow-sm">

            <div class="text-slate-500 text-sm">
                Net Revenue
            </div>

            <div class="mt-3 text-3xl font-black text-green-600">

                {{ number_format(
                    \App\Models\Sale::revenueBearing()->sum('grand_total')
                ) }}

            </div>

        </div>

        <div class="bg-white rounded-3xl p-5 shadow-sm">

            <div class="text-slate-500 text-sm">
                Products
            </div>

            <div class="mt-3 text-4xl font-black text-orange-500">

                {{ \App\Models\Product::count() }}

            </div>

        </div>

        <div class="bg-white rounded-3xl p-5 shadow-sm">

            <div class="text-slate-500 text-sm">
                Shift
            </div>

            <div class="mt-3 text-3xl font-black text-blue-600">

                OPEN

            </div>

        </div>

    </div>

    <div class="mt-8 bg-white rounded-3xl p-5 shadow-sm">

        <div class="flex items-center justify-between mb-5">

            <h2 class="text-xl font-bold">

                Payment Summary

            </h2>

            <a
                href="{{ route('reports.index') }}"
                class="text-indigo-600 text-sm font-semibold"
            >
                View All
            </a>

        </div>

        <div class="space-y-5">

            <div>

                <div class="flex justify-between mb-2">

                    <span>Cash</span>

                    <span>48%</span>

                </div>

                <div class="h-3 bg-slate-200 rounded-full overflow-hidden">

                    <div class="h-full bg-green-500 w-[48%]"></div>

                </div>

            </div>

            <div>

                <div class="flex justify-between mb-2">

                    <span>MoMo</span>

                    <span>24%</span>

                </div>

                <div class="h-3 bg-slate-200 rounded-full overflow-hidden">

                    <div class="h-full bg-yellow-400 w-[24%]"></div>

                </div>

            </div>

            <div>

                <div class="flex justify-between mb-2">

                    <span>Card</span>

                    <span>18%</span>

                </div>

                <div class="h-3 bg-slate-200 rounded-full overflow-hidden">

                    <div class="h-full bg-indigo-500 w-[18%]"></div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
@extends('layouts.mobile-app')

@section('content')

<div class="p-4">

    <!-- SEARCH -->

    <div class="mb-5">

        <input
            type="text"
            placeholder="Search products..."
            class="w-full bg-white rounded-2xl
                   px-5 py-4 border border-slate-200
                   focus:outline-none focus:ring-2
                   focus:ring-indigo-500"
        >

    </div>

    <!-- CATEGORY TABS -->

    <div class="flex gap-3 overflow-x-auto pb-3 mb-6">

        <button class="bg-indigo-600 text-white
                       px-5 py-2 rounded-full
                       text-sm font-semibold whitespace-nowrap">

            All

        </button>

        <button class="bg-white px-5 py-2 rounded-full
                       text-sm font-semibold whitespace-nowrap">

            Beer

        </button>

        <button class="bg-white px-5 py-2 rounded-full
                       text-sm font-semibold whitespace-nowrap">

            Wine

        </button>

        <button class="bg-white px-5 py-2 rounded-full
                       text-sm font-semibold whitespace-nowrap">

            Soft Drinks

        </button>

    </div>

    <!-- PRODUCTS -->

    <div class="grid grid-cols-2 gap-4">

        @foreach($products as $product)

            <div class="bg-white rounded-3xl p-4 shadow-sm">

                <div class="h-28 rounded-2xl bg-slate-100
                            flex items-center justify-center mb-4">

                    <span class="text-5xl">
                        🍺
                    </span>

                </div>

                <div class="font-bold text-slate-900 text-lg">

                    {{ $product->name }}

                </div>

                <div class="text-slate-500 text-sm mt-1">

                    Stock:
                    {{ $product->stock }}

                </div>

                <div class="mt-3 flex items-center justify-between">

                    <div class="text-indigo-600 font-black text-xl">

                        {{ number_format($product->selling_price) }}

                    </div>

                    <form
                        method="POST"
                        action="{{ route('pos.add') }}"
                    >

                        @csrf

                        <input
                            type="hidden"
                            name="product_id"
                            value="{{ $product->id }}"
                        >

                        <button
                            type="submit"
                            class="h-12 w-12 rounded-2xl
                                   bg-indigo-600 text-white
                                   text-2xl font-bold"
                        >

                            +

                        </button>

                    </form>

                </div>

            </div>

        @endforeach

    </div>

    <!-- FLOATING CART -->

    <div class="fixed bottom-24 left-4 right-4">

        <div class="bg-indigo-600 rounded-3xl
                    px-5 py-4 shadow-2xl">

            <div class="flex items-center justify-between">

                <div>

                    <div class="text-white text-sm">

                        {{ count(session('cart', [])) }} Items

                    </div>

                    <div class="text-white font-black text-2xl">

                        {{ number_format($cartTotal ?? 0) }} Frw

                    </div>

                </div>

                <a
                    href="#cartDrawer"
                    class="bg-white text-indigo-600
                           px-5 py-3 rounded-2xl
                           font-bold"
                >

                    View Cart

                </a>

            </div>

        </div>

    </div>

</div>

@endsection
