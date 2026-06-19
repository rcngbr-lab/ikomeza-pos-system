@php
    $user = auth()->user();

    $canSell = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'CASHIER', 'WAITER', 'SERVER');
    $canShift = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'STORE_KEEPER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF'
    );
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
        'BAR_CHIEF'
    );

    $canAuditLogs = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'STORE_KEEPER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF'
    );

    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'mark' => 'DB'],
    ];

    if ($canSell) {
        $links[] = ['label' => 'POS Terminal', 'route' => 'pos.index', 'mark' => 'POS'];
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'WAITER', 'SERVER')) {
        $links[] = ['label' => 'Tables', 'route' => 'tables.index', 'active' => 'tables.*', 'mark' => 'TB'];
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'KITCHEN_MANAGER', 'KITCHEN_CHIEF', 'BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER', 'WAITER', 'SERVER')) {
        $links[] = ['label' => 'Tickets', 'route' => 'tickets.index', 'active' => 'tickets.*', 'mark' => 'TK'];
    }

    if ($canViewSales) {
        $links[] = ['label' => 'Sales', 'route' => 'sales.index', 'mark' => 'SA'];
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'CASHIER', 'WAITER', 'SERVER')) {
        $links[] = ['label' => 'Customers', 'route' => 'customers.index', 'active' => 'customers.*', 'mark' => 'CU'];
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
        'BAR_CHIEF'
    );

    $canCatalog = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');

    if ($canOperate) {
        $links = array_merge($links, [
            ['label' => 'Store Control', 'route' => 'store.dashboard', 'active' => 'store.*', 'mark' => 'ST'],
            ['label' => 'Stock Counts', 'route' => 'store.stock-counts', 'active' => 'store.stock-counts*', 'mark' => 'SC'],
            ['label' => 'Inventory', 'route' => 'inventory.index', 'mark' => 'IN'],
            ['label' => 'Reports', 'route' => 'reports.index', 'mark' => 'RP'],
        ]);
    }

    if ($canCatalog) {
        $links = array_merge($links, [
            ['label' => 'Products', 'route' => 'products.index', 'active' => 'products.*', 'mark' => 'PR'],
            ['label' => 'Categories', 'route' => 'categories.index', 'active' => 'categories.*', 'mark' => 'CT'],
        ]);
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')) {
        $links = array_merge($links, [
            ['label' => 'Credit Control', 'route' => 'receivables.index', 'active' => 'receivables.*', 'mark' => 'AR'],
            ['label' => 'Refunds', 'route' => 'refunds.index', 'mark' => 'RF'],
            ['label' => 'Users', 'route' => 'users.index', 'mark' => 'US'],
        ]);
    }

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
        $links = array_merge($links, [
            ['label' => 'Roles', 'route' => 'roles.index', 'mark' => 'RO'],
            ['label' => 'Permissions', 'route' => 'permissions.index', 'mark' => 'PM'],
            ['label' => 'Settings', 'route' => 'settings.index', 'active' => 'settings.*', 'mark' => 'SE'],
        ]);
    }

    if ($canAuditLogs) {
        $links[] = ['label' => 'Audit Logs', 'route' => 'audit.logs', 'mark' => 'AU'];
    }
@endphp

<div class="flex h-full w-full flex-col bg-slate-950 text-slate-200">
    <div class="border-b border-slate-800 px-3 py-3" :class="sidebarCollapsed ? 'text-center' : 'px-4 text-left'">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500" x-show="!sidebarCollapsed">
            Enterprise POS
        </p>
        <h1 class="text-xl font-black tracking-tight text-white" :class="sidebarCollapsed ? 'mt-0 text-base' : 'mt-1'">
            <span x-show="!sidebarCollapsed">FRONTIER</span>
            <span x-show="sidebarCollapsed">FR</span>
        </h1>
    </div>

    <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
        <p class="px-2 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500" x-show="!sidebarCollapsed">
            Operations
        </p>

        @foreach($links as $link)
            @php
                $active = request()->routeIs($link['active'] ?? $link['route']);
            @endphp

            <a
                href="{{ route($link['route']) }}"
                title="{{ $link['label'] }}"
                class="flex items-center gap-2.5 rounded-lg px-2 py-2 text-xs font-black transition {{ $active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                :class="sidebarCollapsed ? 'justify-center px-2' : ''"
            >
                <span class="flex h-7 min-w-7 items-center justify-center rounded-md {{ $active ? 'bg-white/15' : 'bg-slate-900 text-slate-400' }} text-[10px] font-black">
                    {{ $link['mark'] }}
                </span>
                <span class="truncate" x-show="!sidebarCollapsed">
                    {{ $link['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-800 p-3">
        <div class="flex items-center gap-2.5" :class="sidebarCollapsed ? 'justify-center' : ''">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-black text-white">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0" x-show="!sidebarCollapsed">
                <p class="truncate text-xs font-black text-white">
                    {{ auth()->user()->name }}
                </p>
                <p class="truncate text-[11px] text-slate-400">
                    {{ auth()->user()->roleLabel() }}
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf

            <button
                type="submit"
                class="flex h-9 w-full items-center justify-center gap-2 rounded-lg border border-slate-800 bg-slate-900 px-2 text-xs font-black text-slate-200 transition hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-100"
                :class="sidebarCollapsed ? 'px-2' : ''"
                title="Logout"
            >
                <span class="flex h-6 min-w-6 items-center justify-center rounded-md bg-slate-950 text-[9px] font-black text-slate-400">
                    OUT
                </span>
                <span x-show="!sidebarCollapsed">Logout</span>
            </button>
        </form>
    </div>
</div>
