@extends('layouts.app')

@section('content')

<div class="max-w-2xl mx-auto p-6">

    <div class="bg-white rounded-3xl shadow-sm p-8">

        <!-- HEADER -->

        <div class="mb-8">

            <h1 class="text-3xl font-black text-slate-900">

                Adjust Stock

            </h1>

            <p class="text-slate-500 mt-2">

                Update inventory quantity safely

            </p>

        </div>

        <!-- PRODUCT INFO -->

        <div class="mb-8 p-5 bg-slate-100 rounded-2xl">

            <h2 class="text-2xl font-bold">

                {{ $product->name }}

            </h2>

            <div class="mt-3 text-slate-600">

                Current Stock:
                <span class="font-black">

                    {{ $product->stock }}

                </span>

            </div>

        </div>

        <!-- ERRORS -->

        @if ($errors->any())

            <div class="
                mb-6
                bg-red-100
                text-red-700
                p-4
                rounded-2xl
            ">

                <ul class="list-disc pl-5">

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <!-- SUCCESS -->

        @if(session('success'))

            <div class="
                mb-6
                bg-green-100
                text-green-700
                p-4
                rounded-2xl
            ">

                {{ session('success') }}

            </div>

        @endif

        <!-- FORM -->

        <form
            action="{{ route(
                'products.adjust.stock',
                $product->id
            ) }}"
            method="POST"
            class="space-y-6"
        >

            @csrf

            <!-- TYPE -->

            <div>

                <label class="
                    block
                    font-bold
                    mb-2
                ">

                    Adjustment Type

                </label>

                <select
                    name="type"
                    class="
                        w-full
                        rounded-2xl
                        border-slate-300
                    "
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

                <label class="
                    block
                    font-bold
                    mb-2
                ">

                    Quantity

                </label>

                <input
                    type="number"
                    name="quantity"
                    min="1"
                    class="
                        w-full
                        rounded-2xl
                        border-slate-300
                    "
                    required
                >

            </div>

            <!-- REASON -->

            <div>

                <label class="
                    block
                    font-bold
                    mb-2
                ">

                    Reason

                </label>

                <textarea
                    name="reason"
                    rows="4"
                    class="
                        w-full
                        rounded-2xl
                        border-slate-300
                    "
                ></textarea>

            </div>

            <!-- BUTTONS -->

            <div class="
                flex
                items-center
                gap-4
            ">

                <button
                    type="submit"
                    class="
                        bg-orange-500
                        hover:bg-orange-600
                        text-white
                        px-6
                        py-4
                        rounded-2xl
                        font-bold
                    "
                >

                    Save Adjustment

                </button>

                <a
                    href="{{ route('products.index') }}"
                    class="
                        bg-slate-200
                        hover:bg-slate-300
                        text-slate-700
                        px-6
                        py-4
                        rounded-2xl
                        font-bold
                    "
                >

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

@endsection