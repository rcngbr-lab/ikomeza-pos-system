@extends('layouts.app')

@section('content')

<div class="max-w-4xl mx-auto">

    <!-- ===================================================== -->
    <!-- HEADER -->
    <!-- ===================================================== -->

    <div class="mb-8">

        <h1
            class="
                text-3xl
                md:text-4xl
                font-black
                text-slate-900
            "
        >
            Create Category
        </h1>

        <p class="text-slate-500 mt-2">

            Organize inventory and product classification

        </p>

    </div>

    <!-- ===================================================== -->
    <!-- FORM CARD -->
    <!-- ===================================================== -->

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
            action="{{ route('categories.store') }}"
            class="space-y-6"
        >

            @csrf

            <!-- ================================================= -->
            <!-- NAME -->
            <!-- ================================================= -->

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
                    Category Name
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="
                        w-full
                        rounded-2xl
                        border
                        border-slate-300
                        px-4
                        py-3
                        focus:outline-none
                        focus:ring-2
                        focus:ring-blue-500
                    "
                    placeholder="Example: Beer"
                >

                @error('name')

                    <p class="text-red-600 text-sm mt-2">

                        {{ $message }}

                    </p>

                @enderror

            </div>

            <!-- ================================================= -->
            <!-- DESCRIPTION -->
            <!-- ================================================= -->

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
                    rows="5"
                    class="
                        w-full
                        rounded-2xl
                        border
                        border-slate-300
                        px-4
                        py-3
                        focus:outline-none
                        focus:ring-2
                        focus:ring-blue-500
                    "
                    placeholder="Optional category notes..."
                >{{ old('description') }}</textarea>

                @error('description')

                    <p class="text-red-600 text-sm mt-2">

                        {{ $message }}

                    </p>

                @enderror

            </div>

            <!-- ================================================= -->
            <!-- ACTIONS -->
            <!-- ================================================= -->

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    gap-4
                    pt-4
                "
            >

                <button
                    type="submit"
                    class="
                        bg-blue-600
                        hover:bg-blue-700
                        transition
                        text-white
                        font-bold
                        px-6
                        py-3
                        rounded-2xl
                    "
                >
                    Save Category
                </button>

                <a
                    href="{{ route('categories.index') }}"
                    class="
                        bg-slate-200
                        hover:bg-slate-300
                        transition
                        text-slate-900
                        font-bold
                        px-6
                        py-3
                        rounded-2xl
                        text-center
                    "
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

@endsection