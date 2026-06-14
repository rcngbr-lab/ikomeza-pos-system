@php
    $openShift = \App\Models\Shift::where('user_id', auth()->id())
        ->where(function ($query) {
            $query->where('is_open', true)->orWhere('status', 'OPEN');
        })
        ->latest()
        ->first();
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-14 items-center justify-between gap-2 px-2.5 lg:px-4">
        <div class="flex min-w-0 items-center gap-2.5">
            <button
                type="button"
                class="hidden h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700 lg:flex"
                title="Toggle sidebar"
                aria-label="Toggle sidebar"
                @click="toggleSidebar"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 6h16"/>
                    <path d="M4 12h10"/>
                    <path d="M4 18h16"/>
                </svg>
            </button>

            <div class="lg:hidden">
                <h1 class="text-base font-black leading-none tracking-tight text-indigo-600">
                    IKOMEZA
                </h1>
            </div>

            <div class="hidden md:block">
                <h2 class="text-base font-black leading-none text-slate-900 lg:text-lg">
                    {{ str(request()->route()?->getName() ?? 'dashboard')->replace('.', ' ')->title() }}
                </h2>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    Business operations control center
                </p>
            </div>
        </div>

        <div class="hidden max-w-xl flex-1 lg:flex">
            <input
                type="text"
                placeholder="Search receipts, products, reports..."
                class="h-9 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div class="flex shrink-0 items-center gap-1.5 lg:gap-2">
            <a
                href="{{ route($openShift ? 'shifts.current' : 'shifts.open.form') }}"
                class="hidden h-9 items-center gap-2 rounded-lg border px-3 text-[11px] font-black xl:flex {{ $openShift ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}"
            >
                <span class="h-2 w-2 rounded-full {{ $openShift ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $openShift ? 'Shift Active' : 'Open Shift' }}
            </a>

            @if(auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'))
                <a
                    href="{{ route('inventory.index') }}"
                    class="relative flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[11px] font-black text-slate-600 transition hover:bg-slate-100"
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
                    class="flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-black text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                    title="Logout"
                >
                    <span class="sm:hidden">OUT</span>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>

            <div class="flex items-center gap-2 pl-1">
                <div class="hidden text-right sm:block">
                    <p class="text-xs font-black leading-none text-slate-900">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-slate-500">
                        {{ auth()->user()->roleLabel() }}
                    </p>
                </div>

                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-black text-white shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </div>
</header>
