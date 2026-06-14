<form method="GET" action="{{ $action }}" class="dense-toolbar">
    <div class="grid flex-1 gap-2 md:grid-cols-5">
        <input
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search product, supplier, reference..."
            class="dense-input"
        >

        <select name="department_id" class="dense-select">
            <option value="">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        <select name="store_id" class="dense-select">
            <option value="">All Stores</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected((int) $selectedStoreId === (int) $store->id)>
                    {{ $store->name }}
                </option>
            @endforeach
        </select>

        <select name="filter" class="dense-select">
            <option value="">All Time</option>
            <option value="today" @selected(request('filter') === 'today')>Today</option>
            <option value="yesterday" @selected(request('filter') === 'yesterday')>Yesterday</option>
            <option value="week" @selected(request('filter') === 'week')>This Week</option>
            <option value="last_week" @selected(request('filter') === 'last_week')>Last Week</option>
            <option value="month" @selected(request('filter') === 'month')>This Month</option>
            <option value="last_month" @selected(request('filter') === 'last_month')>Last Month</option>
            <option value="year" @selected(request('filter') === 'year')>This Year</option>
        </select>

        <button class="dense-btn-primary">Apply</button>
        <input type="date" name="start_date" value="{{ request('start_date') }}" class="dense-input">
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="dense-input">
        <a href="{{ $action }}" class="dense-btn-soft">
            Reset
        </a>
    </div>
</form>
