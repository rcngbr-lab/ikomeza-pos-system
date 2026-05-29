@extends('layouts.app')

@section('content')

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-950 lg:text-4xl">
                Categories
            </h1>
            <p class="mt-2 text-sm font-medium text-slate-500">
                Organize products for faster selling, reporting, and stock control.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('products.create') }}"
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
            >
                Add Product
            </a>

            <a
                href="{{ route('categories.create') }}"
                class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700"
            >
                Add Category
            </a>
        </div>
    </div>

    @if(session('success') || session('error'))
        <div class="space-y-3">
            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Total Categories</p>
            <p class="mt-3 text-3xl font-black text-slate-950">{{ number_format($totalCategories) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Active Categories</p>
            <p class="mt-3 text-3xl font-black text-emerald-600">{{ number_format($activeCategories) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-bold text-slate-500">Assigned Products</p>
            <p class="mt-3 text-3xl font-black text-indigo-600">{{ number_format($assignedProducts) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('categories.index') }}" class="grid gap-3 lg:grid-cols-[1fr_220px_auto]">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search categories..."
                class="min-w-0 flex-1 rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >

            <select
                name="department_id"
                class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All Departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) $selectedDepartmentId === (int) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>

            <button
                type="submit"
                class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-800"
            >
                Search
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px]">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-5 py-4">Category</th>
                        <th class="px-5 py-4">Department</th>
                        <th class="px-5 py-4">Code</th>
                        <th class="px-5 py-4">Products</th>
                        <th class="px-5 py-4">Sort</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-black text-slate-950">{{ $category->name }}</p>
                                <p class="mt-1 max-w-xl truncate text-sm text-slate-500">
                                    {{ $category->description ?: 'No description' }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ ($category->department?->code ?? '') === 'KITCHEN' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                    {{ $category->department->name ?? 'Unassigned' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-600">
                                {{ $category->code ?: 'N/A' }}
                            </td>
                            <td class="px-5 py-4 font-black text-slate-950">
                                {{ number_format($category->products_count) }}
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $category->sort_order }}
                            </td>
                            <td class="px-5 py-4">
                                @if($category->active)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">
                                        Active
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('categories.edit', $category) }}"
                                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-black text-white transition hover:bg-indigo-700"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('categories.destroy', $category) }}"
                                        onsubmit="return confirm('Delete this category?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-black text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            @disabled($category->products_count > 0)
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center font-bold text-slate-500">
                                No categories found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $categories->links() }}
</div>

@endsection
