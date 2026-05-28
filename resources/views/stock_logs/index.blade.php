@extends('layouts.app')

@section('content')

<div class="p-4 md:p-6">

    <!-- HEADER -->

    <div class="flex items-center justify-between mb-6">

        <div>

            <h1 class="text-3xl font-black">
                Stock Logs
            </h1>

            <p class="text-slate-500 text-sm">
                Inventory movement history
            </p>

        </div>

    </div>

    <!-- FILTERS -->

    <form
        method="GET"
        class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-5"
    >

        <!-- SEARCH -->

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search product..."
            class="border rounded-xl px-4 py-3"
        >

        <!-- FILTER -->

        <select
            name="filter"
            class="border rounded-xl px-4 py-3"
        >

            <option value="">
                All Time
            </option>

            <option
                value="today"
                @selected(request('filter') == 'today')
            >
                Daily
            </option>

            <option
                value="weekly"
                @selected(request('filter') == 'weekly')
            >
                Weekly
            </option>

            <option
                value="monthly"
                @selected(request('filter') == 'monthly')
            >
                Monthly
            </option>

            <option
                value="yearly"
                @selected(request('filter') == 'yearly')
            >
                Yearly
            </option>

        </select>

        <!-- START -->

        <input
            type="date"
            name="start_date"
            value="{{ request('start_date') }}"
            class="border rounded-xl px-4 py-3"
        >

        <!-- END -->

        <input
            type="date"
            name="end_date"
            value="{{ request('end_date') }}"
            class="border rounded-xl px-4 py-3"
        >

        <!-- BUTTON -->

        <button
            class="bg-slate-900 hover:bg-black
                   text-white rounded-xl
                   font-bold"
        >

            APPLY

        </button>

    </form>

    <!-- TABLE -->

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-950 text-white">

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
                            User
                        </th>

                        <th class="p-4 text-left">
                            Date
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($logs as $log)

                        <tr class="border-b hover:bg-slate-50">

                            <!-- PRODUCT -->

                            <td class="p-4 font-semibold">

                                {{ $log->product->name ?? 'Deleted' }}

                            </td>

                            <!-- TYPE -->

                            <td class="p-4">

                                <td class="p-4">

    @if($log->type == 'SALE')

        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600">
            SALE
        </span>

    @elseif($log->type == 'RESTOCK')

        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-600">
            RESTOCK
        </span>

    @elseif($log->type == 'REFUND')

        <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
            REFUND
        </span>

    @elseif($log->type == 'ADJUSTMENT')

        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
            ADJUSTMENT
        </span>

    @elseif($log->type == 'DAMAGE')

        <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
            DAMAGE
        </span>

    @else

        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
            {{ $log->type }}
        </span>

    @endif

</td>

                         

                            <!-- QTY -->

                            <td class="p-4 font-bold">

                                {{ $log->quantity }}

                            </td>

                            <!-- USER -->

                            <td class="p-4">

                                {{ $log->user->name ?? 'System' }}

                            </td>

                            <!-- DATE -->

                            <td class="p-4 text-slate-500">

                                {{ $log->created_at->format('d/m/Y H:i') }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="p-10 text-center text-slate-500"
                            >

                                No stock logs found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <!-- PAGINATION -->

    <div class="mt-5">

        {{ $logs->links() }}

    </div>

</div>

@endsection