@extends('layouts.app')

@section('content')
<div class="min-h-screen space-y-6 bg-slate-100 pb-28">
    <div>
        <p class="text-xs font-black uppercase tracking-widest text-indigo-600">Store Management</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950 md:text-4xl">Suppliers</h1>
        <p class="mt-2 text-sm text-slate-500">Create suppliers and track purchase sources for Kitchen, Bar, and Main Store.</p>
    </div>

    @include('store._nav')
    @include('store._filters', ['action' => route('store.suppliers')])

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-black text-slate-950">New Supplier</h2>
        <form method="POST" action="{{ route('store.suppliers.store') }}" class="mt-4 grid gap-3 md:grid-cols-4">
            @csrf
            <input name="company_name" value="{{ old('company_name') }}" required placeholder="Company name" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="contact_person" value="{{ old('contact_person') }}" placeholder="Contact person" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="phone" value="{{ old('phone') }}" placeholder="Phone" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="email" value="{{ old('email') }}" placeholder="Email" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="payment_terms" value="{{ old('payment_terms') }}" placeholder="Payment terms" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <input name="supplied_categories" value="{{ old('supplied_categories') }}" placeholder="Categories supplied" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
            <select name="department_id" class="rounded-xl border-slate-200 bg-slate-50 text-sm">
                <option value="">Serves all departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white">Save Supplier</button>
            <textarea name="address" rows="2" placeholder="Address" class="rounded-xl border-slate-200 bg-slate-50 text-sm md:col-span-2">{{ old('address') }}</textarea>
            <textarea name="notes" rows="2" placeholder="Notes" class="rounded-xl border-slate-200 bg-slate-50 text-sm md:col-span-2">{{ old('notes') }}</textarea>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-xl font-black text-slate-950">Supplier Directory</h2>
        </div>

        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($suppliers as $supplier)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-slate-950">{{ $supplier->company_name }}</p>
                            <p class="text-sm text-slate-500">{{ $supplier->contact_person ?: 'No contact person' }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">{{ $supplier->status }}</span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p><span class="font-bold text-slate-800">Phone:</span> {{ $supplier->phone ?: '-' }}</p>
                        <p><span class="font-bold text-slate-800">Email:</span> {{ $supplier->email ?: '-' }}</p>
                        <p><span class="font-bold text-slate-800">Department:</span> {{ $supplier->department->name ?? 'Global' }}</p>
                        <p><span class="font-bold text-slate-800">Categories:</span> {{ $supplier->supplied_categories ?: '-' }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-400 md:col-span-2 xl:col-span-3">
                    No suppliers found.
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 p-5">{{ $suppliers->links() }}</div>
    </section>
</div>
@endsection
