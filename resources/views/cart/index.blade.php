@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Shopping Cart</h2>

        <a href="{{ route('pos.index') }}"
           class="btn btn-dark">
            Back To POS
        </a>

    </div>

    @php
        $cart = session('cart', []);
        $total = 0;
    @endphp

    @if(count($cart) > 0)

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Product</th>
                            <th width="120">Price</th>
                            <th width="140">Qty</th>
                            <th width="120">Total</th>
                            <th width="100"></th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($cart as $item)

                            @php
                                $lineTotal =
                                    $item['price']
                                    * $item['quantity'];

                                $total += $lineTotal;
                            @endphp

                            <tr>

                                <td>
                                    {{ $item['name'] }}
                                </td>

                                <td>
                                    {{ number_format($item['price']) }}
                                </td>

                                <td>

                                    <form
                                        action="{{ route('cart.update') }}"
                                        method="POST"
                                        class="d-flex gap-2"
                                    >
                                        @csrf

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="{{ $item['id'] }}"
                                        >

                                        <input
                                            type="number"
                                            name="quantity"
                                            value="{{ $item['quantity'] }}"
                                            min="1"
                                            class="form-control"
                                        >

                                        <button
                                            class="btn btn-primary btn-sm"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>

                                <td>

                                    {{ number_format($lineTotal) }}

                                </td>

                                <td>

                                    <form
                                        action="{{ route('cart.remove') }}"
                                        method="POST"
                                    >
                                        @csrf

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="{{ $item['id'] }}"
                                        >

                                        <button
                                            class="btn btn-danger btn-sm"
                                        >
                                            Remove
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        <div class="d-flex gap-2">

    <!-- CLEAR CART -->

    <form
        action="{{ route('cart.clear') }}"
        method="POST"
    >
        @csrf

        <button
            type="submit"
            class="btn btn-outline-danger"
        >
            Clear Cart
        </button>

    </form>


    <!-- CHECKOUT -->

 <div class="card border-0 shadow-sm mt-4">

    <div class="card-body">

        <div class="d-flex justify-content-between mb-3">

            <h4>Total</h4>

            <h4>{{ number_format($total) }}</h4>

        </div>

        <div class="d-flex gap-2">

            <form
                action="{{ route('cart.clear') }}"
                method="POST"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-outline-danger"
                >
                    Clear Cart
                </button>

            </form>

            <form
                action="{{ route('cart.checkout') }}"
                method="POST"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    Checkout
                </button>

            </form>

        </div>

    </div>

</div>

                <div class="d-flex gap-2">

                    <form
                        action="{{ route('cart.clear') }}"
                        method="POST"
                    >
                        @csrf

                        <button class="btn btn-outline-danger">
                            Clear Cart
                        </button>

                    </form>

                    <form
                        action="{{ route('cart.checkout') }}"
                        method="POST"
                    >
                        @csrf

                        <button class="btn btn-success">
                            Checkout
                        </button>

                    </form>

                </div>

            </div>

        </div>

    @else

        <div class="alert alert-info">

            Cart is empty.

        </div>

    @endif

</div>

@endsection