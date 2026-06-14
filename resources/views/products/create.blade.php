@extends('layouts.app')

@section('content')

<div class="max-w-5xl mx-auto">

    <!-- PAGE HEADER -->

    <div class="flex items-center justify-between mb-8">

        <div>

            <h1 class="text-4xl font-black text-slate-900">
                Add Product
            </h1>

            <p class="text-slate-500 mt-2">
                Create and manage bar inventory products
            </p>

        </div>

        <div class="flex gap-3">
            <a
                href="{{ route('categories.index') }}"
                class="
                    bg-white
                    hover:bg-slate-50
                    text-slate-700
                    border
                    border-slate-200
                    px-5
                    py-3
                    rounded-2xl
                    font-bold
                    transition
                "
            >
                Categories
            </a>

            <a
                href="{{ route('products.index') }}"
                class="
                    bg-slate-200
                    hover:bg-slate-300
                    text-slate-800
                    px-5
                    py-3
                    rounded-2xl
                    font-bold
                    transition
                "
            >
                Back
            </a>
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
                mb-6
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
            p-8
        "
    >

        <form
            action="{{ route('products.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-8"
        >

            @csrf

            <!-- GRID -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

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
                        value="{{ old('name') }}"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="Enter product name"
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
                        value="{{ old('barcode') }}"
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="SKU-001"
                    >

                </div>

                <!-- PRODUCT IMAGE -->

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
                        Product Image
                    </label>

                    <input
                        type="file"
                        name="product_image"
                        accept="image/*"
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-3
                            text-sm
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                    >

                    <p class="mt-2 text-xs font-semibold text-slate-500">
                        Recommended: transparent PNG/JPG, max 3MB. POS cards use contain-fit, no cropping.
                    </p>

                </div>

                <!-- IMAGE URL -->

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
                        Image URL
                    </label>

                    <input
                        type="url"
                        name="image_url"
                        value="{{ old('image_url') }}"
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="https://example.com/product.png"
                    >

                </div>

                <!-- DEPARTMENT -->

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
                        Department
                    </label>

                    <select
                        name="department_id"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                    >

                        <option value="">
                            Select Department
                        </option>

                        @foreach($departments as $department)

                            <option
                                value="{{ $department->id }}"
                                @selected(old('department_id') == $department->id)
                            >
                                {{ $department->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <!-- CATEGORY -->

                <div>

                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label
                            class="
                                block
                                text-sm
                                font-bold
                                text-slate-700
                            "
                        >
                            Category
                        </label>

                        <a
                            href="{{ route('categories.create') }}"
                            class="text-xs font-black text-blue-600 hover:text-blue-700"
                        >
                            Add Category
                        </a>
                    </div>

                    <select
                        name="category_id"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                    >

                        <option value="">
                            Select Category
                        </option>

                        @foreach($categories as $category)

                            <option
                                value="{{ $category->id }}"
                                @selected(old('category_id') == $category->id)
                            >
                                {{ $category->name }}
                                @if($category->department)
                                    - {{ $category->department->name }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                </div>

                <!-- DEFAULT STORE -->

                <div>

                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Default Store
                    </label>

                    <select
                        name="default_store_id"
                        class="w-full border border-slate-300 rounded-2xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Auto-select by department</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected(old('default_store_id') == $store->id)>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>

                </div>

                <!-- SUPPLIER -->

                <div>

                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Supplier
                    </label>

                    <select
                        name="supplier_id"
                        class="w-full border border-slate-300 rounded-2xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">No supplier selected</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                {{ $supplier->company_name }}
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
                        value="{{ old('buy_price') }}"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="0.00"
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
                        value="{{ old('selling_price') }}"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="0.00"
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
                        value="{{ old('stock') }}"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="0"
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
                        value="{{ old('alert_stock', 5) }}"
                        required
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="5"
                    >

                </div>

                <!-- PRODUCT TYPE -->
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
        Product Type
    </label>

    <select
        name="product_type"
        required
        class="
            w-full
            border
            border-slate-300
            rounded-2xl
            px-5
            py-4
            focus:outline-none
            focus:ring-2
            focus:ring-blue-500
        "
    >

        <option value="FINISHED_PRODUCT" @selected(old('product_type', 'FINISHED_PRODUCT') === 'FINISHED_PRODUCT')>
            Finished Product
        </option>

        <option value="RAW_MATERIAL" @selected(old('product_type') === 'RAW_MATERIAL')>
            Raw Material
        </option>

        <option value="SERVICE" @selected(old('product_type') === 'SERVICE')>
            Service
        </option>

    </select>

</div>

                <!-- UNIT -->

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
                        Unit
                    </label>

                    <input
                        type="text"
                        name="unit"
                        value="{{ old('unit', 'Bottle') }}"
                        class="
                            w-full
                            border
                            border-slate-300
                            rounded-2xl
                            px-5
                            py-4
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                        placeholder="Bottle"
                    >

                </div>

            </div>

            <!-- DESCRIPTION -->

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
                    Description
                </label>

                <textarea
                    name="description"
                    rows="4"
                    class="
                        w-full
                        border
                        border-slate-300
                        rounded-2xl
                        px-5
                        py-4
                        focus:outline-none
                        focus:ring-2
                        focus:ring-blue-500
                    "
                    placeholder="Optional product description"
                >{{ old('description') }}</textarea>

            </div>

            <!-- OPTIONS -->

            <div class="flex items-center gap-8">

                <label class="flex items-center gap-3">

                    <input
                        type="checkbox"
                        name="track_stock"
                        value="1"
                        checked
                        class="w-5 h-5"
                    >

                    <span class="font-semibold text-slate-700">
                        Track Stock
                    </span>

                </label>

                <label class="flex items-center gap-3">

                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        checked
                        class="w-5 h-5"
                    >

                    <span class="font-semibold text-slate-700">
                        Active Product
                    </span>

                </label>

            </div>

            <!-- BUTTONS -->

            <div class="flex gap-4 pt-4">

                <button
                    type="submit"
                    class="
                        flex-1
                        bg-blue-600
                        hover:bg-blue-700
                        text-white
                        py-4
                        rounded-2xl
                        font-bold
                        text-lg
                        transition
                    "
                >
                    Save Product
                </button>

                <a
                    href="{{ route('products.index') }}"
                    class="
                        flex-1
                        bg-slate-200
                        hover:bg-slate-300
                        text-slate-800
                        py-4
                        rounded-2xl
                        font-bold
                        text-lg
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
