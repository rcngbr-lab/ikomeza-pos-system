@extends('layouts.app')

@section('content')

<div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">
        My Report
    </p>

    <h1 class="mt-2 text-3xl font-black text-slate-950">
        Cashier Performance
    </h1>

    <p class="mt-3 max-w-2xl text-sm text-slate-500">
        Your personal sales activity is available from the sales screen and is automatically limited to your own receipts.
    </p>

    <a
        href="{{ route('sales.index') }}"
        class="mt-6 inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white"
    >
        Open My Sales
    </a>
</div>

@endsection
