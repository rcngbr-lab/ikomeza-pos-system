@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto p-4 sm:p-6">

    <div class="grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">

        <div class="space-y-5">

            <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6">

                <!-- HEADER -->

                <div class="mb-5">

                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900">

                        Adjust Stock

                    </h1>

                    <p class="text-slate-500 mt-1">

                        Update inventory safely and keep the POS product image current.

                    </p>

                </div>

                <!-- PRODUCT INFO -->

                <div class="flex gap-4 rounded-2xl bg-slate-100 p-4">

                    <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-sm">

                        @if($product->image_source)

                            <img
                                src="{{ $product->image_source }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-contain p-2"
                                loading="lazy"
                            >

                        @else

                            <div class="px-3 text-center text-xs font-black uppercase tracking-wide text-slate-400">

                                No Image

                            </div>

                        @endif

                    </div>

                    <div class="min-w-0 flex-1">

                        <h2 class="truncate text-xl font-black text-slate-900">

                            {{ $product->name }}

                        </h2>

                        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">

                            <div>
                                <dt class="text-slate-500">Current Stock</dt>
                                <dd class="font-black text-slate-900">{{ $product->stock }}</dd>
                            </div>

                            <div>
                                <dt class="text-slate-500">Unit</dt>
                                <dd class="font-black text-slate-900">{{ $product->unit ?: 'Item' }}</dd>
                            </div>

                            <div>
                                <dt class="text-slate-500">Price</dt>
                                <dd class="font-black text-slate-900">{{ number_format((float) $product->selling_price) }} RWF</dd>
                            </div>

                            <div>
                                <dt class="text-slate-500">Status</dt>
                                <dd class="font-black text-slate-900">{{ $product->status }}</dd>
                            </div>

                        </dl>

                    </div>

                </div>

            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6">

                <h2 class="text-xl font-black text-slate-900">

                    Product Image

                </h2>

                <p class="mt-1 text-sm text-slate-500">

                    Upload a clean product image or paste an image URL. Stock quantity is not changed here.

                </p>

                <form
                    action="{{ route('products.image.update', $product) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="mt-5 space-y-4"
                >

                    @csrf

                    <div>

                        <label class="mb-2 block text-sm font-black text-slate-800">

                            Upload Image

                        </label>

                        <input
                            type="file"
                            name="product_image"
                            accept="image/*"
                            class="block w-full rounded-xl border border-slate-300 bg-white text-sm file:mr-4 file:border-0 file:bg-indigo-600 file:px-4 file:py-3 file:text-sm file:font-black file:text-white"
                        >

                        <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WEBP up to 3MB. Uploaded image has priority over URL.</p>

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-black text-slate-800">

                            Image URL

                        </label>

                        <input
                            type="url"
                            name="image_url"
                            value="{{ old('image_url', $product->image_url) }}"
                            placeholder="https://example.com/product.png"
                            class="w-full rounded-xl border-slate-300"
                        >

                    </div>

                    @if($product->image_path || $product->image_url)

                        <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">

                            <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">
                            Remove current image

                        </label>

                    @endif

                    <div class="flex flex-wrap gap-3">

                        <button
                            type="submit"
                            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700"
                        >

                            Save Image

                        </button>

                    </div>

                </form>

            </div>

        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6">

            <!-- ERRORS -->

            @if ($errors->any())

                <div class="mb-5 rounded-2xl bg-red-100 p-4 text-red-700">

                    <ul class="list-disc pl-5">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif

            <!-- SUCCESS -->

            @if(session('success'))

                <div class="mb-5 rounded-2xl bg-green-100 p-4 text-green-700">

                    {{ session('success') }}

                </div>

            @endif

            <h2 class="text-xl font-black text-slate-900">

                Stock Change Request

            </h2>

            <p class="mt-1 text-sm text-slate-500">

                This creates a pending requisition. Live stock changes only after approval.

            </p>

            <!-- FORM -->

            <form
                action="{{ route(
                    'products.adjust.stock',
                    $product->id
                ) }}"
                method="POST"
                class="mt-6 space-y-5"
            >

                @csrf

                <!-- TYPE -->

                <div>

                    <label class="mb-2 block font-bold">

                        Adjustment Type

                    </label>

                    <select
                        name="type"
                        class="w-full rounded-xl border-slate-300"
                        required
                    >

                        <option value="ADD">

                            Add Stock

                        </option>

                        <option value="REMOVE">

                            Remove Stock

                        </option>

                    </select>

                </div>

                <!-- QUANTITY -->

                <div>

                    <label class="mb-2 block font-bold">

                        Quantity

                    </label>

                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        class="w-full rounded-xl border-slate-300"
                        required
                    >

                </div>

                <!-- REASON -->

                <div>

                    <label class="mb-2 block font-bold">

                        Reason

                    </label>

                    <textarea
                        name="reason"
                        rows="5"
                        class="w-full rounded-xl border-slate-300"
                    ></textarea>

                </div>

                <!-- BUTTONS -->

                <div class="flex flex-wrap items-center gap-3">

                    <button
                        type="submit"
                        class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-black text-white hover:bg-orange-600"
                    >

                        Save Adjustment

                    </button>

                    <a
                        href="{{ route('products.index') }}"
                        class="rounded-xl bg-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-300"
                    >

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection
