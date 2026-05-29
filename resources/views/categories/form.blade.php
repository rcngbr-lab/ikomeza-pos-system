@if($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
        <ul class="space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
    @csrf

    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">
                Category Name
            </label>
            <input
                type="text"
                name="name"
                value="{{ old('name', $category->name) }}"
                required
                placeholder="Beer, Food, Retail..."
                class="w-full rounded-2xl border border-slate-300 px-5 py-4 font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">
                Code
            </label>
            <input
                type="text"
                name="code"
                value="{{ old('code', $category->code) }}"
                placeholder="BEER"
                class="w-full rounded-2xl border border-slate-300 px-5 py-4 font-semibold uppercase focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">
                Department
            </label>
            <select
                name="department_id"
                required
                class="w-full rounded-2xl border border-slate-300 px-5 py-4 font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">Select Department</option>
                @foreach($departments as $department)
                    <option
                        value="{{ $department->id }}"
                        @selected(old('department_id', $category->department_id) == $department->id)
                    >
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-bold text-slate-700">
                Sort Order
            </label>
            <input
                type="number"
                min="0"
                name="sort_order"
                value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                class="w-full rounded-2xl border border-slate-300 px-5 py-4 font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div class="flex items-end">
            <label class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                <span class="font-bold text-slate-700">Active Category</span>
                <input type="hidden" name="active" value="0">
                <input
                    type="checkbox"
                    name="active"
                    value="1"
                    class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    @checked(old('active', $category->active ?? true))
                >
            </label>
        </div>

        <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-bold text-slate-700">
                Description
            </label>
            <textarea
                name="description"
                rows="4"
                placeholder="Optional category notes"
                class="w-full rounded-2xl border border-slate-300 px-5 py-4 font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >{{ old('description', $category->description) }}</textarea>
        </div>
    </div>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <button
            type="submit"
            class="flex-1 rounded-2xl bg-indigo-600 px-5 py-4 text-base font-black text-white transition hover:bg-indigo-700"
        >
            {{ $button }}
        </button>

        <a
            href="{{ route('categories.index') }}"
            class="flex-1 rounded-2xl bg-slate-200 px-5 py-4 text-center text-base font-black text-slate-800 transition hover:bg-slate-300"
        >
            Cancel
        </a>
    </div>
</form>
