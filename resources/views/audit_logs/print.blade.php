<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Audit Logs Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 10px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }
        h1 { margin: 0; font-size: 22px; }
        .meta { text-align: right; line-height: 1.6; color: #475569; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #111827; color: #fff; padding: 6px; text-align: left; font-size: 8px; text-transform: uppercase; }
        td { border: 1px solid #dbe3ef; padding: 6px; vertical-align: top; }
        tr { page-break-inside: avoid; }
        .badge { font-weight: 900; }
        .print-button { margin-bottom: 12px; }
        @media print { .print-button { display: none; } }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Print / Save PDF</button>

    <div class="header">
        <div>
            <div style="font-weight:900; letter-spacing:.08em;">IKOMEZA POS</div>
            <h1>Audit Logs Report</h1>
        </div>
        <div class="meta">
            <div>Generated: {{ now()->format('Y-m-d H:i') }}</div>
            <div>Records: {{ number_format($logs->count()) }}</div>
            <div>Filters: {{ http_build_query($filters) ?: 'None' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>Module</th>
                <th>Department</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Severity</th>
                <th>IP</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>#{{ $log->id }}</td>
                    <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                    <td>{{ $log->role_name ?: '-' }}</td>
                    <td class="badge">{{ $log->displayAction() }}</td>
                    <td>{{ $log->displayModule() }}</td>
                    <td>{{ $log->department?->name ?? 'Global' }}</td>
                    <td>{{ $log->description ?: '-' }}</td>
                    <td>{{ $log->displayReference() }}</td>
                    <td class="badge">{{ $log->severity ?: 'INFO' }}</td>
                    <td>{{ $log->ip_address ?: '-' }}</td>
                    <td>{{ optional($log->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; font-weight:900;">No audit logs match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
