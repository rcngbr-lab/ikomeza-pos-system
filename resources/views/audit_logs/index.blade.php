@extends('layouts.app')

@section('content')
@php
    $periodValue = request('date_filter', request('period', request('filter', 'all')));
    $query = request()->query();
    $severityClass = function (?string $severity) {
        return match (strtoupper((string) $severity)) {
            'CRITICAL' => 'bg-rose-100 text-rose-700',
            'SECURITY' => 'bg-purple-100 text-purple-700',
            'WARNING' => 'bg-amber-100 text-amber-700',
            default => 'bg-emerald-100 text-emerald-700',
        };
    };
    $actionClass = function (?string $action) {
        $action = strtoupper((string) $action);

        return match (true) {
            str_contains($action, 'DELETE'), str_contains($action, 'REJECT'), str_contains($action, 'FAILED') => 'bg-rose-100 text-rose-700',
            str_contains($action, 'APPROV'), str_contains($action, 'COMPLETED'), str_contains($action, 'SUCCESS'), str_contains($action, 'STOCK_IN') => 'bg-emerald-100 text-emerald-700',
            str_contains($action, 'PRICE'), str_contains($action, 'REFUND'), str_contains($action, 'ROLE'), str_contains($action, 'PERMISSION') => 'bg-amber-100 text-amber-700',
            str_contains($action, 'LOGIN'), str_contains($action, 'LOGOUT'), str_contains($action, 'SECURITY') => 'bg-indigo-100 text-indigo-700',
            default => 'bg-slate-100 text-slate-700',
        };
    };
    $jsonPreview = function ($value) {
        if (empty($value)) {
            return '-';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-';
    };
@endphp

<div class="dense-page">
    <div class="dense-header">
        <div>
            <p class="dense-eyebrow">Accountability</p>
            <h1 class="dense-title">Audit Logs</h1>
            <p class="dense-subtitle">Trace users, modules, stock/financial impact, IP, and security activity.</p>
        </div>

        @if($canExport)
            <div class="grid grid-cols-2 gap-2 sm:flex">
                <a href="{{ route('audit.logs.print', $query) }}" target="_blank" class="dense-btn-soft">Print</a>
                <a href="{{ route('audit.logs.export', array_merge($query, ['format' => 'csv'])) }}" class="dense-btn-soft">CSV</a>
                <a href="{{ route('audit.logs.export', array_merge($query, ['format' => 'excel'])) }}" class="dense-btn-soft">Excel</a>
                <a href="{{ route('audit.logs.export', array_merge($query, ['format' => 'pdf'])) }}" class="dense-btn-dark">PDF</a>
            </div>
        @endif
    </div>

    <div class="dense-stat-row xl:grid-cols-7">
        @foreach([
            ['label' => 'Total', 'value' => $summary['total'], 'tone' => 'text-slate-950'],
            ['label' => 'Today', 'value' => $summary['today'], 'tone' => 'text-indigo-600'],
            ['label' => 'Security', 'value' => $summary['security'], 'tone' => 'text-purple-600'],
            ['label' => 'Critical', 'value' => $summary['critical'], 'tone' => 'text-rose-600'],
            ['label' => 'Stock', 'value' => $summary['stock'], 'tone' => 'text-amber-600'],
            ['label' => 'Financial', 'value' => $summary['financial'], 'tone' => 'text-emerald-600'],
            ['label' => 'Approvals', 'value' => $summary['pending_approvals'], 'tone' => 'text-orange-600'],
        ] as $metric)
            <div class="dense-stat">
                <p class="dense-stat-label">{{ $metric['label'] }}</p>
                <p class="dense-stat-value {{ $metric['tone'] }}">{{ number_format($metric['value']) }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('audit.logs') }}" class="dense-toolbar" id="auditFilterForm">
        <select name="date_filter" class="dense-select md:w-44">
            @foreach($periods as $value => $label)
                <option value="{{ $value }}" @selected($periodValue === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="start_date" value="{{ request('start_date') }}" class="dense-input md:w-40">
        <input type="date" name="end_date" value="{{ request('end_date') }}" class="dense-input md:w-40">

        <select name="user_id" class="dense-select md:w-52">
            <option value="">All users</option>
            @foreach($users as $staff)
                <option value="{{ $staff->id }}" @selected((int) request('user_id') === (int) $staff->id)>
                    {{ $staff->name }} - {{ $staff->username ?? $staff->email }}
                </option>
            @endforeach
        </select>

        <select name="module" class="dense-select md:w-44">
            <option value="">All modules</option>
            @foreach($modules as $module)
                <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
            @endforeach
        </select>

        <select name="action" class="dense-select md:w-48">
            <option value="">All actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>

        <select name="severity" class="dense-select md:w-40">
            <option value="">All severity</option>
            @foreach($severities as $severity)
                <option value="{{ $severity }}" @selected(request('severity') === $severity)>{{ $severity }}</option>
            @endforeach
        </select>

        <select name="department_id" class="dense-select md:w-44">
            <option value="">All departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) request('department_id') === (int) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>

        <select name="role" class="dense-select md:w-44">
            <option value="">All roles</option>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="per_page" class="dense-select md:w-36">
            @foreach($perPageOptions as $option)
                <option value="{{ $option }}" @selected((int) request('per_page', 20) === $option)>{{ $option }} / page</option>
            @endforeach
        </select>

        <input type="search" name="search" value="{{ request('search') }}" placeholder="Reference, user, IP, action..." class="dense-input min-w-0 flex-1">

        <div class="grid grid-cols-2 gap-2 md:flex">
            <button class="dense-btn-dark">Apply</button>
            <a href="{{ route('audit.logs') }}" class="dense-btn-soft">Reset</a>
        </div>
    </form>

    <section class="dense-card">
        <div class="dense-card-header">
            <div>
                <h2 class="text-sm font-black text-slate-950">Audit Trail</h2>
                <p class="text-xs text-slate-500">Filter values persist through pagination and export.</p>
            </div>
            <p class="text-xs font-bold text-slate-500">{{ $logs->total() }} records</p>
        </div>

        <div class="dense-table-wrap">
            <table class="dense-table min-w-[1180px]">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Department</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Severity</th>
                        <th>IP</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap">{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <p class="font-black text-slate-950">{{ $log->user->name ?? 'Unknown' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $log->user->username ?? $log->user->email ?? 'System event' }}</p>
                            </td>
                            <td>{{ $log->role_name ?: '-' }}</td>
                            <td><span class="dense-badge {{ $actionClass($log->displayAction()) }}">{{ str($log->displayAction())->replace('_', ' ')->title() }}</span></td>
                            <td class="font-black text-slate-700">{{ $log->displayModule() }}</td>
                            <td>{{ $log->department->name ?? 'Global' }}</td>
                            <td class="font-black text-slate-950">{{ $log->displayReference() }}</td>
                            <td class="max-w-xs truncate">{{ $log->description ?: '-' }}</td>
                            <td><span class="dense-badge {{ $severityClass($log->severity) }}">{{ $log->severity ?: 'INFO' }}</span></td>
                            <td>{{ $log->ip_address ?: '-' }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('audit.logs.show', $log) }}" class="dense-btn-soft">View</a>
                                    <details class="relative">
                                        <summary class="dense-btn-dark cursor-pointer list-none [&::-webkit-details-marker]:hidden">Expand</summary>
                                        <div class="absolute right-0 z-20 mt-2 w-[28rem] rounded-xl border border-slate-200 bg-white p-3 text-left shadow-2xl">
                                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                                <div><span class="font-black text-slate-400">Amount</span><p class="font-black">{{ $log->amount ? number_format($log->amount) . ' RWF' : '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">Device</span><p class="font-black">{{ $log->device ?: '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">Before</span><p class="font-black">{{ $log->quantity_before ?? '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">Changed</span><p class="font-black">{{ $log->quantity_changed ?? '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">After</span><p class="font-black">{{ $log->quantity_after ?? '-' }}</p></div>
                                                <div><span class="font-black text-slate-400">Branch</span><p class="font-black">{{ $log->branch->name ?? '-' }}</p></div>
                                            </div>
                                            <pre class="mt-3 max-h-44 overflow-auto rounded-lg bg-slate-950 p-2 text-[10px] text-slate-100">{{ $jsonPreview(['old' => $log->old_values, 'new' => $log->new_values, 'metadata' => $log->metadata]) }}</pre>
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="dense-empty">No logs match the active filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-3 py-2">
            {{ $logs->onEachSide(1)->links() }}
        </div>
    </section>
</div>
@endsection
