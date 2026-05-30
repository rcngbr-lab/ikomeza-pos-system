@extends('layouts.app')

@section('content')

@php
    $periodValue = request('date_filter', request('period', request('filter', 'all')));
    $query = request()->query();

    $severityClass = function (?string $severity) {
        return match (strtoupper((string) $severity)) {
            'SECURITY' => 'bg-purple-100 text-purple-700 border-purple-200',
            'CRITICAL' => 'bg-red-100 text-red-700 border-red-200',
            'WARNING' => 'bg-amber-100 text-amber-700 border-amber-200',
            default => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        };
    };

    $actionClass = function (?string $action) {
        $action = strtoupper((string) $action);

        return match (true) {
            str_contains($action, 'DELETE'),
            str_contains($action, 'REJECT'),
            str_contains($action, 'FAILED') => 'bg-red-50 text-red-700 ring-red-200',
            str_contains($action, 'APPROV'),
            str_contains($action, 'COMPLETED'),
            str_contains($action, 'SUCCESS'),
            str_contains($action, 'STOCK_IN') => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            str_contains($action, 'PRICE'),
            str_contains($action, 'REFUND'),
            str_contains($action, 'ROLE'),
            str_contains($action, 'PERMISSION') => 'bg-amber-50 text-amber-700 ring-amber-200',
            str_contains($action, 'LOGIN'),
            str_contains($action, 'LOGOUT'),
            str_contains($action, 'SECURITY') => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    };

    $jsonPreview = function ($value) {
        if (empty($value)) {
            return '-';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-';
    };
@endphp

<style>
    @media print {
        aside,
        nav,
        header,
        .no-print {
            display: none !important;
        }

        main {
            padding: 0 !important;
        }

        .audit-print-area {
            box-shadow: none !important;
            border: 0 !important;
        }
    }
</style>

<div class="audit-print-area space-y-5">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Accountability Center</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Audit Logs</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                Searchable business trail for sales, security, inventory, approvals, staff actions, and operational control.
            </p>
        </div>

        @if($canExport)
            <div class="no-print grid grid-cols-2 gap-2 sm:flex">
                <a href="{{ route('audit.logs.print', $query) }}" target="_blank" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-black text-slate-700 shadow-sm">
                    Print
                </a>
                <a href="{{ route('audit.logs.export', array_merge($query, ['format' => 'csv'])) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-black text-slate-700 shadow-sm">
                    CSV
                </a>
                <a href="{{ route('audit.logs.export', array_merge($query, ['format' => 'excel'])) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-black text-slate-700 shadow-sm">
                    Excel
                </a>
                <a href="{{ route('audit.logs.export', array_merge($query, ['format' => 'pdf'])) }}" class="rounded-xl bg-slate-950 px-4 py-3 text-center text-sm font-black text-white shadow-sm">
                    PDF
                </a>
            </div>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        @foreach([
            ['label' => 'Total Logs', 'value' => $summary['total'], 'tone' => 'text-slate-950'],
            ['label' => 'Today Activities', 'value' => $summary['today'], 'tone' => 'text-indigo-600'],
            ['label' => 'Security Alerts', 'value' => $summary['security'], 'tone' => 'text-purple-600'],
            ['label' => 'Critical Actions', 'value' => $summary['critical'], 'tone' => 'text-red-600'],
            ['label' => 'Stock Changes', 'value' => $summary['stock'], 'tone' => 'text-amber-600'],
            ['label' => 'Financial Actions', 'value' => $summary['financial'], 'tone' => 'text-emerald-600'],
            ['label' => 'Pending Approvals', 'value' => $summary['pending_approvals'], 'tone' => 'text-orange-600'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-black {{ $card['tone'] }}">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('audit.logs') }}" class="no-print rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" id="auditFilterForm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Search action, reference, user, IP..." class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">

            <select name="date_filter" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($periods as $value => $label)
                    <option value="{{ $value }}" @selected($periodValue === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <input type="date" name="start_date" value="{{ request('start_date') }}" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">

            <select name="user_id" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Staff</option>
                @foreach($users as $staff)
                    <option value="{{ $staff->id }}" @selected((int) request('user_id') === (int) $staff->id)>
                        {{ $staff->name }} - {{ $staff->email }}
                    </option>
                @endforeach
            </select>

            <input name="user_search" value="{{ request('user_search') }}" placeholder="User name or email" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">

            <select name="role" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Roles</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="department_id" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Departments</option>
                <option value="global" @selected(request('department_id') === 'global')>Global</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) request('department_id') === (int) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>

            <select name="module" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Modules</option>
                @foreach($modules as $module)
                    <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                @endforeach
            </select>

            <select name="action" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>

            <select name="severity" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Severities</option>
                @foreach($severities as $severity)
                    <option value="{{ $severity }}" @selected(request('severity') === $severity)>{{ $severity }}</option>
                @endforeach
            </select>

            <input name="reference" value="{{ request('reference') }}" placeholder="Receipt, requisition, product..." class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">

            <select name="per_page" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($perPageOptions as $option)
                    <option value="{{ $option }}" @selected((int) request('per_page', 20) === $option)>{{ $option }} per page</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-semibold text-slate-500">
                Custom dates use start date 00:00:00 and end date 23:59:59.
            </p>
            <div class="flex gap-2">
                <a href="{{ route('audit.logs') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-black text-slate-700">
                    Reset
                </a>
                <button id="applyAuditFilters" class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-black text-white shadow-lg shadow-indigo-600/20">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>

    <div id="auditLoading" class="no-print hidden rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm font-black text-indigo-700">
        Loading filtered audit logs...
    </div>

    <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px]">
                <thead class="bg-slate-950 text-left text-xs font-black uppercase tracking-wider text-white">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Module</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-4 text-sm font-black text-slate-950">#{{ $log->id }}</td>
                            <td class="px-4 py-4">
                                <p class="text-sm font-black text-slate-950">{{ $log->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-slate-500">{{ $log->user->email ?? 'System event' }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm font-semibold text-slate-600">{{ $log->role_name ?: '-' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $actionClass($log->displayAction()) }}">
                                    {{ str($log->displayAction())->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm font-black text-slate-700">{{ $log->displayModule() }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-700">
                                    {{ $log->department->name ?? 'Global' }}
                                </span>
                            </td>
                            <td class="max-w-xs px-4 py-4 text-sm text-slate-600">{{ $log->description ?: '-' }}</td>
                            <td class="px-4 py-4 text-sm font-black text-slate-950">{{ $log->displayReference() }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $severityClass($log->severity) }}">
                                    {{ $log->severity ?: 'INFO' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $log->ip_address ?: '-' }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-2">
                                    <a href="{{ route('audit.logs.show', $log) }}" class="rounded-lg bg-slate-950 px-3 py-2 text-center text-xs font-black text-white">
                                        View
                                    </a>
                                    <details class="group">
                                        <summary class="cursor-pointer rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-black text-slate-700">Expand</summary>
                                        <div class="absolute right-6 z-20 mt-2 w-[520px] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl">
                                            <div class="grid grid-cols-3 gap-3 text-xs">
                                                <div><span class="font-black text-slate-400">Amount</span><p class="font-black">{{ $log->amount ? number_format($log->amount) . ' RWF' : '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">Before</span><p class="font-black">{{ $log->quantity_before ?? '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">Changed</span><p class="font-black">{{ $log->quantity_changed ?? '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">After</span><p class="font-black">{{ $log->quantity_after ?? '-' }}</p></div>
                                                <div class="col-span-2"><span class="font-black text-slate-400">Device</span><p class="font-black">{{ $log->device ?: '-' }}</p></div>
                                            </div>
                                            <pre class="mt-3 max-h-48 overflow-auto rounded-xl bg-slate-950 p-3 text-[11px] text-slate-100">{{ $jsonPreview(['old' => $log->old_values, 'new' => $log->new_values, 'metadata' => $log->metadata]) }}</pre>
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center text-sm font-semibold text-slate-500">
                                No audit logs match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-3 md:hidden">
        @forelse($logs as $log)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">#{{ $log->id }} - {{ $log->displayModule() }}</p>
                        <h2 class="mt-1 text-base font-black text-slate-950">{{ str($log->displayAction())->replace('_', ' ')->title() }}</h2>
                    </div>
                    <span class="rounded-full border px-2.5 py-1 text-[11px] font-black {{ $severityClass($log->severity) }}">
                        {{ $log->severity ?: 'INFO' }}
                    </span>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ $log->description ?: '-' }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div><span class="font-black text-slate-400">User</span><p class="font-bold">{{ $log->user->name ?? 'Unknown' }}</p></div>
                    <div><span class="font-black text-slate-400">Department</span><p class="font-bold">{{ $log->department->name ?? 'Global' }}</p></div>
                    <div><span class="font-black text-slate-400">Reference</span><p class="font-bold">{{ $log->displayReference() }}</p></div>
                    <div><span class="font-black text-slate-400">Date</span><p class="font-bold">{{ optional($log->created_at)->format('Y-m-d H:i') }}</p></div>
                </div>
                <a href="{{ route('audit.logs.show', $log) }}" class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-xl bg-slate-950 text-sm font-black text-white">
                    View Details
                </a>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-semibold text-slate-500">
                No audit logs match the selected filters.
            </div>
        @endforelse
    </section>

    <div class="no-print rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        {{ $logs->onEachSide(1)->links() }}
    </div>
</div>

<script>
    document.getElementById('auditFilterForm')?.addEventListener('submit', () => {
        document.getElementById('auditLoading')?.classList.remove('hidden');
        document.getElementById('applyAuditFilters')?.setAttribute('disabled', 'disabled');
    });
</script>

@endsection
