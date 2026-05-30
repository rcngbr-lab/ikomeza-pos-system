@extends('layouts.app')

@section('content')
@php
    $queryForPrint = request()->query();
@endphp

<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">
                {{ $canReviewAll ? 'Management Review' : 'My Shift Story' }}
            </p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 md:text-4xl">
                Shift History
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                {{ $canReviewAll
                    ? 'Review all staff shifts, cash reconciliation, shortages, overages, and print filtered reports.'
                    : 'Review your own shift activity, cash reconciliation, and printable shift reports.' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('shifts.current') }}"
                class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm"
            >
                Current Shift
            </a>
            <a
                href="{{ route('shifts.history.print', $queryForPrint) }}"
                target="_blank"
                class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-200"
            >
                Print Filtered
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('shifts.history') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-5">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="{{ $canReviewAll ? 'Search shift, staff, email...' : 'Search shift code...' }}"
                class="rounded-xl border-slate-200 bg-slate-50 text-sm"
            >

            @if($canReviewAll)
                <select name="user_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                    <option value="">All Staff</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) request('user_id') === (int) $user->id)>
                            {{ $user->name }} - {{ $user->roleLabel() }}
                        </option>
                    @endforeach
                </select>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-600">
                    {{ auth()->user()->name }}
                </div>
            @endif

            <select name="status" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">All Status</option>
                <option value="OPEN" @selected(request('status') === 'OPEN')>Open</option>
                <option value="CLOSED" @selected(request('status') === 'CLOSED')>Closed</option>
            </select>

            <select name="filter" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">All Time</option>
                <option value="today" @selected(request('filter') === 'today')>Today</option>
                <option value="yesterday" @selected(request('filter') === 'yesterday')>Yesterday</option>
                <option value="week" @selected(request('filter') === 'week')>This Week</option>
                <option value="last_week" @selected(request('filter') === 'last_week')>Last Week</option>
                <option value="month" @selected(request('filter') === 'month')>This Month</option>
                <option value="last_month" @selected(request('filter') === 'last_month')>Last Month</option>
                <option value="year" @selected(request('filter') === 'year')>This Year</option>
            </select>

            <button class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white">
                Apply Filters
            </button>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-[1fr_1fr_160px_160px]">
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <select name="per_page" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="20" @selected((int) request('per_page', 20) === 20)>20 per page</option>
                <option value="50" @selected((int) request('per_page') === 50)>50 per page</option>
                <option value="100" @selected((int) request('per_page') === 100)>100 per page</option>
            </select>
            <a href="{{ route('shifts.history') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-3 text-center text-sm font-black text-slate-700">
                Reset
            </a>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Filtered Shifts</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['count']) }}</p>
            <p class="mt-1 text-xs font-bold text-slate-400">{{ $periodLabel }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Total Sales</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">{{ number_format((float) $summary['sales']) }} RWF</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Expected Cash</p>
            <p class="mt-3 text-3xl font-black text-indigo-600">{{ number_format((float) $summary['expected']) }} RWF</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Net Difference</p>
            <p class="mt-3 text-3xl font-black {{ (float) $summary['difference'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ number_format((float) $summary['difference']) }} RWF
            </p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Open / Closed</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($summary['open']) }} / {{ number_format($summary['closed']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Cash Sales</p>
            <p class="mt-3 text-3xl font-black text-blue-600">{{ number_format((float) $summary['cash']) }} RWF</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Shortage</p>
            <p class="mt-3 text-3xl font-black text-rose-600">{{ number_format((float) $summary['shortage']) }} RWF</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Overage</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">{{ number_format((float) $summary['overage']) }} RWF</p>
        </div>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-950">Shift Records</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Date range uses opened time from 00:00:00 to 23:59:59.
                </p>
            </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full min-w-[1180px]">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-5 py-4">Shift</th>
                        <th class="px-5 py-4">Staff</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Opening</th>
                        <th class="px-5 py-4">Sales</th>
                        <th class="px-5 py-4">Cash</th>
                        <th class="px-5 py-4">Expected</th>
                        <th class="px-5 py-4">Closing</th>
                        <th class="px-5 py-4">Difference</th>
                        <th class="px-5 py-4">Opened</th>
                        <th class="px-5 py-4">Closed</th>
                        <th class="px-5 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shifts as $shift)
                        <tr class="text-sm">
                            <td class="px-5 py-4">
                                <p class="font-black text-slate-950">{{ $shift->shift_code ?: '#' . $shift->id }}</p>
                                <p class="text-xs text-slate-500">#{{ $shift->id }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-900">{{ $shift->user->name ?? 'N/A' }}</p>
                                <p class="text-xs text-slate-500">{{ $shift->user?->roleLabel() }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $shift->status === 'OPEN' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $shift->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4">{{ number_format((float) $shift->opening_cash) }}</td>
                            <td class="px-5 py-4 font-black text-emerald-600">{{ number_format((float) $shift->total_sales) }}</td>
                            <td class="px-5 py-4">{{ number_format((float) $shift->cash_sales) }}</td>
                            <td class="px-5 py-4 font-bold">{{ number_format((float) $shift->expected_cash) }}</td>
                            <td class="px-5 py-4">{{ number_format((float) $shift->closing_cash) }}</td>
                            <td class="px-5 py-4 font-black {{ (float) $shift->difference < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ number_format((float) $shift->difference) }}
                            </td>
                            <td class="px-5 py-4 text-slate-500">{{ $shift->opened_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $shift->closed_at?->format('Y-m-d H:i') ?? 'Still open' }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white">
                                    Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-5 py-12 text-center text-sm font-semibold text-slate-400">
                                No shifts found for these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 p-4 lg:hidden">
            @forelse($shifts as $shift)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-slate-950">{{ $shift->shift_code ?: '#' . $shift->id }}</p>
                            <p class="text-sm text-slate-500">{{ $shift->user->name ?? 'N/A' }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $shift->status === 'OPEN' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                            {{ $shift->status }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs font-bold text-slate-400">Sales</p>
                            <p class="font-black text-emerald-600">{{ number_format((float) $shift->total_sales) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs font-bold text-slate-400">Difference</p>
                            <p class="font-black {{ (float) $shift->difference < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ number_format((float) $shift->difference) }}
                            </p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs font-bold text-slate-400">Opened</p>
                            <p class="font-bold text-slate-700">{{ $shift->opened_at?->format('Y-m-d H:i') ?? '-' }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <p class="text-xs font-bold text-slate-400">Closed</p>
                            <p class="font-bold text-slate-700">{{ $shift->closed_at?->format('Y-m-d H:i') ?? 'Open' }}</p>
                        </div>
                    </div>

                    <a href="{{ route('shifts.print', $shift) }}" target="_blank" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white">
                        Print Shift
                    </a>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm font-semibold text-slate-400">
                    No shifts found for these filters.
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 p-5">
            {{ $shifts->links() }}
        </div>
    </section>
</div>
@endsection
