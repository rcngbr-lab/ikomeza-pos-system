@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Refund Control</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Refund Approvals</h1>
            <p class="mt-1 text-sm text-slate-500">Refunds now require approval before stock restoration and revenue reversal.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-slate-950">Pending Requests</h2>
                <p class="text-sm text-slate-500">Requester and approver must be separated unless the approver is Admin.</p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">
                {{ $refundRequests->where('status', \App\Models\RefundRequest::STATUS_PENDING)->count() }} pending
            </span>
        </div>

        <div class="grid gap-3 lg:hidden">
            @forelse($refundRequests as $request)
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-black text-slate-950">{{ $request->request_number }}</p>
                            <p class="text-xs text-slate-500">{{ $request->sale->receipt_no ?? 'No receipt' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $request->status === \App\Models\RefundRequest::STATUS_PENDING ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ str($request->status)->headline() }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-slate-500">Requester</span>
                            <p class="font-bold text-slate-900">{{ $request->requester->name ?? 'Unknown' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Amount</span>
                            <p class="font-black text-rose-600">{{ number_format($request->amount) }} RWF</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">{{ $request->reason ?: 'No reason provided' }}</p>

                    @if($request->status === \App\Models\RefundRequest::STATUS_PENDING)
                        <div class="mt-3 grid gap-2">
                            <form method="POST" action="{{ route('refund.requests.approve', $request) }}" class="grid gap-2">
                                @csrf
                                <input name="approval_note" placeholder="Approval note optional" class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs">
                                <button class="h-10 rounded-lg bg-emerald-600 text-xs font-black text-white">Approve & Restore Stock</button>
                            </form>
                            <form method="POST" action="{{ route('refund.requests.reject', $request) }}">
                                @csrf
                                <button class="h-10 w-full rounded-lg bg-rose-600 text-xs font-black text-white">Reject</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">
                    No refund requests yet.
                </div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Request</th>
                        <th class="px-4 py-3">Receipt</th>
                        <th class="px-4 py-3">Requester</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($refundRequests as $request)
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $request->request_number }}</td>
                            <td class="px-4 py-3">{{ $request->sale->receipt_no ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $request->requester->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 font-black text-rose-600">{{ number_format($request->amount) }} RWF</td>
                            <td class="px-4 py-3">{{ $request->reason ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $request->status === \App\Models\RefundRequest::STATUS_PENDING ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ str($request->status)->headline() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($request->status === \App\Models\RefundRequest::STATUS_PENDING)
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('refund.requests.approve', $request) }}" class="flex gap-2">
                                            @csrf
                                            <input name="approval_note" placeholder="Note" class="h-9 w-36 rounded-lg border-slate-200 bg-slate-50 text-xs">
                                            <button class="h-9 rounded-lg bg-emerald-600 px-3 text-xs font-black text-white">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('refund.requests.reject', $request) }}">
                                            @csrf
                                            <button class="h-9 rounded-lg bg-rose-600 px-3 text-xs font-black text-white">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm font-semibold text-slate-500">
                                No refund requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $refundRequests->links() }}</div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-4">
            <h2 class="text-lg font-black text-slate-950">Refund History</h2>
            <p class="text-sm text-slate-500">Executed refunds with restored stock and reversed revenue.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Receipt</th>
                        <th class="px-4 py-3">Refunded By</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($refunds as $refund)
                        <tr>
                            <td class="px-4 py-3">{{ $refund->id }}</td>
                            <td class="px-4 py-3">{{ $refund->sale->receipt_no ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $refund->user->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 font-black text-rose-600">{{ number_format($refund->amount ?? 0) }} RWF</td>
                            <td class="px-4 py-3">{{ $refund->reason ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700">
                                    {{ $refund->status ?? 'UNKNOWN' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $refund->refunded_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm font-semibold text-slate-500">
                                No executed refunds yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $refunds->links() }}</div>
    </section>
</div>

@endsection
