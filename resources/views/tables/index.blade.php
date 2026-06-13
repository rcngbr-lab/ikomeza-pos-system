@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Restaurant Floor</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Tables</h1>
        <p class="mt-1 text-sm text-slate-500">Manage table availability for waiter/server POS orders.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="POST" action="{{ route('tables.store') }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_120px_auto]">
            @csrf
            <input name="name" placeholder="Table name" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="section" placeholder="Section" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="seats" type="number" min="1" value="4" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <button class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-black text-white">Add Table</button>
        </form>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($tables as $table)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">{{ $table->name }}</h2>
                        <p class="text-xs text-slate-500">{{ $table->section ?: 'Main floor' }} - {{ $table->seats }} seats</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $table->status === 'AVAILABLE' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ str($table->status)->headline() }}
                    </span>
                </div>

                <form method="POST" action="{{ route('tables.status', $table) }}" class="mt-4 grid grid-cols-[1fr_auto] gap-2">
                    @csrf
                    <select name="status" class="h-10 rounded-xl border-slate-200 bg-slate-50 text-xs font-bold">
                        @foreach(['AVAILABLE', 'OCCUPIED', 'RESERVED', 'OUT_OF_SERVICE'] as $status)
                            <option value="{{ $status }}" @selected($table->status === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                    <button class="h-10 rounded-xl bg-slate-950 px-3 text-xs font-black text-white">Save</button>
                </form>
            </article>
        @endforeach
    </div>
</div>

@endsection

