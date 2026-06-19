@php
    $user = auth()->user();

    $canSell = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'CASHIER',
        'WAITER',
        'SERVER'
    );

    $canOperate = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF',
        'STORE_KEEPER'
    );

    $canCatalog = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    $canManageStaff = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER');
    $canAdmin = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR');
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
    $canShift = $user->hasOperationalRole(
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
    $canTables = $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'WAITER', 'SERVER');
    $canTickets = $user->hasOperationalRole(
        'ADMIN',
        'ADMINISTRATOR',
        'MANAGER',
        'KITCHEN_MANAGER',
        'KITCHEN_CHIEF',
        'BAR_MANAGER',
        'BAR_CHIEF',
        'BARTENDER',
        'WAITER',
        'SERVER'
    );

    $allItems = collect([
        [
            'label' => 'Home',
            'route' => 'dashboard',
            'active' => 'dashboard',
            'icon' => 'home',
            'group' => 'Core',
            'show' => true,
        ],
        [
            'label' => 'POS',
            'route' => 'pos.index',
            'active' => 'pos.*',
            'icon' => 'terminal',
            'group' => 'Core',
            'show' => $canSell,
        ],
        [
            'label' => 'Tables',
            'route' => 'tables.index',
            'active' => 'tables.*',
            'icon' => 'table',
            'group' => 'Restaurant',
            'show' => $canTables,
        ],
        [
            'label' => 'Tickets',
            'route' => 'tickets.index',
            'active' => 'tickets.*',
            'icon' => 'ticket',
            'group' => 'Restaurant',
            'show' => $canTickets,
        ],
        [
            'label' => 'Sales',
            'route' => 'sales.index',
            'active' => 'sales.*',
            'icon' => 'receipt',
            'group' => 'Core',
            'show' => $canViewSales,
        ],
        [
            'label' => 'Customers',
            'route' => 'customers.index',
            'active' => 'customers.*',
            'icon' => 'users',
            'group' => 'Core',
            'show' => $canSell,
        ],
        [
            'label' => 'Shift',
            'route' => 'shifts.current',
            'active' => 'shifts.*',
            'icon' => 'clock',
            'group' => 'Core',
            'show' => $canShift,
        ],
        [
            'label' => 'Requests',
            'route' => 'requisitions.index',
            'active' => 'requisitions.*',
            'icon' => 'clipboard',
            'group' => 'Operations',
            'show' => $canRequest,
        ],
        [
            'label' => 'Store',
            'route' => 'store.dashboard',
            'active' => 'store.*',
            'icon' => 'store',
            'group' => 'Stock',
            'show' => $canOperate,
        ],
        [
            'label' => 'Stock Count',
            'route' => 'store.stock-counts',
            'active' => 'store.stock-counts*',
            'icon' => 'count',
            'group' => 'Stock',
            'show' => $canOperate,
        ],
        [
            'label' => 'Inventory',
            'route' => 'inventory.index',
            'active' => 'inventory.*',
            'icon' => 'warehouse',
            'group' => 'Stock',
            'show' => $canOperate,
        ],
        [
            'label' => 'Movements',
            'route' => 'stock.movements',
            'active' => 'stock.*',
            'icon' => 'arrows',
            'group' => 'Stock',
            'show' => $canOperate,
        ],
        [
            'label' => 'Products',
            'route' => 'products.index',
            'active' => 'products.*',
            'icon' => 'package',
            'group' => 'Catalog',
            'show' => $canCatalog,
        ],
        [
            'label' => 'Add Product',
            'route' => 'products.create',
            'active' => 'products.create',
            'icon' => 'plus',
            'group' => 'Catalog',
            'show' => $canCatalog,
        ],
        [
            'label' => 'Categories',
            'route' => 'categories.index',
            'active' => 'categories.*',
            'icon' => 'tags',
            'group' => 'Catalog',
            'show' => $canCatalog,
        ],
        [
            'label' => 'Reports',
            'route' => 'reports.index',
            'active' => 'reports.index',
            'icon' => 'chart',
            'group' => 'Control',
            'show' => $canOperate,
        ],
        [
            'label' => 'Credit',
            'route' => 'receivables.index',
            'active' => 'receivables.*',
            'icon' => 'credit',
            'group' => 'Control',
            'show' => $canManageStaff,
        ],
        [
            'label' => 'My Report',
            'route' => 'reports.my',
            'active' => 'reports.my',
            'icon' => 'file',
            'group' => 'Control',
            'show' => $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER'),
        ],
        [
            'label' => 'Refunds',
            'route' => 'refunds.index',
            'active' => 'refunds.*',
            'icon' => 'refund',
            'group' => 'Control',
            'show' => $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER'),
        ],
        [
            'label' => 'Users',
            'route' => 'users.index',
            'active' => 'users.*',
            'icon' => 'users',
            'group' => 'Admin',
            'show' => $canManageStaff,
        ],
        [
            'label' => 'Roles',
            'route' => 'roles.index',
            'active' => 'roles.*',
            'icon' => 'shield',
            'group' => 'Admin',
            'show' => $canAdmin,
        ],
        [
            'label' => 'Permissions',
            'route' => 'permissions.index',
            'active' => 'permissions.*',
            'icon' => 'key',
            'group' => 'Admin',
            'show' => $canAdmin,
        ],
        [
            'label' => 'Audit Logs',
            'route' => 'audit.logs',
            'active' => 'audit.*',
            'icon' => 'activity',
            'group' => 'Admin',
            'show' => $canAuditLogs,
        ],
        [
            'label' => 'Settings',
            'route' => 'settings.index',
            'active' => 'settings.*',
            'icon' => 'settings',
            'group' => 'Admin',
            'show' => $canAdmin,
        ],
    ])
        ->filter(fn ($item) => $item['show'] && Route::has($item['route']))
        ->values();

    $primaryItems = $allItems
        ->take(4)
        ->values();

    $moreItems = $allItems
        ->reject(fn ($item) => $primaryItems->pluck('route')->contains($item['route']))
        ->groupBy('group');

    $icon = function (string $name, string $class = 'h-5 w-5') {
        $attrs = 'class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

        $paths = match ($name) {
            'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
            'terminal' => '<rect x="3" y="4" width="18" height="16" rx="3"/><path d="m8 9 3 3-3 3"/><path d="M13 15h3"/>',
            'receipt' => '<path d="M6 3h12v18l-2-1-2 1-2-1-2 1-2-1-2 1V3Z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h4"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'clipboard' => '<path d="M9 4h6l1 2h3v15H5V6h3l1-2Z"/><path d="M9 12h6"/><path d="M9 16h4"/>',
            'store' => '<path d="M4 10h16l-1-5H5l-1 5Z"/><path d="M5 10v10h14V10"/><path d="M8 20v-6h8v6"/><path d="M7 5V3h10v2"/>',
            'warehouse' => '<path d="M3 10 12 4l9 6"/><path d="M5 10v10h14V10"/><path d="M8 20v-6h8v6"/><path d="M8 14h8"/>',
            'arrows' => '<path d="M7 7h11l-3-3"/><path d="M17 17H6l3 3"/><path d="M18 7l-3 3"/><path d="M6 17l3-3"/>',
            'package' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
            'plus' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M12 8v8"/><path d="M8 12h8"/>',
            'tags' => '<path d="M20 13 11 4H4v7l9 9 7-7Z"/><path d="M7.5 7.5h.01"/><path d="m14 6 6 6"/>',
            'chart' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-3"/>',
            'file' => '<path d="M14 3H6v18h12V7l-4-4Z"/><path d="M14 3v4h4"/><path d="M9 13h6"/><path d="M9 17h4"/>',
            'refund' => '<path d="M9 14 5 10l4-4"/><path d="M5 10h10a4 4 0 0 1 0 8h-2"/>',
            'credit' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18"/><path d="M7 15h4"/><path d="M15 15h2"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
            'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12 21 2"/><path d="m17 6 3 3"/><path d="m14 9 3 3"/>',
            'activity' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 14 3-4 3 3 4-7"/>',
            'table' => '<rect x="4" y="6" width="16" height="8" rx="2"/><path d="M7 14v5"/><path d="M17 14v5"/><path d="M8 10h8"/>',
            'ticket' => '<path d="M4 7a2 2 0 0 1 2-2h12v4a2 2 0 1 0 0 4v4H6a2 2 0 0 1-2-2v-4a2 2 0 1 0 0-4Z"/><path d="M9 9h4"/><path d="M9 13h6"/>',
            'count' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8"/><path d="M8 13h8"/><path d="M8 17h5"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2 3-.2-.1a1.7 1.7 0 0 0-2 .1 1.7 1.7 0 0 0-.8 1.7V22h-3.6v-.3a1.7 1.7 0 0 0-.8-1.7 1.7 1.7 0 0 0-2-.1l-.2.1-2-3 .1-.1A1.7 1.7 0 0 0 4.6 15 1.7 1.7 0 0 0 3 14H2v-4h1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2-3 .2.1a1.7 1.7 0 0 0 2-.1 1.7 1.7 0 0 0 .8-1.7V2h3.6v.3a1.7 1.7 0 0 0 .8 1.7 1.7 1.7 0 0 0 2 .1l.2-.1 2 3-.1.1A1.7 1.7 0 0 0 19.4 9 1.7 1.7 0 0 0 21 10h1v4h-1a1.7 1.7 0 0 0-1.6 1Z"/>',
            default => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/>',
        };

        return '<svg ' . $attrs . '>' . $paths . '</svg>';
    };

    $isActive = fn ($item) => request()->routeIs($item['active'] ?? $item['route']);
