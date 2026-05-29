@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-950 lg:text-4xl">
                Edit Category
            </h1>
            <p class="mt-2 text-sm font-medium text-slate-500">
                Update category details used by products and POS filters.
            </p>
        </div>

        <a
            href="{{ route('categories.index') }}"
            class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50"
        >
            Back
        </a>
    </div>

    @include('categories.form', [
        'category' => $category,
        'action' => route('categories.update', $category),
        'method' => 'PUT',
        'button' => 'Update Category',
    ])
</div>

@endsection
