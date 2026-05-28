@extends('layouts.app')

@section('content')

<div class="p-6">

    <!-- HEADER -->

    <div class="
        flex
        items-center
        justify-between
        mb-8
    ">

        <div>

            <h1 class="
                text-3xl
                font-black
                text-slate-900
            ">

                Stock Movement Logs

            </h1>

            <p class="text-slate-500 mt-2">

                Inventory movement history and audit trail

            </p>

        </div>

    </div>

    <!-- TABLE -->

    <div class="
        bg-white
        rounded-3xl
        shadow-sm
        overflow-hidden
    ">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="
                    bg-slate-900
                    text-white
                ">

                    <tr>

                        <th class="p-4 text-left">

                            Product

                        </th>

                        <th class="p-4 text-left">

                            Type

                        </th>

                        <th class="p-4 text-left">

                            Qty

                        </th>

                        <th class="p-4 text-left">

                            Before

                        </th>

                        <th class="p-4 text-left">

                            After

                        </th>

                        <th class="p-4 text-left">

                            User

                        </th>

                        <th class="p-4 text-left">

                            Reason

                        </th>

                        <th class="p-4 text-left">

                            Date

                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($movements as $movement)

                        <tr class="border-b">

                            <td class="p-4 font-semibold">

                                {{ $movement->product->name ?? 'N/A' }}

                            </td>

                            <td class="p-4">

                                <span class="
                                    px-3
                                    py-1
                                    rounded-full
                                    text-xs
                                    font-bold
                                    bg-orange-100
                                    text-orange-700
                                ">

                                    {{ $movement->type }}

                                </span>

                            </td>

                            <td class="p-4">

                                {{ $movement->quantity }}

                            </td>

                            <td class="p-4">

                                {{ $movement->before_stock }}

                            </td>

                            <td class="p-4">

                                {{ $movement->after_stock }}

                            </td>

                            <td class="p-4">

                                {{ $movement->user->name ?? 'N/A' }}

                            </td>

                            <td class="p-4 text-slate-600">

                                {{ $movement->reason }}

                            </td>

                            <td class="p-4 text-slate-500">

                                {{ $movement
                                    ->created_at
                                    ->format('d M Y H:i')
                                }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="
                                    p-8
                                    text-center
                                    text-slate-500
                                "
                            >

                                No stock movements found

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <!-- PAGINATION -->

    <div class="mt-6">

        {{ $movements->links() }}

    </div>

</div>

@endsection