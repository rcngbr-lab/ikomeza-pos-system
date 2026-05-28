@php
    $user = auth()->user();

    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'mark' => 'DB'],
        ['label' => 'POS Terminal', 'route' => 'pos.index', 'mark' => 'POS'],
        ['label' => 'Sales', 'route' => 'sales.index', 'mark' => 'SA'],
        ['label' => 'Shifts', 'route' => 'shifts.current', 'mark' => 'SH'],
    ];

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')) {
        $links = array_merge($links, [
            ['label' => 'Inventory', 'route' => 'inventory.index', 'mark' => 'IN'],
            ['label' => 'Products', 'route' => 'products.index', 'mark' => 'PR'],
            ['label' => 'Reports', 'route' => 'reports.index', 'mark' => 'RP'],
            ['label' => 'Refunds', 'route' => 'refunds.index', 'mark' => 'RF'],
            ['label' => 'Users', 'route' => 'users.index', 'mark' => 'US'],
        ]);
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
        $links = array_merge($links, [
            ['label' => 'Roles', 'route' => 'roles.index', 'mark' => 'RO'],
            ['label' => 'Permissions', 'route' => 'permissions.index', 'mark' => 'PM'],
            ['label' => 'Audit Logs', 'route' => 'audit.logs', 'mark' => 'AU'],
        ]);
    }
@endphp

<div class="flex h-full w-full flex-col bg-slate-950 text-slate-200">
    <div class="border-b border-slate-800 px-6 py-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">
            Enterprise POS
        </p>
        <h1 class="mt-2 text-2xl font-black tracking-tight text-white">
            IKOMEZA
        </h1>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Operations
        </p>

        @foreach($links as $link)
            @php
                $active = request()->routeIs($link['route']);
            @endphp

            <a
                href="{{ route($link['route']) }}"
                class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition {{ $active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
            >
                <span class="flex h-8 min-w-8 items-center justify-center rounded-lg {{ $active ? 'bg-white/15' : 'bg-slate-900 text-slate-400' }} text-[11px] font-black">
                    {{ $link['mark'] }}
                </span>
                <span class="truncate">
                    {{ $link['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-800 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 font-black text-white">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">
                    {{ auth()->user()->name }}
                </p>
                <p class="truncate text-xs text-slate-400">
                    {{ auth()->user()->roleLabel() }}
                </p>
            </div>
        </div>
    </div>
</div>
