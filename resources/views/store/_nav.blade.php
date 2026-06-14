@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'store.dashboard'],
        ['label' => 'Suppliers', 'route' => 'store.suppliers'],
        ['label' => 'Purchases', 'route' => 'store.purchases'],
        ['label' => 'Issues', 'route' => 'store.issues'],
        ['label' => 'Damages', 'route' => 'store.damages'],
        ['label' => 'Returns', 'route' => 'store.returns'],
        ['label' => 'Movements', 'route' => 'store.movements'],
    ];
@endphp

<div class="overflow-x-auto">
    <div class="inline-flex min-w-full gap-1 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm">
        @foreach($items as $item)
            @php $active = request()->routeIs($item['route']); @endphp

            <a
                href="{{ route($item['route']) }}"
                class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-black transition {{ $active ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
