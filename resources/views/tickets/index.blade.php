@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Kitchen / Bar Workflow</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Order Tickets</h1>
            <p class="mt-1 text-sm text-slate-500">Unified POS receipts split internally into Kitchen KOT and Bar BOT.</p>
        </div>
        <form method="GET" class="grid gap-2 sm:grid-cols-3">
            <select name="type" class="h-10 rounded-xl border-slate-200 bg-white text-sm">
                <option value="">All tickets</option>
                <option value="KOT" @selected(request('type') === 'KOT')>Kitchen KOT</option>
                <option value="BOT" @selected(request('type') === 'BOT')>Bar BOT</option>
            </select>
            <select name="status" class="h-10 rounded-xl border-slate-200 bg-white text-sm">
                <option value="">All status</option>
                @foreach(['PENDING', 'ACCEPTED', 'READY', 'SERVED', 'CANCELLED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
                @endforeach
            </select>
            <button class="h-10 rounded-xl bg-indigo-600 px-4 text-sm font-black text-white">Filter</button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse($tickets as $ticket)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide {{ $ticket->ticket_type === 'BOT' ? 'text-indigo-600' : 'text-amber-600' }}">
                            {{ $ticket->ticket_type }}
                        </p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">{{ $ticket->ticket_number }}</h2>
                        <p class="text-xs text-slate-500">{{ $ticket->sale->receipt_no ?? 'No receipt' }} - {{ $ticket->sale->user->name ?? 'Unknown' }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $ticket->status === 'READY' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ str($ticket->status)->headline() }}
                    </span>
                </div>

                <div class="mt-4 space-y-2">
                    @foreach($ticket->items as $item)
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm">
                            <span class="font-bold text-slate-800">{{ $item->product_name }}</span>
                            <span class="font-black text-slate-950">x{{ number_format((float) $item->quantity, 3) }}</span>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="mt-4 grid grid-cols-2 gap-2">
                    @csrf
                    <select name="status" class="h-10 rounded-xl border-slate-200 bg-slate-50 text-xs font-bold">
                        @foreach(['ACCEPTED', 'READY', 'SERVED', 'CANCELLED'] as $status)
                            <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                    <button class="h-10 rounded-xl bg-slate-950 text-xs font-black text-white">Update</button>
                </form>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm font-semibold text-slate-500 md:col-span-2 xl:col-span-3">
                No order tickets match this view.
            </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>

@endsection

