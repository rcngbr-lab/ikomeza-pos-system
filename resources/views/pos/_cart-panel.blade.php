@php
    $isDrawer = ($cartPanelMode ?? 'desktop') === 'drawer';
    $panelShellClasses = $isDrawer
        ? 'max-h-[92vh] p-2.5'
        : 'sticky top-16 h-[calc(100vh-5rem)] max-h-[calc(100vh-5rem)]';
@endphp

<div class="pos-cart-panel-shell flex min-h-0 {{ $panelShellClasses }}">
    <div class="flex min-h-0 w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm {{ $isDrawer ? 'max-h-[calc(92vh-1.25rem)]' : 'h-full' }}">
        <div class="shrink-0 border-b border-slate-100 px-3 py-2.5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-black leading-tight text-slate-950">Current Sale</h2>
                    <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-500" x-text="cartItems.length + ' lines / ' + cartCount + ' units'">
                        {{ count($cart) }} lines / {{ $cartCount }} units
                    </p>
                </div>

                <div class="flex shrink-0 items-start gap-2">
                    <div class="text-right">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Total</p>
                        <p class="text-lg font-black leading-tight text-indigo-600" x-text="money(total)"></p>
                    </div>

                    @if($isDrawer)
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-700"
                            aria-label="Close cart"
                            @click="cartOpen = false"
                        >
                            X
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="touch-scroll shrink-0 space-y-1.5 overflow-y-auto p-2 {{ $isDrawer ? 'max-h-[30vh] sm:max-h-[34vh]' : 'max-h-[24vh] 2xl:max-h-[28vh]' }}">
            <template x-if="cartItems.length === 0">
                <div class="rounded-lg border border-dashed border-slate-300 p-5 text-center text-xs font-semibold text-slate-500">
                    Add products to begin checkout.
                </div>
            </template>

            <template x-for="item in cartItems" :key="item.id">
                <div class="rounded-lg bg-slate-50 p-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-950" x-text="item.name"></p>
                            <p class="mt-0.5 text-[11px] font-semibold text-slate-500" x-text="money(item.price)"></p>
                            <p
                                class="mt-0.5 text-[9px] font-black uppercase tracking-wide"
                                :class="item.department_code === 'KITCHEN' ? 'text-amber-700' : 'text-indigo-700'"
                                x-text="item.department || 'Unassigned'"
                            >
                            </p>
                        </div>
                        <p class="shrink-0 text-sm font-black text-slate-950" x-text="new Intl.NumberFormat().format(cartLineTotal(item))"></p>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-base font-black text-slate-700 shadow-sm"
                                aria-label="Decrease quantity"
                                @click="cartPost(routes.decrease, item.id)"
                            >
                                -
                            </button>

                            <span class="min-w-7 text-center text-sm font-black" x-text="item.quantity"></span>

                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-base font-black text-white shadow-sm"
                                aria-label="Increase quantity"
                                @click="cartPost(routes.increase, item.id)"
                            >
                                +
                            </button>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-[11px] font-black text-rose-600"
                            @click="cartPost(routes.remove, item.id)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <form method="POST" action="{{ route('pos.checkout') }}" class="flex min-h-0 flex-1 flex-col border-t border-slate-100">
            @csrf

            <div class="pos-cart-form-scroll touch-scroll min-h-0 flex-1 space-y-2 overflow-y-auto p-2">
                <div class="grid gap-1.5">
                    <select name="table_id" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-[11px] font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Walk-in / no table</option>
                        @foreach($tables as $table)
                            <option value="{{ $table->id }}" @selected((int) old('table_id') === (int) $table->id)>
                                {{ $table->name }}{{ $table->section ? ' - ' . $table->section : '' }} ({{ str($table->status)->headline() }})
                            </option>
                        @endforeach
                    </select>

                    <select name="customer_id" class="h-9 rounded-lg border-slate-200 bg-slate-50 text-[11px] font-semibold focus:border-indigo-500 focus:ring-indigo-500">
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
                        class="h-9 rounded-lg border-slate-200 bg-slate-50 text-[11px] focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div class="grid gap-1.5">
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="discount"
                        x-model.number="discount"
                        placeholder="Discount amount"
                        class="h-9 rounded-lg border-slate-200 bg-slate-50 text-[11px] focus:border-indigo-500 focus:ring-indigo-500"
                    >

                    <input
                        type="text"
                        name="discount_reason"
                        value="{{ old('discount_reason') }}"
                        placeholder="Discount reason / approval note"
                        class="h-9 rounded-lg border-slate-200 bg-slate-50 text-[11px] focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Payments</p>
                        <div class="flex gap-1.5">
                            <button type="button" class="h-8 rounded-lg bg-white px-2 text-[11px] font-black text-slate-700 shadow-sm" @click="fillFirstPayment">
                                Fill
                            </button>
                            <button type="button" class="h-8 rounded-lg bg-white px-2 text-[11px] font-black text-indigo-700 shadow-sm" @click="addPayment">
                                + Add
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <template x-for="(payment, index) in payments" :key="index">
                            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-1.5 rounded-lg bg-white p-1.5 shadow-sm">
                                <select
                                    x-model="payment.method"
                                    x-bind:name="'payments[' + index + '][method]'"
                                    class="h-8 rounded-lg border-slate-200 bg-slate-50 text-[11px] font-bold focus:border-indigo-500 focus:ring-indigo-500"
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
                                    class="h-8 rounded-lg border-slate-200 bg-slate-50 text-[11px] font-bold focus:border-indigo-500 focus:ring-indigo-500"
                                >

                                <button
                                    type="button"
                                    class="h-8 w-8 rounded-lg text-[11px] font-black text-rose-600"
                                    aria-label="Remove payment"
                                    @click="removePayment(index)"
                                >
                                    X
                                </button>

                                <input
                                    type="text"
                                    x-model="payment.reference"
                                    x-bind:name="'payments[' + index + '][reference]'"
                                    placeholder="Ref / transaction ID"
                                    class="col-span-3 h-8 rounded-lg border-slate-200 bg-slate-50 text-[11px] focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="pos-checkout-footer shrink-0 space-y-2 border-t border-slate-100 bg-white p-2">
                <div class="rounded-lg bg-slate-950 p-2.5 text-white">
                    <div class="space-y-1.5 text-[11px]">
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
                        <div class="flex items-center justify-between border-t border-white/10 pt-2">
                            <span class="text-xs text-slate-300">Grand total</span>
                            <span class="text-xl font-black leading-tight" x-text="money(grandTotal)"></span>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="h-10 w-full rounded-lg bg-indigo-600 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="cartItems.length === 0"
                >
                    Complete Sale
                </button>

                @if($isDrawer)
                    <button
                        type="button"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-rose-600"
                        @click="cartPost(routes.clear)"
                    >
                        Clear Cart
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
