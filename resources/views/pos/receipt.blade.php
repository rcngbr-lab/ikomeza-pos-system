<!DOCTYPE html>
<html>

<head>

    <title>
        Receipt
    </title>

    <style>

        body {

            font-family: monospace;
            background: #f5f5f5;
            padding: 20px;
        }

        .receipt {

            width: 320px;
            margin: auto;
            background: white;
            padding: 20px;
            color: black;
        }

        .center {

            text-align: center;
        }

        .line {

            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        table {

            width: 100%;
            font-size: 12px;
        }

        th {

            text-align: left;
        }

        td {

            padding: 4px 0;
        }

        .right {

            text-align: right;
        }

        .total {

            font-size: 20px;
            font-weight: bold;
            text-align: right;
        }

        .btn {

            display: inline-block;
            margin-top: 20px;
            background: black;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
        }

        .refund-banner {

            border: 2px solid #991b1b;
            color: #991b1b;
            text-align: center;
            padding: 8px;
            margin: 10px 0;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .refund-note {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .dept {
            display: block;
            font-size: 10px;
            font-weight: bold;
            opacity: .7;
        }

        @media print {

            .btn {

                display: none;
            }

            body {

                background: white;
                padding: 0;
            }

            .receipt {

                box-shadow: none;
            }
        }

    </style>

</head>

<body>

<div class="receipt">

    <!-- HEADER -->

    <div class="center">

        

        <p>

            Kigali - Rwanda

        </p>

        <p>

            Receipt:
            {{ $sale->receipt_no }}

        </p>

    </div>

    <div class="line"></div>

    <!-- INFO -->

    <p>

        Cashier:
        {{ $sale->user->name ?? 'N/A' }}

    </p>

    <p>

        Date:
        {{ $sale->created_at }}

    </p>

    <p>

        Payment:
        {{ $sale->payment_method }}

    </p>

    @if($sale->is_refunded || $sale->sale_status === 'REFUNDED')
        <div class="refund-banner">
            REFUNDED RECEIPT
        </div>

        <div class="refund-note">
            Stock restored on {{ optional($sale->refunded_at)->format('d/m/Y H:i') ?? 'refund date' }}
        </div>
    @endif

    <div class="line"></div>

    <!-- ITEMS -->

    <table>

        <thead>

            <tr>

                <th>
                    Item
                </th>

                <th class="right">
                    Qty
                </th>

                <th class="right">
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach($sale->items as $item)

                <tr>

                    <td>

                        {{ $item->product->name ?? 'Deleted Product' }}
                        <span class="dept">
                            {{ $item->department->name ?? $item->product?->department?->name ?? 'Department' }}
                        </span>

                    </td>

                    <td class="right">

                        {{ $item->quantity }}

                    </td>

                    <td class="right">

                     {{ number_format(
    $item->subtotal
) }}

                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <div class="line"></div>

    <!-- TOTAL -->

    <div
    style="
        margin-top:15px;
        text-align:right;
        font-size:34px;
        font-weight:bold;
    "
>

    {{ number_format($sale->grand_total) }} Frw

</div>

    <div class="line"></div>

    <!-- FOOTER -->

    <div class="center">

        <p>

            Thank You

        </p>

        <p>

            Welcome Again

        </p>

    </div>

    <!-- ACTIONS -->

    <div class="center">

    <button
        onclick="printReceipt()"
        class="btn"
    >

        Print Receipt

    </button>

</div>

<script>

    function printReceipt()
    {
        window.print();

        setTimeout(() => {

            window.location.href =
                "{{ route('pos.index') }}";

        }, 1000);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO PRINT ON OPEN
    |--------------------------------------------------------------------------
    */

    window.onload = function ()
    {
        setTimeout(() => {

            window.print();

        }, 500);

        /*
        |--------------------------------------------------------------------------
        | RETURN TO POS
        |--------------------------------------------------------------------------
        */

        setTimeout(() => {

            window.location.href =
                "{{ route('pos.index') }}";

        }, 1500);
    };

</script>
</div>

</body>
</html>
