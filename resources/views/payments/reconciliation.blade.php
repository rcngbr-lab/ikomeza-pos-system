<x-app-layout>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Financial Control</p>
                <h1 class="text-2xl font-black text-slate-950">Payment Reconciliation</h1>
                <p class="text-sm text-slate-500">Review unmatched MOMO, Airtel Money, card, and bank payments.</p>
            </div>

            <form method="GET" class="grid gap-2 sm:grid-cols-4">
                <input name="search" value="{{ request('search') }}" class="h-10 rounded-xl border border-slate-200 px-3 text-sm" placeholder="Receipt, ref, transaction">
                <select name="method" class="h-10 rounded-xl border border-slate-200 px-3 text-sm">
                    <option value="">All Methods</option>
                    @foreach (['MOMO', 'AIRTEL_MONEY', 'VISA', 'MASTER_CARD', 'BANK_TRANSFER'] as $method)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ str_replace('_', ' ', $method) }}</option>
                    @endforeach
                </select>
                <select name="status" class="h-10 rounded-xl border border-slate-200 px-3 text-sm">
                    <option value="">Open Issues</option>
                    @foreach (['UNMATCHED', 'MATCHED', 'EXCEPTION', 'NOT_REQUIRED'] as $status)
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
                        <th class="px-4 py-3">Receipt</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Paid</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $payment->sale?->receipt_no ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ str_replace('_', ' ', $payment->method) }}</td>
                            <td class="px-4 py-3 font-bold">{{ number_format((float) $payment->amount) }} RWF</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $payment->payment_reference ?: $payment->reference ?: $payment->transaction_id ?: 'Missing' }}
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2 py-1 text-xs font-bold',
                                    'bg-emerald-100 text-emerald-700' => $payment->reconciliation_status === 'MATCHED',
                                    'bg-rose-100 text-rose-700' => $payment->reconciliation_status === 'EXCEPTION',
                                    'bg-amber-100 text-amber-700' => $payment->reconciliation_status === 'UNMATCHED',
                                    'bg-slate-100 text-slate-600' => ! in_array($payment->reconciliation_status, ['MATCHED', 'EXCEPTION', 'UNMATCHED'], true),
                                ])>{{ $payment->reconciliation_status }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('payments.match', $payment) }}">
                                        @csrf
                                        <button class="h-9 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white">Matched</button>
                                    </form>
                                    <form method="POST" action="{{ route('payments.exception', $payment) }}" class="flex gap-1">
                                        @csrf
                                        <input name="notes" class="h-9 w-32 rounded-lg border border-slate-200 px-2 text-xs" placeholder="Reason" required>
                                        <button class="h-9 rounded-lg bg-rose-600 px-3 text-xs font-bold text-white">Exception</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No payments match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $payments->links() }}
    </div>
</x-app-layout>
