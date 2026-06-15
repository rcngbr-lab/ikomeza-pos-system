@php
    $isDrawer = ($cartPanelMode ?? 'desktop') === 'drawer';
@endphp

<div class="pos-cart-panel-shell {{ $isDrawer ? 'max-h-[92vh] overflow-y-auto p-2.5' : 'sticky top-16 max-h-[calc(100vh-5rem)] overflow-y-auto' }}">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-black text-slate-950">Current Sale</h2>
                    <p class="mt-0.5 text-xs font-semibold text-slate-500" x-text="cartItems.length + ' line items / ' + cartCount + ' units'">
                        {{ count($cart) }} line items / {{ $cartCount }} units
                    </p>
                </div>

                <div class="flex items-start gap-3">
                    <div class="text-right">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total</p>
                        <p class="text-lg font-black text-indigo-600" x-text="money(total)"></p>
                    </div>

                    @if($isDrawer)
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-sm font-black text-slate-700"
                            aria-label="Close cart"
                            @click="cartOpen = false"
                        >
                            X
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="touch-scroll max-h-[32vh] space-y-2 overflow-y-auto p-2.5 {{ $isDrawer ? 'sm:max-h-[40vh]' : '2xl:max-h-[38vh]' }}">
            <template x-if="cartItems.length === 0">
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm font-semibold text-slate-500">
                    Add products to begin checkout.
                </div>
            </template>

            <template x-for="item in cartItems" :key="item.id">
                <div class="rounded-lg bg-slate-50 p-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-950" x-text="item.name"></p>
                            <p class="mt-1 text-xs font-semibold text-slate-500" x-text="money(item.price)"></p>
                            <p
                                class="mt-1 text-[10px] font-black uppercase tracking-wide"
                                :class="item.department_code === 'KITCHEN' ? 'text-amber-700' : 'text-indigo-700'"
                                x-text="item.department || 'Unassigned'"
                            >
                            </p>
                        </div>
                        <p class="shrink-0 text-sm font-black text-slate-950" x-text="new Intl.NumberFormat().format(cartLineTotal(item))"></p>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-lg font-black text-slate-700 shadow-sm"
                                aria-label="Decrease quantity"
                                @click="cartPost(routes.decrease, item.id)"
                            >
                                -
                            </button>

                            <span class="min-w-8 text-center text-sm font-black" x-text="item.quantity"></span>

                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-lg font-black text-white shadow-sm"
                                aria-label="Increase quantity"
                                @click="cartPost(routes.increase, item.id)"
                            >
                                +
                            </button>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-black text-rose-600"
                            @click="cartPost(routes.remove, item.id)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <form method="POST" action="{{ route('pos.checkout') }}" class="space-y-2.5 border-t border-slate-100 p-2.5">
            @csrf

            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                <select name="table_id" class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Walk-in / no table</option>
                    @foreach($tables as $table)
                        <option value="{{ $table->id }}" @selected((int) old('table_id') === (int) $table->id)>
                            {{ $table->name }}{{ $table->section ? ' - ' . $table->section : '' }} ({{ str($table->status)->headline() }})
                        </option>
                    @endforeach
                </select>

                <select name="customer_id" class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No customer account</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((int) old('customer_id') === (int) $customer->id)>
                            {{ $customer->name }}{{ $customer->phone ? ' - ' . $customer->phone : '' }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="text"
                    name="customer_name"
                    value="{{ old('customer_name') }}"
                    placeholder="Customer name optional"
                    class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2 xl:col-span-1"
                >
            </div>

            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="discount"
                    x-model.number="discount"
                    placeholder="Discount amount"
                    class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                >

                <input
                    type="text"
                    name="discount_reason"
                    value="{{ old('discount_reason') }}"
                    placeholder="Discount reason / approval note"
                    class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Payments</p>
                    <div class="flex gap-2">
                        <button type="button" class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-black text-slate-700 shadow-sm" @click="fillFirstPayment">
                            Fill
                        </button>
                        <button type="button" class="rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-black text-indigo-700 shadow-sm" @click="addPayment">
                            + Add
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <template x-for="(payment, index) in payments" :key="index">
                        <div class="grid gap-2 rounded-lg bg-white p-2 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
                            <select
                                x-model="payment.method"
                                x-bind:name="'payments[' + index + '][method]'"
                                class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach($paymentMethods as $method => $label)
                                    <option value="{{ $method }}">{{ $label }}</option>
                                @endforeach
                            </select>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                x-model.number="payment.amount"
                                x-bind:name="'payments[' + index + '][amount]'"
                                placeholder="Amount"
                                class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs font-bold focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <div class="grid grid-cols-[1fr_auto] gap-2 sm:contents">
                                <input
                                    type="text"
                                    x-model="payment.reference"
                                    x-bind:name="'payments[' + index + '][reference]'"
                                    placeholder="Ref"
                                    class="h-10 rounded-lg border-slate-200 bg-slate-50 text-xs focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2"
                                >
                                <button
                                    type="button"
                                    class="h-10 rounded-lg px-2 text-xs font-black text-rose-600"
                                    aria-label="Remove payment"
                                    @click="removePayment(index)"
                                >
                                    X
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="rounded-lg bg-slate-950 p-3 text-white">
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-300">
                        <span>Subtotal</span>
                        <span x-text="money(total)"></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-300">
                        <span>Discount</span>
                        <span x-text="money(discount)"></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-300">
                        <span>VAT {{ number_format($taxPreview['vat_rate'], 1) }}%</span>
                        <span x-text="money(taxAmount)"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/10 pt-3">
                        <span class="text-sm text-slate-300">Grand total</span>
                        <span class="text-xl font-black" x-text="money(grandTotal)"></span>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="h-11 w-full rounded-lg bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50"
                :disabled="cartItems.length === 0"
            >
                Complete Sale
            </button>
        </form>

        @if($isDrawer)
            <div class="border-t border-slate-100 p-3 pt-0">
                <button
                    type="button"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs font-black text-rose-600"
                    @click="cartPost(routes.clear)"
                >
                    Clear Cart
                </button>
            </div>
        @endif
    </div>
</div>
