<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>

        Receipt

    </title>

    <style>

        body {

            font-family: Arial, sans-serif;
            width: 300px;
            margin: auto;
            padding: 10px;
            color: #000;
            font-size: 13px;

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
            border-collapse: collapse;

        }

        td {

            padding: 4px 0;
            vertical-align: top;

        }

        .right {

            text-align: right;

        }

        .bold {

            font-weight: bold;

        }

        .small {

            font-size: 12px;

        }

        .mt {

            margin-top: 10px;

        }

        .btn-area {

            margin-top: 20px;
            text-align: center;

        }

        button,
        a {

            display: inline-block;
            padding: 10px 14px;
            border: none;
            background: #111827;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;

        }

        a {

            background: #16a34a;

        }

        @media print {

            .btn-area {

                display: none;

            }

        }

    </style>

</head>

<body>

    <!-- STORE -->

    <div class="center">

        <h2>

            IKOMEZA BAR POS

        </h2>

        <div class="small">

            Kigali - Rwanda

        </div>

        <div class="small">

            Tel: 078XXXXXXX

        </div>

    </div>

    <div class="line"></div>

    <!-- RECEIPT INFO -->

    <div class="small">

        <div>

            Receipt:
            {{ $sale->receipt_no }}

        </div>

        <div>

            Date:
            {{ $sale->created_at }}

        </div>

        <div>

            Cashier:
            {{ $sale->user->name ?? 'Admin' }}

        </div>

    </div>

    <div class="line"></div>

    <!-- ITEMS -->

    <table>

        @foreach($sale->items as $item)

            <tr>

                <td colspan="2">

                    {{ $item->product->name }}

                </td>

            </tr>

            <tr>

                <td class="small">

                    {{ $item->quantity }}

                    ×

                    {{ number_format($item->price) }}

                </td>

                <td class="right small">

                    {{ number_format($item->subtotal) }}

                </td>

            </tr>

        @endforeach

    </table>

    <div class="line"></div>

    <!-- TOTALS -->

    <table>

        <tr>

            <td class="bold">

                TOTAL

            </td>

            <td class="right bold">

                {{ number_format($sale->grand_total) }} Frw

            </td>

        </tr>

        <tr>

            <td>

                Paid

            </td>

            <td class="right">

                {{ number_format($sale->amount_paid) }} Frw

            </td>

        </tr>

        <tr>

            <td>

                Change

            </td>

            <td class="right">

                {{ number_format($sale->change_amount) }} Frw

            </td>

        </tr>

        <tr>

            <td>

                Payment

            </td>

            <td class="right">

                {{ $sale->payment_method }}

            </td>

        </tr>

    </table>

    <div class="line"></div>

    <!-- FOOTER -->

    <div class="center small mt">

        Thank you for your purchase

    </div>

    <!-- BUTTONS -->

    <div class="btn-area">

        <button onclick="window.print()">

            Print Receipt

        </button>

        <a href="{{ route('cart.index') }}">

            Back To POS

        </a>

    </div>

    <!-- AUTO PRINT -->

    <script>

        window.onload = function () {

            window.print();

            setTimeout(function () {

                window.location.href =
                    "{{ route('cart.index') }}";

            }, 1200);

        };

    </script>

</body>

</html>