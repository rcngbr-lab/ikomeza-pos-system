@extends('layouts.app')

@section('content')

<div class="space-y-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-indigo-600">Credit Control</p>
            <h1 class="text-2xl font-black tracking-tight text-slate-950">Accounts Receivable</h1>
            <p class="text-xs text-slate-500">Customer credit exposure, collections, aging, approvals, and statements.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('customers.index') }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 shadow-sm">Customers</a>
            <button type="button" onclick="window.print()" class="inline-flex h-9 items-center rounded-xl bg-slate-950 px-3 text-xs font-black text-white shadow-sm">Print</button>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
        @foreach([
            ['label' => 'Credit Accounts', 'value' => number_format($summary['accounts']), 'tone' => 'text-slate-950'],
            ['label' => 'Outstanding', 'value' => number_format($summary['outstanding']) . ' RWF', 'tone' => 'text-rose-600'],
            ['label' => 'Available Credit', 'value' => number_format($summary['available_credit']) . ' RWF', 'tone' => 'text-emerald-600'],
            ['label' => 'Paid Today', 'value' => number_format($summary['payments']) . ' RWF', 'tone' => 'text-indigo-600'],
            ['label' => 'Overdue', 'value' => number_format($summary['overdue']) . ' RWF', 'tone' => 'text-orange-600'],
            ['label' => 'High Risk', 'value' => number_format($summary['high_risk']), 'tone' => 'text-red-600'],
        ] as $card)
            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-1 text-lg font-black {{ $card['tone'] }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
        <form method="GET" class="grid gap-2 md:grid-cols-[1.1fr_140px_140px_140px_140px_120px_120px_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Search customer, phone, code, account..." class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
            <select name="branch_id" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
                <option value="">All branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((int) request('branch_id') === (int) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="status" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
                <option value="">All status</option>
                @foreach(['ACTIVE','INACTIVE','SUSPENDED','BLOCKED','CLOSED'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="category" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
                <option value="">All categories</option>
                @foreach(['WALK_IN','REGISTERED','VIP','EMPLOYEE','CORPORATE'] as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <select name="risk_level" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
                <option value="">All risk</option>
                @foreach(['LOW','MEDIUM','HIGH','CRITICAL'] as $risk)
                    <option value="{{ $risk }}" @selected(request('risk_level') === $risk)>{{ $risk }}</option>
                @endforeach
            </select>
            <input name="start_date" type="date" value="{{ request('start_date') }}" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
            <input name="end_date" type="date" value="{{ request('end_date') }}" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
            <div class="flex gap-2">
                <button class="h-9 rounded-lg bg-slate-950 px-3 text-xs font-black text-white">Apply</button>
                <a href="{{ route('receivables.index') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">Reset</a>
            </div>
        </form>
    </section>

    <section class="grid gap-3 xl:grid-cols-[1fr_360px]">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                <div>
                    <h2 class="text-sm font-black text-slate-950">Credit Accounts</h2>
                    <p class="text-[11px] text-slate-500">{{ $accounts->total() }} accounts found</p>
                </div>
                <div class="hidden text-[11px] font-bold text-slate-500 sm:block">Use the row actions for profile, payment, collection, and statement.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-slate-950 text-left text-[10px] uppercase tracking-wide text-white">
                        <tr>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2 text-right">Limit</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                            <th class="px-3 py-2 text-right">Available</th>
                            <th class="px-3 py-2">Risk</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($accounts as $account)
                            @php($customer = $account->customer)
                            <tr class="align-top">
                                <td class="px-3 py-2">
                                    <p class="font-black text-slate-950">{{ $customer?->name ?? 'Unknown customer' }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $account->account_number }} · {{ $customer?->phone ?: 'No phone' }}</p>
                                </td>
                                <td class="px-3 py-2">{{ $account->category }}</td>
                                <td class="px-3 py-2 text-right font-bold">{{ number_format($account->credit_limit) }}</td>
                                <td class="px-3 py-2 text-right font-black text-rose-600">{{ number_format($account->current_balance) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-emerald-600">{{ number_format($account->available_credit) }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-1 text-[10px] font-black {{ in_array($account->risk_level, ['HIGH','CRITICAL'], true) ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600' }}">{{ $account->risk_level }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-1 text-[10px] font-black {{ $account->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700' }}">{{ $account->status }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    @if($customer)
                                        <details class="group text-right">
                                            <summary class="inline-flex h-8 cursor-pointer list-none items-center rounded-lg bg-indigo-600 px-3 text-[11px] font-black text-white shadow-sm [&::-webkit-details-marker]:hidden">Manage</summary>
                                            <div class="absolute right-6 z-20 mt-2 w-[min(92vw,620px)] rounded-2xl border border-slate-200 bg-white p-3 text-left shadow-2xl">
                                                <div class="grid gap-3 lg:grid-cols-2">
                                                    <form method="POST" action="{{ route('receivables.customers.profile', $customer) }}" class="space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                                        @csrf
                                                        <p class="text-xs font-black text-slate-950">Credit Profile</p>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <select name="category" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                @foreach(['WALK_IN','REGISTERED','VIP','EMPLOYEE','CORPORATE'] as $category)
                                                                    <option value="{{ $category }}" @selected($account->category === $category)>{{ $category }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select name="risk_level" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                @foreach(['LOW','MEDIUM','HIGH','CRITICAL'] as $risk)
                                                                    <option value="{{ $risk }}" @selected($account->risk_level === $risk)>{{ $risk }}</option>
                                                                @endforeach
                                                            </select>
                                                            <input name="credit_limit" type="number" step="0.01" min="0" value="{{ $account->credit_limit }}" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <input name="credit_period_days" type="number" min="0" max="365" value="{{ $account->credit_period_days }}" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <select name="status" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                @foreach(['ACTIVE','INACTIVE','SUSPENDED','BLOCKED','CLOSED'] as $status)
                                                                    <option value="{{ $status }}" @selected($account->status === $status)>{{ $status }}</option>
                                                                @endforeach
                                                            </select>
                                                            <input name="blocked_reason" value="{{ $account->blocked_reason }}" placeholder="Reason optional" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                        </div>
                                                        <button class="h-9 rounded-lg bg-slate-950 px-3 text-xs font-black text-white">Save Profile</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('receivables.customers.payment', $customer) }}" class="space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                                        @csrf
                                                        <p class="text-xs font-black text-slate-950">Receive Payment</p>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <input name="amount" type="number" step="0.01" min="0.01" max="{{ $account->current_balance }}" placeholder="Amount" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <select name="payment_method" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                @foreach(\App\Models\Sale::PAYMENT_METHOD_LABELS as $method => $label)
                                                                    @if($method !== 'CREDIT')
                                                                        <option value="{{ $method }}">{{ $label }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                            <input name="reference" placeholder="Reference / txn ID" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <input name="notes" placeholder="Notes" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                        </div>
                                                        <button class="h-9 rounded-lg bg-emerald-600 px-3 text-xs font-black text-white">Post Payment</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('receivables.customers.collection', $customer) }}" class="space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-3 lg:col-span-2">
                                                        @csrf
                                                        <p class="text-xs font-black text-slate-950">Collection Follow-up</p>
                                                        <div class="grid gap-2 md:grid-cols-4">
                                                            <select name="stage" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                @foreach(['CURRENT','FIRST_REMINDER','SECOND_REMINDER','FINAL_NOTICE','LEGAL','BAD_DEBT'] as $stage)
                                                                    <option value="{{ $stage }}">{{ $stage }}</option>
                                                                @endforeach
                                                            </select>
                                                            <select name="channel" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                @foreach(['CALL','SMS','EMAIL','VISIT','WHATSAPP','LETTER'] as $channel)
                                                                    <option value="{{ $channel }}">{{ $channel }}</option>
                                                                @endforeach
                                                            </select>
                                                            <input name="commitment_amount" type="number" step="0.01" min="0" placeholder="Commitment amount" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <input name="commitment_date" type="date" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <input name="next_follow_up_at" type="date" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <select name="status" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                                <option value="OPEN">OPEN</option>
                                                                <option value="CLOSED">CLOSED</option>
                                                                <option value="ESCALATED">ESCALATED</option>
                                                            </select>
                                                            <input name="contact_person" placeholder="Contact person" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                            <input name="notes" placeholder="Notes" class="h-9 rounded-lg border-slate-200 bg-white text-xs">
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <button class="h-9 rounded-lg bg-orange-600 px-3 text-xs font-black text-white">Record Follow-up</button>
                                                            <a href="{{ route('receivables.customers.statement', $customer) }}" target="_blank" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">Statement</a>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-8 text-center text-sm font-semibold text-slate-500">No receivables accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-3 py-2">{{ $accounts->links() }}</div>
        </div>

        <aside class="space-y-3">
            <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <h2 class="text-sm font-black text-slate-950">Aging Summary</h2>
                <div class="mt-2 space-y-1 text-xs">
                    @foreach([
                        'current' => 'Current',
                        'days_1_30' => '1-30 Days',
                        'days_31_60' => '31-60 Days',
                        'days_61_90' => '61-90 Days',
                        'days_91_120' => '91-120 Days',
                        'over_120' => 'Over 120 Days',
                    ] as $key => $label)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-1.5">
                            <span class="font-bold text-slate-600">{{ $label }}</span>
                            <span class="font-black text-slate-950">{{ number_format($aging[$key] ?? 0) }} RWF</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <h2 class="text-sm font-black text-slate-950">Pending / Recent Approvals</h2>
                <div class="mt-2 space-y-2">
                    @forelse($approvals as $approval)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-2 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-black text-slate-950">{{ $approval->request_number }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $approval->status === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : ($approval->status === 'REJECTED' ? 'bg-rose-100 text-rose-700' : 'bg-orange-100 text-orange-700') }}">{{ $approval->status }}</span>
                            </div>
                            <p class="mt-1 text-slate-500">{{ $approval->customer?->name ?? 'No customer' }} · {{ number_format($approval->amount) }} RWF</p>
                            @if($approval->status === 'PENDING')
                                <div class="mt-2 flex gap-2">
                                    <form method="POST" action="{{ route('receivables.approvals.approve', $approval) }}">@csrf<button class="h-8 rounded-lg bg-emerald-600 px-2 text-[11px] font-black text-white">Approve</button></form>
                                    <form method="POST" action="{{ route('receivables.approvals.reject', $approval) }}">@csrf<input type="hidden" name="approval_note" value="Rejected from credit control"><button class="h-8 rounded-lg bg-rose-600 px-2 text-[11px] font-black text-white">Reject</button></form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-200 p-3 text-center text-xs font-semibold text-slate-500">No approval activity yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <h2 class="text-sm font-black text-slate-950">Open Collections</h2>
                <div class="mt-2 space-y-2">
                    @forelse($collections as $collection)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-2 text-xs">
                            <p class="font-black text-slate-950">{{ $collection->customer?->name ?? 'Unknown customer' }}</p>
                            <p class="text-slate-500">{{ $collection->stage }} via {{ $collection->channel }}</p>
                            <p class="mt-1 font-semibold text-slate-700">{{ $collection->notes ?: 'No notes recorded.' }}</p>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-200 p-3 text-center text-xs font-semibold text-slate-500">No open collection follow-ups.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </section>
</div>

@endsection
