@extends('layouts.app')

@section('content')

<div class="space-y-8 w-full">

    <!-- PAGE HEADER -->

    <div
        class="
            flex
            flex-col
            md:flex-row
            md:items-center
            md:justify-between
            gap-4
        "
    >

        <div>

            <h1
                class="
                    text-3xl
                    md:text-4xl
                    font-black
                    text-slate-900
                "
            >
                Edit Product
            </h1>

            <p class="text-slate-500 mt-2">

                Update inventory product information

            </p>

        </div>

    </div>

    <!-- VALIDATION ERRORS -->

    @if ($errors->any())

        <div
            class="
                bg-red-100
                border
                border-red-300
                text-red-700
                px-5
                py-4
                rounded-2xl
            "
        >

            <ul class="space-y-1">

                @foreach ($errors->all() as $error)

                    <li>
                        • {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif

    <!-- FORM CARD -->

    <div
        class="
            bg-white
            rounded-3xl
            shadow-sm
            p-6
            md:p-8
        "
    >

        <form
            method="POST"
            action="{{ route('products.update', $product->id) }}"
            class="space-y-8"
        >

            @csrf
            @method('PUT')

            <!-- GRID -->

            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    gap-6
                "
            >

                <!-- PRODUCT NAME -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Product Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name) }}"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        required
                    >

                </div>

                <!-- BARCODE -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Barcode
                    </label>

                    <input
                        type="text"
                        name="barcode"
                        value="{{ old('barcode', $product->barcode) }}"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                    >

                </div>

                <!-- CATEGORY -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Category
                    </label>

                    <select
                        name="category_id"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        required
                    >

                        @foreach($categories as $category)

                            <option
                                value="{{ $category->id }}"
                                {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}
                            >

                                {{ $category->name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <!-- COST PRICE -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Cost Price
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="buy_price"
                        value="{{ old('buy_price', $product->buy_price) }}"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        required
                    >

                </div>

                <!-- SELLING PRICE -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Selling Price
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="selling_price"
                        value="{{ old('selling_price', $product->selling_price) }}"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        required
                    >

                </div>

                <!-- STOCK -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        name="stock"
                        value="{{ old('stock', $product->stock) }}"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        required
                    >

                </div>

                <!-- ALERT STOCK -->

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-bold
                            text-slate-700
                            mb-2
                        "
                    >
                        Alert Stock
                    </label>

                    <input
                        type="number"
                        name="alert_stock"
                        value="{{ old('alert_stock', $product->alert_stock) }}"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        required
                    >

                </div>

            </div>

            <!-- ACTIONS -->

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    gap-4
                    pt-4
                "
            >

                <!-- UPDATE -->

                <button
                    type="submit"
                    class="
                        flex-1
                        bg-blue-600
                        hover:bg-blue-700
                        text-white
                        px-6
                        py-4
                        rounded-2xl
                        font-bold
                        transition
                    "
                >

                    Update Product

                </button>

                <!-- CANCEL -->

                <a
                    href="{{ route('products.index') }}"
                    class="
                        flex-1
                        bg-slate-200
                        hover:bg-slate-300
                        text-slate-800
                        px-6
                        py-4
                        rounded-2xl
                        font-bold
                        text-center
                        transition
                    "
                >

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

@endsection