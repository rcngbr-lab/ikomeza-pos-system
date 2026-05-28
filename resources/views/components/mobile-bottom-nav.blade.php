@php
    $user = auth()->user();

    $items = [
        ['label' => 'Home', 'route' => 'dashboard', 'mark' => 'DB'],
        ['label' => 'POS', 'route' => 'pos.index', 'mark' => 'POS'],
        ['label' => 'Sales', 'route' => 'sales.index', 'mark' => 'SA'],
        ['label' => 'Shift', 'route' => 'shifts.current', 'mark' => 'SH'],
    ];

    if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')) {
        $items[] = ['label' => 'Stock', 'route' => 'inventory.index', 'mark' => 'IN'];
        $items[] = ['label' => 'Reports', 'route' => 'reports.index', 'mark' => 'RP'];
    }
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200 bg-white/95 shadow-[0_-2px_10px_rgba(15,23,42,0.08)] backdrop-blur">
    <div class="grid h-16 md:h-20" style="grid-template-columns: repeat({{ count($items) }}, minmax(0, 1fr));">
        @foreach($items as $item)
            @php $active = request()->routeIs($item['route']); @endphp

            <a
                href="{{ route($item['route']) }}"
                class="flex flex-col items-center justify-center gap-1 text-[10px] font-semibold transition active:scale-95 md:text-xs {{ $active ? 'text-indigo-600' : 'text-slate-500' }}"
            >
                <span class="flex h-7 min-w-7 items-center justify-center rounded-lg {{ $active ? 'bg-indigo-50' : 'bg-slate-100' }} text-[10px] font-black">
                    {{ $item['mark'] }}
                </span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
