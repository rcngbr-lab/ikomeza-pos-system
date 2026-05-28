@extends('layouts.app')

@section('content')

<h1>Bar Orders</h1>

<table width="100%" cellpadding="10">

    <thead>

        <tr>
            <th>ID</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

    </thead>

    <tbody>

        @foreach($orders as $order)

        <tr>

            <td>{{ $order->id }}</td>

            <td>
                {{ $order->product?->name }}
            </td>

            <td>
                {{ $order->quantity }}
            </td>

            <td>
                {{ $order->status }}
            </td>

            <td>

                @if($order->status != 'PREPARED')

                <form
                    method="POST"
                    action="/bar/prepare/{{ $order->id }}"
                >

                    @csrf

                    <button type="submit">

                        Prepare

                    </button>

                </form>

                @endif

            </td>

        </tr>

        @endforeach

    </tbody>

</table>

@endsection