<x-app-layout>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Production Support</p>
                <h1 class="text-2xl font-black text-slate-950">Error Events</h1>
                <p class="text-sm text-slate-500">Unhandled application errors captured for support review.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <select name="status" class="h-10 rounded-xl border border-slate-200 px-3 text-sm">
                    <option value="">All status</option>
                    @foreach (['OPEN', 'RESOLVED'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <button class="h-10 rounded-xl bg-slate-950 px-4 text-sm font-bold text-white">Filter</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3">{{ $event->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3 font-bold text-rose-600">{{ $event->severity }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $event->message }}</p>
                                <p class="mt-1 max-w-4xl break-words text-xs text-slate-500">{{ $event->context }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $event->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No error events recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $events->links() }}
    </div>
</x-app-layout>
