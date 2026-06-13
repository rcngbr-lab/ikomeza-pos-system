@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Customer Accounts</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Customers & Credit</h1>
            <p class="mt-1 text-sm text-slate-500">Manage customer credit limits and receivable balances.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" placeholder="Search customers" class="h-10 rounded-xl border-slate-200 bg-white text-sm">
            <button class="h-10 rounded-xl bg-slate-950 px-4 text-sm font-black text-white">Search</button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="POST" action="{{ route('customers.store') }}" class="grid gap-3 lg:grid-cols-[1fr_160px_1fr_130px_auto]">
            @csrf
            <input name="name" placeholder="Customer name" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="phone" placeholder="Phone" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="email" placeholder="Email optional" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="credit_limit" type="number" step="0.01" min="0" placeholder="Credit limit" class="h-11 rounded-xl border-slate-200 bg-slate-50 text-sm">
            <button class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-black text-white">Create</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-950 text-left text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Balance</th>
                        <th class="px-4 py-3">Credit Limit</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-black text-slate-950">{{ $customer->name }}</p>
                                <p class="text-xs text-slate-500">{{ $customer->customer_code }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $customer->phone ?: '-' }}</td>
                            <td class="px-4 py-3 font-black text-rose-600">{{ number_format($customer->balance) }} RWF</td>
                            <td class="px-4 py-3">{{ number_format($customer->credit_limit) }} RWF</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $customer->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $customer->status }}</span>
                            </td>
                            <td class="px-4 py-3 space-y-2">
                                @if(auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'))
                                    <form method="POST" action="{{ route('customers.update', $customer) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input name="credit_limit" type="number" step="0.01" min="0" value="{{ $customer->credit_limit }}" class="h-9 w-28 rounded-lg border-slate-200 bg-slate-50 text-xs">
                                        <select name="status" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
                                            <option value="ACTIVE" @selected($customer->status === 'ACTIVE')>Active</option>
                                            <option value="INACTIVE" @selected($customer->status === 'INACTIVE')>Inactive</option>
                                        </select>
                                        <button class="h-9 rounded-lg bg-slate-950 px-3 text-xs font-black text-white">Save</button>
                                    </form>
                                @endif
                                @if((float) $customer->balance > 0 && auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'CASHIER'))
                                    <form method="POST" action="{{ route('customers.payment', $customer) }}" class="flex flex-wrap gap-2">
                                        @csrf
                                        <input name="amount" type="number" step="0.01" min="0.01" max="{{ $customer->balance }}" placeholder="Payment" class="h-9 w-28 rounded-lg border-slate-200 bg-slate-50 text-xs">
                                        <select name="payment_method" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-xs">
                                            @foreach(\App\Models\Sale::PAYMENT_METHOD_LABELS as $method => $label)
                                                @if($method !== 'CREDIT')
                                                    <option value="{{ $method }}">{{ $label }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button class="h-9 rounded-lg bg-emerald-600 px-3 text-xs font-black text-white">Receive</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm font-semibold text-slate-500">No customers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </section>
</div>

@endsection
