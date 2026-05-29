<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Receipt - {{ $sale->receipt_no }}
    </title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            font-family: Arial, sans-serif;
            width:72mm;
            margin:auto;
            padding:8px;
            background:white;
            color:black;
            font-size:11px;
        }

        .header{

            display:flex;
            align-items:center;
            gap:8px;
            border-bottom:1px dashed #000;
            padding-bottom:8px;
            margin-bottom:8px;
        }

        .logo{

            width:45px;
            height:45px;
            object-fit:contain;
        }

        .company{

            flex:1;
        }

        .company h1{

            font-size:17px;
            font-weight:900;
            line-height:1;
        }

        .small{

            font-size:10px;
            margin-top:2px;
        }

        .line{

            border-top:1px dashed #000;
            margin:8px 0;
        }

        table{

            width:100%;
            border-collapse:collapse;
        }

        td{

            padding:3px 0;
            vertical-align:top;
        }

        .right{
            text-align:right;
        }

        .bold{
            font-weight:bold;
        }

        .items th{

            text-align:left;
            border-bottom:1px dashed #000;
            padding-bottom:4px;
            font-size:11px;
        }

        .items td{

            padding:5px 0;
        }

        .grand-total{

            font-size:18px;
            font-weight:900;
        }

        .footer{

            margin-top:12px;
            text-align:center;
            font-size:10px;
            border-top:1px dashed #000;
            padding-top:10px;
            line-height:1.6;
        }

        .barcode{

            text-align:center;
            margin-top:10px;
            font-size:15px;
            letter-spacing:2px;
            font-weight:bold;
        }

        .refund-banner{

            border:2px solid #991b1b;
            color:#991b1b;
            text-align:center;
            padding:8px;
            margin:8px 0;
            font-size:14px;
            font-weight:900;
            letter-spacing:1px;
        }

        .refund-note{
            text-align:center;
            font-size:10px;
            font-weight:bold;
            margin-top:4px;
        }

        .no-print{

            text-align:center;
            margin-top:12px;
        }

        .btn{

            background:black;
            color:white;
            border:none;
            padding:8px 14px;
            border-radius:6px;
            cursor:pointer;
            font-size:11px;
        }

        @media print {

            .no-print{
                display:none;
            }

            body{
                width:72mm;
            }
        }

    </style>

</head>

<body onload="printReceipt()">

    <!-- HEADER -->


<script>

function printReceipt()
{
    window.print();

    /*
    |--------------------------------------------------------------------------
    | AUTO BACK TO POS
    |--------------------------------------------------------------------------
    */

    setTimeout(() => {

        window.location.href = "/pos";

    }, 1000);
}

</script>








    <div class="header">

        <img
            src="{{ asset('images/logo.png') }}"
            class="logo"
            alt="Logo"
        >

        <div class="company">

            <h1>
                AGNES BAR
            </h1>

            <div class="small">
                Kigali - Rwanda
            </div>

            <div class="small">
                Tel: +250 780 000 000
            </div>

            <div class="small">
                info@agnesbar.com
            </div>

        </div>

    </div>

    <!-- INFO -->

    <table>

        <tr>

            <td class="bold">
                Invoice #
            </td>

            <td class="right">
                {{ $sale->receipt_no }}
            </td>

        </tr>

        <tr>

            <td class="bold">
                Date
            </td>

            <td class="right">
                {{ $sale->created_at->format('d/m/Y H:i') }}
            </td>

        </tr>

        <tr>

            <td class="bold">
                Cashier
            </td>

            <td class="right">
                {{ $sale->user->name ?? 'Cashier' }}
            </td>

        </tr>

        <tr>

            <td class="bold">
                Payment
            </td>

            <td class="right">
                {{ $sale->payment_method }}
            </td>

        </tr>

        @if($sale->is_refunded || $sale->sale_status === 'REFUNDED')
            <tr>
                <td class="bold">
                    Status
                </td>

                <td class="right">
                    REFUNDED
                </td>
            </tr>
        @endif

    </table>

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

    <table class="items">

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
                        {{ $item->product->name ?? 'Deleted' }}
                        <br>
                        <small>
                            {{ $item->department->name ?? $item->product?->department?->name ?? 'Department' }}
                        </small>
                    </td>

                    <td class="right">
                        x{{ $item->quantity }}
                    </td>

                    <td class="right">
                        {{ number_format($item->subtotal) }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <div class="line"></div>

    <!-- TOTAL -->

    <table>

        <tr>

            <td class="bold">
                TOTAL
            </td>

            <td class="right grand-total">
                {{ number_format($sale->grand_total) }} Frw
            </td>

        </tr>

        <tr>

            <td>
                Amount Paid
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

    </table>

    <!-- BARCODE -->

    <div class="barcode">

        *{{ $sale->receipt_no }}*

    </div>

    <!-- FOOTER -->

    <div class="footer">

        Thank you for your purchase

        <br>

        Visit Again

        <br><br>

        Email:
        info@agnesbar.com

        <br>

        Powered By BAR POS SYSTEM

    </div>

    <!-- PRINT -->

    <div class="no-print">

        <button
            onclick="window.print()"
            class="btn"
        >
            Print Again
        </button>

    </div>

</body>

</html>
