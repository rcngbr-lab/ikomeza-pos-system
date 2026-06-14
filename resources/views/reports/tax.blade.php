<x-app-layout>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Rwanda VAT Readiness</p>
                <h1 class="text-2xl font-black text-slate-950">VAT Report</h1>
                <p class="text-sm text-slate-500">Taxable sales, VAT collected, and gross receipts for the selected period.</p>
            </div>

            <form method="GET" class="grid gap-2 sm:grid-cols-4">
                <select name="filter" class="h-10 rounded-xl border border-slate-200 px-3 text-sm">
                    @foreach (['today' => 'Today', 'month' => 'This Month', 'last_month' => 'Last Month', 'year' => 'This Year'] as $value => $label)
                        <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="h-10 rounded-xl border border-slate-200 px-3 text-sm">
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="h-10 rounded-xl border border-slate-200 px-3 text-sm">
                <button class="h-10 rounded-xl bg-slate-950 px-4 text-sm font-bold text-white">Apply</button>
            </form>
        </div>

        <div class="grid gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Receipts</p>
                <p class="text-xl font-black">{{ number_format($report['summary']['receipts']) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Taxable Sales</p>
                <p class="text-xl font-black">{{ number_format($report['summary']['taxable']) }} RWF</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">VAT Collected</p>
                <p class="text-xl font-black text-emerald-600">{{ number_format($report['summary']['vat']) }} RWF</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs text-slate-500">Gross Receipts</p>
                <p class="text-xl font-black">{{ number_format($report['summary']['gross']) }} RWF</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Receipts</th>
                        <th class="px-4 py-3">Taxable</th>
                        <th class="px-4 py-3">VAT</th>
                        <th class="px-4 py-3">Gross</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['daily'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $row->report_date }}</td>
                            <td class="px-4 py-3">{{ number_format($row->receipts) }}</td>
                            <td class="px-4 py-3">{{ number_format($row->taxable) }}</td>
                            <td class="px-4 py-3">{{ number_format($row->vat) }}</td>
                            <td class="px-4 py-3">{{ number_format($row->gross) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No VAT data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
