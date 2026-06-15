<form method="GET" action="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
    <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex min-w-0 items-center gap-2">
            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">
                Period
            </span>
            <p class="truncate text-xs font-semibold text-slate-600">
                Showing <span class="font-black text-slate-950">{{ $dateLabel ?? 'Today' }}</span>
            </p>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-[150px_140px_140px_auto_auto] xl:flex">
            @if(isset($branches) && $branches->count() > 1)
                <select name="branch_id" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) ($selectedBranchId ?? request('branch_id')) === (string) $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <select name="filter" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
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
                class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                aria-label="Start date"
            >

            <input
                type="date"
                name="end_date"
                value="{{ request('end_date') }}"
                class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                aria-label="End date"
            >

            <button class="h-9 rounded-lg bg-indigo-600 px-3 text-xs font-black text-white shadow-sm shadow-indigo-200">
                Apply
            </button>

            <a href="{{ route('dashboard') }}" class="flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-black text-slate-700">
                Reset
            </a>
        </div>
    </div>
</form>
