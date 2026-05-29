@extends('layouts.app')

@section('content')

<div class="space-y-6">

    <!-- HEADER -->

    <div class="flex items-center justify-between">

        <h1 class="text-3xl font-black">
            Products
        </h1>

        <a
            href="{{ route('products.create') }}"
            class="
                bg-blue-600
                hover:bg-blue-700
                text-white
                px-5
                py-4
                rounded-2xl
                font-bold
                transition
            "
        >
            Add Product
        </a>

    </div>

    <form method="GET" action="{{ route('products.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[220px_1fr_auto]">
            <select name="department_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold">
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>

            <div class="hidden md:block"></div>

            <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white">
                Filter
            </button>
        </div>
    </form>

    <!-- PRODUCTS TABLE -->

    <div
        class="
            bg-white
            rounded-3xl
            shadow-sm
            overflow-x-auto
        "
    >

        <table class="w-full min-w-[700px]">

            <thead class="bg-slate-900 text-white">

                <tr>

                    <th class="text-left p-4">
                        Product
                    </th>

                    <th class="text-left p-4">
                        Barcode
                    </th>

                    <th class="text-left p-4">
                        Price
                    </th>

                    <th class="text-left p-4">
                        Stock
                    </th>

                    <th class="text-left p-4">
                        Category
                    </th>

                    <th class="text-left p-4">
                        Department
                    </th>

                    <th class="text-left p-4">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($products as $product)

                    <tr class="border-b hover:bg-slate-50">

                        <!-- PRODUCT -->

                        <td class="p-4 font-semibold">

                            {{ $product->name }}

                        </td>

                        <!-- BARCODE -->

                        <td class="p-4 text-slate-600">

                            {{ $product->barcode ?? 'N/A' }}

                        </td>

                        <!-- PRICE -->

                        <td class="p-4 font-bold text-blue-600">

                            {{ number_format((float) $product->selling_price, 2) }}

                        </td>

                        <!-- STOCK -->

                        <td class="p-4">

                            @if($product->stock <= $product->alert_stock)

                                <span
                                    class="
                                        bg-red-100
                                        text-red-700
                                        px-3
                                        py-1
                                        rounded-full
                                        text-sm
                                        font-bold
                                    "
                                >
                                    Low:
                                    {{ $product->stock }}
                                </span>

                            @else

                                <span
                                    class="
                                        bg-green-100
                                        text-green-700
                                        px-3
                                        py-1
                                        rounded-full
                                        text-sm
                                        font-bold
                                    "
                                >
                                    {{ $product->stock }}
                                </span>

                            @endif

                        </td>

                        <!-- CATEGORY -->

                        <td class="p-4">

                            {{ $product->category->name ?? 'N/A' }}

                        </td>

                        <td class="p-4">

                            <span class="rounded-full px-3 py-1 text-xs font-black {{ ($product->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $product->department->name ?? 'Unassigned' }}
                            </span>

                        </td>

                        <!-- ACTIONS -->

                        <td class="p-4">

    <div class="flex gap-2">

        <!-- SELL -->

        <a
            href="{{ route('pos.index', [
                'product' => $product->id
            ]) }}"
            class="
                bg-green-600
                hover:bg-green-700
                text-white
                px-4
                py-2
                rounded-xl
                text-sm
                font-bold
            "
        >
            Sell
        </a>

        <!-- ADJUST -->

        <a
    href="{{ route('products.adjust', $product->id) }}"
    class="
        bg-orange-500
        hover:bg-orange-600
        text-white
        px-4
        py-2
        rounded-xl
        text-sm
        font-bold
    "
>

    Adjust Stock

</a>

    </div>

</td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="
                                p-6
                                text-center
                                text-slate-500
                            "
                        >

                            No products found

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection
