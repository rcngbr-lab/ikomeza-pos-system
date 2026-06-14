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
            enctype="multipart/form-data"
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

                    <div class="mb-3 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-white">
                            @if($product->image_source)
                                <img
                                    src="{{ $product->image_source }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-contain p-2"
                                    loading="lazy"
                                >
                            @else
                                <span class="text-center text-xs font-black text-slate-400">
                                    No Image
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 text-xs font-semibold text-slate-500">
                            <p class="font-black text-slate-700">Current POS image</p>
                            <p class="mt-1 truncate">{{ $product->image_url ?: ($product->image_path ?: 'Placeholder will be used') }}</p>

                            @if($product->image_path || $product->image_url)
                                <label class="mt-2 inline-flex items-center gap-2 font-black text-rose-600">
                                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">
                                    Remove image
                                </label>
                            @endif
                        </div>
                    </div>

                    <input
                        type="file"
                        name="product_image"
                        accept="image/*"
                        class="
                            w-full
                            rounded-2xl
                            border
                            border-slate-300
                            px-4
                            py-3
                            text-sm
                            focus:outline-none
                            focus:ring-2
                            focus:ring-blue-500
                        "
                    >

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
                        value="{{ old('image_url', $product->image_url) }}"
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
                        placeholder="https://example.com/product.png"
                    >

                    <p class="mt-2 text-xs font-semibold text-slate-500">
                        Uploaded image takes priority. Use URL only when no uploaded image exists.
                    </p>

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
                        @foreach($departments as $department)
                            <option
                                value="{{ $department->id }}"
                                @selected(old('department_id', $product->department_id) == $department->id)
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
                        class="w-full rounded-2xl border border-slate-300 px-4 py-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Auto-select by department</option>
                        @foreach($stores as $store)
                            <option
                                value="{{ $store->id }}"
                                @selected(old('default_store_id', $product->default_store_id) == $store->id)
                            >
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
                        class="w-full rounded-2xl border border-slate-300 px-4 py-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">No supplier selected</option>
                        @foreach($suppliers as $supplier)
                            <option
                                value="{{ $supplier->id }}"
                                @selected(old('supplier_id', $product->supplier_id) == $supplier->id)
                            >
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
                        <option value="FINISHED_PRODUCT" @selected(old('product_type', $product->product_type ?: 'FINISHED_PRODUCT') === 'FINISHED_PRODUCT')>
                            Finished Product
                        </option>

                        <option value="RAW_MATERIAL" @selected(old('product_type', $product->product_type) === 'RAW_MATERIAL')>
                            Raw Material
                        </option>

                        <option value="SERVICE" @selected(old('product_type', $product->product_type) === 'SERVICE')>
                            Service
                        </option>
                    </select>

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
