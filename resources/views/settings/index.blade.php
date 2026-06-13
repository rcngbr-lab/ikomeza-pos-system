@extends('layouts.app')

@section('content')

<div class="space-y-5">
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">System Control</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Business Settings</h1>
        <p class="mt-1 text-sm text-slate-500">VAT, fiscal metadata, discount limits, and backup controls.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
        @csrf
        @foreach($settings as $group => $items)
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-lg font-black capitalize text-slate-950">{{ $group }}</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($items as $setting)
                        <label class="block">
                            <span class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $setting->label ?: str($setting->key)->headline() }}</span>
                            @if($setting->type === 'boolean')
                                <select name="settings[{{ $setting->key }}]" class="mt-1 h-11 w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-bold">
                                    <option value="1" @selected((string) $setting->value === '1')>Enabled</option>
                                    <option value="0" @selected((string) $setting->value === '0')>Disabled</option>
                                </select>
                            @else
                                <input
                                    name="settings[{{ $setting->key }}]"
                                    value="{{ $setting->value }}"
                                    class="mt-1 h-11 w-full rounded-xl border-slate-200 bg-slate-50 text-sm"
                                >
                            @endif
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <button class="h-12 rounded-xl bg-indigo-600 px-6 text-sm font-black text-white shadow-lg shadow-indigo-600/20">
            Save Settings
        </button>
    </form>
</div>

@endsection

