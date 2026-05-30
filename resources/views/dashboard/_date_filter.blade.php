<form method="GET" action="{{ route('dashboard') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">
                Dashboard Period
            </p>
            <p class="mt-1 text-sm font-semibold text-slate-600">
                Showing: <span class="font-black text-slate-950">{{ $dateLabel ?? 'Today' }}</span>
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[180px_160px_160px_auto_auto]">
            <select name="filter" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="today" @selected(($dateFilter ?? request('filter', 'today')) === 'today')>Today</option>
                <option value="yesterday" @selected(($dateFilter ?? request('filter')) === 'yesterday')>Yesterday</option>
                <option value="week" @selected(($dateFilter ?? request('filter')) === 'week')>This Week</option>
                <option value="last_week" @selected(($dateFilter ?? request('filter')) === 'last_week')>Last Week</option>
                <option value="month" @selected(($dateFilter ?? request('filter')) === 'month')>This Month</option>
                <option value="last_month" @selected(($dateFilter ?? request('filter')) === 'last_month')>Last Month</option>
                <option value="year" @selected(($dateFilter ?? request('filter')) === 'year')>This Year</option>
                <option value="all" @selected(($dateFilter ?? request('filter')) === 'all')>All Time</option>
                <option value="range" @selected(($dateFilter ?? request('filter')) === 'range')>Custom Range</option>
            </select>

            <input
                type="date"
                name="start_date"
                value="{{ request('start_date') }}"
                class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                aria-label="Start date"
            >

            <input
                type="date"
                name="end_date"
                value="{{ request('end_date') }}"
                class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                aria-label="End date"
            >

            <button class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-black text-white shadow-sm shadow-indigo-200">
                Apply
            </button>

            <a href="{{ route('dashboard') }}" class="flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-5 text-sm font-black text-slate-700">
                Reset
            </a>
        </div>
    </div>
</form>
