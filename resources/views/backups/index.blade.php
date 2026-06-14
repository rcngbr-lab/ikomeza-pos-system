<x-app-layout>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Data Protection</p>
                <h1 class="text-2xl font-black text-slate-950">Backups</h1>
                <p class="text-sm text-slate-500">Create verified database and storage backups before risky operations.</p>
            </div>

            <form method="POST" action="{{ route('backups.store') }}" class="flex gap-2">
                @csrf
                <input name="backup_name" class="h-10 rounded-xl border border-slate-200 px-3 text-sm" placeholder="optional-name">
                <button class="h-10 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-sm">Create Backup</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Size</th>
                        <th class="px-4 py-3">Path</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($backups as $backup)
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $backup->backup_name }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2 py-1 text-xs font-bold',
                                    'bg-emerald-100 text-emerald-700' => $backup->status === 'COMPLETED',
                                    'bg-rose-100 text-rose-700' => $backup->status === 'FAILED',
                                    'bg-amber-100 text-amber-700' => ! in_array($backup->status, ['COMPLETED', 'FAILED'], true),
                                ])>{{ $backup->status }}</span>
                            </td>
                            <td class="px-4 py-3">{{ number_format(($backup->size_bytes ?? 0) / 1024, 1) }} KB</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $backup->path }}</td>
                            <td class="px-4 py-3">{{ $backup->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $backup->notes }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No backups recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $backups->links() }}
    </div>
</x-app-layout>
