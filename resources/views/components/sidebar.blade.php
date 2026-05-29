@php
    $user = auth()->user();

    $canSell = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'CASHIER', 'WAITER', 'SERVER');
    $canShift = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'CASHIER', 'WAITER', 'SERVER');
    $canViewSales = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF',
        'BARTENDER',
        'CASHIER',
        'WAITER',
        'SERVER'
    );
    $canRequest = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'STORE_KEEPER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF',
        'BARTENDER',
        'CASHIER',
        'WAITER',
        'SERVER'
    );

    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'mark' => 'DB'],
    ];

    if ($canSell) {
        $links[] = ['label' => 'POS Terminal', 'route' => 'pos.index', 'mark' => 'POS'];
    }

    if ($canViewSales) {
        $links[] = ['label' => 'Sales', 'route' => 'sales.index', 'mark' => 'SA'];
    }

    if ($canShift) {
        $links[] = ['label' => 'Shifts', 'route' => 'shifts.current', 'mark' => 'SH'];
    }

    if ($canRequest) {
        $links[] = ['label' => 'Requisitions', 'route' => 'requisitions.index', 'active' => 'requisitions.*', 'mark' => 'RQ'];
    }

    $canOperate = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'STORE_KEEPER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF',
        'BARTENDER'
    );

    if ($canOperate) {
        $links = array_merge($links, [
            ['label' => 'Inventory', 'route' => 'inventory.index', 'mark' => 'IN'],
            ['label' => 'Products', 'route' => 'products.index', 'active' => 'products.*', 'mark' => 'PR'],
            ['label' => 'Categories', 'route' => 'categories.index', 'active' => 'categories.*', 'mark' => 'CT'],
            ['label' => 'Reports', 'route' => 'reports.index', 'mark' => 'RP'],
        ]);
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')) {
        $links = array_merge($links, [
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
                $active = request()->routeIs($link['active'] ?? $link['route']);
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

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf

            <button
                type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3 py-3 text-sm font-black text-slate-200 transition hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-100"
            >
                <span class="flex h-7 min-w-7 items-center justify-center rounded-lg bg-slate-950 text-[10px] font-black text-slate-400">
                    OUT
                </span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>
