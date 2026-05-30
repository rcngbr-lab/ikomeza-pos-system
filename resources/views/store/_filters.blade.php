<form method="GET" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 md:grid-cols-5">
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search product, supplier, reference..."
            class="rounded-xl border-slate-200 bg-slate-50 text-sm"
        >

        <select name="department_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <option value="">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <select name="store_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <option value="">All Stores</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected((int) $selectedStoreId === (int) $store->id)>
                    {{ $store->name }}
                </option>
            @endforeach
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

        <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-indigo-200">
            Apply Filters
        </button>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
        <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
        <a href="{{ $action }}" class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-3 text-center text-sm font-black text-slate-700">
            Reset
        </a>
    </div>
</form>