@endphp

<nav
    class="mobile-bottom-nav fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-1.5 pt-0.5 shadow-[0_-10px_28px_rgba(15,23,42,0.12)] backdrop-blur-xl"
    style="padding-bottom: max(0.25rem, env(safe-area-inset-bottom));"
>
    <div class="mx-auto max-w-3xl">
        <div class="mobile-nav-shell grid grid-cols-5 items-end gap-1 rounded-2xl border border-slate-200 bg-slate-950 p-0.5 shadow-lg shadow-slate-950/20">
            @foreach($primaryItems as $item)
                @php $active = $isActive($item); @endphp

                <a
                    href="{{ route($item['route']) }}"
                    class="mobile-nav-item flex min-h-[46px] flex-col items-center justify-center gap-0.5 rounded-xl px-1 text-[9px] font-black transition active:scale-95 {{ $active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-950/40' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                    aria-label="{{ $item['label'] }}"
                >
                    {!! $icon($item['icon'], 'h-4 w-4') !!}
                    <span class="max-w-full truncate leading-none">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <details class="group relative">
                <summary
                    class="mobile-nav-more flex min-h-[46px] cursor-pointer list-none flex-col items-center justify-center gap-0.5 rounded-xl px-1 text-[9px] font-black text-slate-300 transition active:scale-95 group-open:bg-white group-open:text-slate-950 [&::-webkit-details-marker]:hidden"
                    aria-label="More navigation"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="5" cy="12" r="1.5"/>
                        <circle cx="12" cy="12" r="1.5"/>
                        <circle cx="19" cy="12" r="1.5"/>
                    </svg>
                    <span class="leading-none">More</span>
                </summary>

                <div class="mobile-nav-panel fixed inset-x-3 bottom-[5.85rem] max-h-[70vh] overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/25">
                    <div class="border-b border-slate-100 bg-slate-950 px-4 py-3 text-white">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-indigo-200">FRONTIER</p>
                                <p class="text-base font-black">Mobile menu</p>
                            </div>
                            <span class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-black">
                                {{ $user->roleLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="mobile-nav-panel-body touch-scroll max-h-[calc(70vh-64px)] space-y-4 overflow-y-auto p-4">
                        @foreach($moreItems as $group => $items)
                            <section>
                                <h3 class="px-1 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                    {{ $group }}
                                </h3>

                                <div class="mobile-nav-panel-grid mt-2 grid grid-cols-2 gap-2 min-[420px]:grid-cols-3">
                                    @foreach($items as $item)
                                        @php $active = $isActive($item); @endphp

                                        <a
                                            href="{{ route($item['route']) }}"
                                            class="mobile-nav-panel-link flex min-h-[76px] flex-col justify-between rounded-2xl border p-3 transition active:scale-[0.98] {{ $active ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-white' }}"
                                        >
                                            <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 shadow-sm' }}">
                                                {!! $icon($item['icon'], 'h-5 w-5') !!}
                                            </span>
                                            <span class="mt-2 text-xs font-black leading-tight">{{ $item['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            </details>
        </div>
    </div>
</nav>
