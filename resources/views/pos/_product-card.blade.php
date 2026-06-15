@php
    $loopIndex = $loopIndex ?? 0;
    $categoryName = $product->category->name ?? 'Uncategorized';
    $departmentName = $product->department->name ?? 'Unassigned';
    $departmentCode = $product->department?->code;
    $imageSource = $product->image_source;
    $stockQuantity = (float) $product->stock;
    $alertStock = (float) $product->alert_stock;
    $isTracked = (bool) $product->track_stock;
    $isOut = $isTracked && $stockQuantity <= 0;
    $isLow = $isTracked && !$isOut && $stockQuantity <= $alertStock;
    $stockTone = $isOut ? 'rose' : ($isLow ? 'amber' : 'emerald');
    $stockLabel = $isOut ? 'Out Of Stock' : 'Stock ' . number_format($stockQuantity);
    $searchText = strtolower(trim(($product->name ?? '') . ' ' . ($product->barcode ?? '') . ' ' . ($product->product_code ?? '') . ' ' . $categoryName . ' ' . $departmentName));
    $productPayload = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (float) $product->selling_price,
        'unit' => $product->unit ?: 'item',
        'stock' => $stockQuantity,
        'stock_label' => $stockLabel,
        'stock_tone' => $stockTone,
        'is_out' => $isOut,
        'track_stock' => $isTracked,
        'category' => $categoryName,
        'department' => $departmentName,
        'department_code' => $departmentCode,
        'image' => $imageSource,
        'barcode' => $product->barcode,
    ];
@endphp

<form
    method="POST"
    action="{{ route('pos.add') }}"
    class="product-card group relative flex h-[196px] min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg active:scale-[0.985] sm:h-[208px] md:h-[218px]"
    :class="{
        'ring-2 ring-indigo-400 shadow-indigo-100': recentProductId === {{ $product->id }},
        'opacity-55': {{ $isOut ? 'true' : 'false' }},
        'pointer-events-none': addingProductId === {{ $product->id }}
    }"
    data-pos-product
    data-index="{{ $loopIndex }}"
    data-category="{{ $categoryName }}"
    data-department="{{ $departmentName }}"
    data-search="{{ $searchText }}"
    data-barcode="{{ strtolower($product->barcode ?? '') }}"
    x-show="productVisible($el)"
    @click.prevent="tapProduct($event, @js($productPayload))"
    @pointerdown="startLongPress(@js($productPayload))"
    @pointerup="cancelLongPress"
    @pointerleave="cancelLongPress"
    @pointercancel="cancelLongPress"
>
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">

    <button
        type="submit"
        class="flex h-full w-full min-w-0 flex-col text-left disabled:cursor-not-allowed"
        aria-label="Add {{ $product->name }} to cart"
        {{ $isOut ? 'disabled' : '' }}
    >
        <div class="product-card-image flex h-[48%] min-h-[86px] items-center justify-center bg-white px-2 pt-2">
            @if($imageSource)
                <img
                    src="{{ $imageSource }}"
                    alt="{{ $product->name }}"
                    class="h-full w-full object-contain"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="flex h-full w-full flex-col items-center justify-center rounded-lg bg-slate-50 text-center">
                    <span class="text-2xl leading-none">📦</span>
                    <span class="mt-1 text-[10px] font-black uppercase tracking-wide text-slate-400">No Image</span>
                </div>
            @endif
        </div>

        <div class="product-card-body flex min-h-0 flex-1 flex-col px-2.5 pb-2.5 pt-2">
            <div class="min-w-0 pr-11">
                <p class="product-card-name line-clamp-2 text-[13px] font-black leading-4 text-slate-950 sm:text-sm">
                    {{ $product->name }}
                </p>
                <p class="product-card-price mt-1 text-lg font-black leading-none text-slate-950 sm:text-xl">
                    {{ number_format($product->selling_price) }}
                </p>
                <p class="product-card-unit mt-0.5 truncate text-[10px] font-black uppercase tracking-wide text-slate-500">
                    RWF / {{ $product->unit ?: 'item' }}
                </p>
            </div>

            <div class="product-card-meta mt-auto flex min-w-0 items-center justify-between gap-2 pr-11">
                <span class="inline-flex min-w-0 items-center gap-1.5 rounded-full bg-slate-50 px-2 py-1 text-[10px] font-black text-slate-600">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $stockTone === 'emerald' ? 'bg-emerald-500' : ($stockTone === 'amber' ? 'bg-amber-500' : 'bg-rose-500') }}"></span>
                    <span class="truncate">{{ $stockLabel }}</span>
                </span>

                <span class="hidden truncate rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500 min-[420px]:inline-flex">
                    {{ $categoryName }}
                </span>
            </div>
        </div>

        <span
            class="product-card-add absolute bottom-2 right-2 flex h-11 w-11 items-center justify-center rounded-xl text-xl font-black text-white shadow-lg transition duration-150 group-hover:scale-105 sm:h-10 sm:w-10 {{ $isOut ? 'bg-slate-300 shadow-none' : 'bg-indigo-600 shadow-indigo-600/25 group-hover:bg-indigo-700' }}"
            aria-hidden="true"
        >
            <span x-show="addingProductId !== {{ $product->id }}">+</span>
            <span x-cloak x-show="addingProductId === {{ $product->id }}" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
        </span>
    </button>
</form>
