@extends('layouts.app')

@section('content')

@php
    $jsonBlock = function ($value) {
        return $value ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-';
    };
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Audit Details</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Log #{{ $auditLog->id }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $auditLog->description ?: 'No description recorded.' }}</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white">Print</button>
            <a href="{{ route('audit.logs') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700">Back</a>
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
            'User' => $auditLog->user?->name ?? 'Unknown',
            'Role' => $auditLog->role_name ?: '-',
            'Action' => str($auditLog->displayAction())->replace('_', ' ')->title(),
            'Module' => $auditLog->displayModule(),
            'Department' => $auditLog->department?->name ?? 'Global',
            'Branch' => $auditLog->branch?->name ?? '-',
            'Reference' => $auditLog->displayReference(),
            'Severity' => $auditLog->severity ?: 'INFO',
        ] as $label => $value)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-black uppercase tracking-wide text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-lg font-black text-slate-950">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Record</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Model</dt><dd class="font-black">{{ $auditLog->model_type ?: $auditLog->model ?: '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Record ID</dt><dd class="font-black">{{ $auditLog->model_id ?: '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Amount</dt><dd class="font-black">{{ $auditLog->amount ? number_format($auditLog->amount) . ' RWF' : '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Timestamp</dt><dd class="font-black">{{ optional($auditLog->created_at)->format('Y-m-d H:i:s') }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Stock Impact</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Before</dt><dd class="font-black">{{ $auditLog->quantity_before ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Changed</dt><dd class="font-black">{{ $auditLog->quantity_changed ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">After</dt><dd class="font-black">{{ $auditLog->quantity_after ?? '-' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Security Context</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="font-bold text-slate-500">IP Address</dt><dd class="mt-1 font-black">{{ $auditLog->ip_address ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Device</dt><dd class="mt-1 font-black">{{ $auditLog->device ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Browser / Agent</dt><dd class="mt-1 break-words text-xs font-semibold text-slate-600">{{ $auditLog->user_agent ?: '-' }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Old Values</h2>
            <pre class="mt-4 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ $jsonBlock($auditLog->old_values) }}</pre>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">New Values</h2>
            <pre class="mt-4 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ $jsonBlock($auditLog->new_values) }}</pre>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Metadata / Approval Chain</h2>
            <pre class="mt-4 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ $jsonBlock($auditLog->metadata) }}</pre>
        </div>
    </section>
</div>

@endsection
