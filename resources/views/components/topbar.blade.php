@php
    $openShift = \App\Models\Shift::where('user_id', auth()->id())
        ->where(function ($query) {
            $query->where('is_open', true)->orWhere('status', 'OPEN');
        })
        ->latest()
        ->first();
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 lg:px-6">
        <div class="flex min-w-0 items-center gap-4">
            <div class="lg:hidden">
                <h1 class="text-lg font-black leading-none tracking-tight text-indigo-600">
                    IKOMEZA
                </h1>
            </div>

            <div class="hidden md:block">
                <h2 class="text-lg font-bold leading-none text-slate-900 lg:text-xl">
                    {{ str(request()->route()?->getName() ?? 'dashboard')->replace('.', ' ')->title() }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Business operations control center
                </p>
            </div>
        </div>

        <div class="hidden max-w-xl flex-1 lg:flex">
            <input
                type="text"
                placeholder="Search receipts, products, reports..."
                class="w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div class="flex shrink-0 items-center gap-2 lg:gap-4">
            <a
                href="{{ route($openShift ? 'shifts.current' : 'shifts.open.form') }}"
                class="hidden items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold xl:flex {{ $openShift ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}"
            >
                <span class="h-2 w-2 rounded-full {{ $openShift ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $openShift ? 'Shift Active' : 'Open Shift' }}
            </a>

            @if(auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'))
                <a
                    href="{{ route('inventory.index') }}"
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-xs font-black text-slate-600 transition hover:bg-slate-100"
                    title="Inventory alerts"
                >
                    IN
                    <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-rose-500"></span>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf

                <button
                    type="submit"
                    class="flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                    title="Logout"
                >
                    <span class="sm:hidden">OUT</span>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>

            <div class="flex items-center gap-3 pl-1">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold leading-none text-slate-900">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ auth()->user()->roleLabel() }}
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </div>
</header>
