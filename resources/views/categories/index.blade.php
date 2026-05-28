@extends('layouts.app')

@section('content')

<div class="space-y-8">

    <!-- ===================================================== -->
    <!-- HEADER -->
    <!-- ===================================================== -->

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
                Categories
            </h1>

            <p class="text-slate-500 mt-2">

                Manage product categories for AGNES BAR POS

            </p>

        </div>

        <!-- BUTTON -->

        <a
            href="{{ route('categories.create') }}"
            class="
                inline-flex
                items-center
                justify-center
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
            + Add Category
        </a>

    </div>

    <!-- ===================================================== -->
    <!-- MOBILE CARDS -->
    <!-- ===================================================== -->

    <div class="grid grid-cols-1 gap-4 lg:hidden">

        @forelse($categories as $category)

            <div
                class="
                    bg-white
                    rounded-3xl
                    shadow-sm
                    p-5
                "
            >

                <!-- TOP -->

                <div
                    class="
                        flex
                        items-start
                        justify-between
                        gap-4
                    "
                >

                    <div>

                        <div
                            class="
                                text-sm
                                text-slate-400
                                mb-1
                            "
                        >
                            #{{ $category->id }}
                        </div>

                        <h2
                            class="
                                text-xl
                                font-bold
                                text-slate-900
                            "
                        >
                            {{ $category->name }}
                        </h2>

                    </div>

                    <span
                        class="
                            bg-green-100
                            text-green-700
                            text-xs
                            font-bold
                            px-3
                            py-1
                            rounded-full
                        "
                    >
                        Active
                    </span>

                </div>

                <!-- DESCRIPTION -->

                <div
                    class="
                        mt-4
                        text-slate-600
                    "
                >

                    {{ $category->description }}

                </div>

                <!-- DATE -->

                <div
                    class="
                        mt-5
                        text-sm
                        text-slate-400
                    "
                >

                    {{ \Carbon\Carbon::parse($category->created_at)->format('d M Y') }}

                </div>

            </div>

        @empty

            <div
                class="
                    bg-white
                    rounded-3xl
                    p-8
                    text-center
                    text-slate-500
                "
            >

                No categories found

            </div>

        @endforelse

    </div>

    <!-- ===================================================== -->
    <!-- DESKTOP TABLE -->
    <!-- ===================================================== -->

    <div
        class="
            hidden
            lg:block
            bg-white
            rounded-3xl
            shadow-sm
            overflow-hidden
        "
    >

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead
                    class="
                        bg-slate-900
                        text-white
                    "
                >

                    <tr>

                        <th class="text-left p-5">
                            ID
                        </th>

                        <th class="text-left p-5">
                            Category Name
                        </th>

                        <th class="text-left p-5">
                            Description
                        </th>

                        <th class="text-left p-5">
                            Date Created
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($categories as $category)

                        <tr
                            class="
                                border-b
                                hover:bg-slate-50
                                transition
                            "
                        >

                            <td class="p-5">

                                #{{ $category->id }}

                            </td>

                            <td class="p-5">

                                <span
                                    class="
                                        font-bold
                                        text-slate-900
                                    "
                                >

                                    {{ $category->name }}

                                </span>

                            </td>

                            <td class="p-5 text-slate-600">

                                {{ $category->description }}

                            </td>

                            <td class="p-5 text-slate-500">

                                {{ \Carbon\Carbon::parse($category->created_at)->format('d M Y') }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="
                                    p-8
                                    text-center
                                    text-slate-500
                                "
                            >

                                No categories found

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection