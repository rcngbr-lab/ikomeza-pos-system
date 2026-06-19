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

            font-family: Arial, Helvetica, sans-serif;
            width:72mm;
            margin:auto;
            padding:6px;
            color:#000;
            background:#fff;
            font-size:11px;
        }

        .header{

            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:10px;
            border-bottom:1px dashed #000;
            padding-bottom:8px;
        }

        .logo{

            width:45px;
            height:45px;
            object-fit:contain;
        }

        .company-info{

            flex:1;
        }

        .company-name{

            font-size:18px;
            font-weight:900;
            line-height:1.1;
        }

        .small{

            font-size:10px;
            line-height:1.4;
        }

        .center{
            text-align:center;
        }

        .line{

            border-top:1px dashed #000;
            margin:8px 0;
        }

        table{

            width:100%;
            border-collapse:collapse;
        }

        table td{

            padding:3px 0;
            vertical-align:top;
            font-size:11px;
        }

        .right{
            text-align:right;
        }

        .bold{
            font-weight:bold;
        }

        .items th{

            border-bottom:1px dashed #000;
            padding-bottom:4px;
            font-size:11px;
            text-align:left;
        }

        .items td{

            padding:5px 0;
        }

        .total-box{

            margin-top:8px;
            border-top:1px dashed #000;
            padding-top:8px;
        }

        .grand-total{

            font-size:18px;
            font-weight:900;
        }

        .payment{

            margin-top:5px;
            font-size:11px;
        }

        .footer{

            margin-top:15px;
            text-align:center;
            font-size:10px;
            border-top:1px dashed #000;
            padding-top:10px;
            line-height:1.6;
        }

        .barcode{

            text-align:center;
            font-size:16px;
            font-weight:bold;
            letter-spacing:2px;
            margin-top:10px;
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

            margin-top:12px;
            text-align:center;
        }

        .btn{

            background:#111827;
            color:#fff;
            border:none;
            padding:8px 16px;
            border-radius:6px;
            cursor:pointer;
            font-size:12px;
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

<body onload="window.print()">

    <!-- HEADER -->

    <div class="header">

        <!-- COMPANY LOGO -->

        <img
            src="{{ asset('images/logo.png') }}"
            alt="Logo"
            class="logo"
        >

        <!-- COMPANY INFO -->

        <div class="company-info">

            <div class="company-name">
                FRONTIER SHOP
            </div>

            <div class="small">
                Kigali - Rwanda
            </div>

            <div class="small">
                Tel: +250 780 000 000
            </div>

            <div class="small">
                Email: info@frontiershop.rw
            </div>

        </div>

    </div>

    <!-- RECEIPT INFO -->

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
                Service Date
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
                {{ $sale->paymentMethodLabel() }} / {{ $sale->payment_status }}
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
                    Amt
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

    <!-- TOTALS -->

    <div class="total-box">

        <table>

            <tr>
                <td>
                    Subtotal
                </td>
                <td class="right">
                    {{ number_format($sale->subtotal) }} Frw
                </td>
            </tr>

            @if((float) $sale->discount > 0)
                <tr>
                    <td>
                        Discount
                    </td>
                    <td class="right">
                        -{{ number_format($sale->discount) }} Frw
                    </td>
                </tr>
            @endif

            @if((float) $sale->tax > 0)
                <tr>
                    <td>
                        VAT {{ number_format($sale->vat_rate, 1) }}%
                    </td>
                    <td class="right">
                        {{ number_format($sale->tax) }} Frw
                    </td>
                </tr>
            @endif

            <tr>

                <td class="bold">
                    Total
                </td>

                <td class="right grand-total">
                    {{ number_format($sale->grand_total) }} Frw
                </td>

            </tr>

        </table>

        <div class="payment">

            Amount Paid:
            <strong>
                {{ number_format($sale->amount_paid) }} Frw
            </strong>

        </div>

        <div class="payment">

            Change:
            <strong>
                {{ number_format($sale->change_amount) }} Frw
            </strong>

        </div>

        @if((float) $sale->credit_due > 0)
            <div class="payment">
                Credit Due:
                <strong>
                    {{ number_format($sale->credit_due) }} Frw
                </strong>
            </div>
        @endif

        @if($sale->payments->count())
            <div class="payment">
                @foreach($sale->payments as $payment)
                    {{ \App\Models\Sale::PAYMENT_METHOD_LABELS[$payment->method] ?? $payment->method }}:
                    <strong>{{ number_format($payment->amount) }} Frw</strong><br>
                @endforeach
            </div>
        @endif

    </div>

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
        info@frontiershop.rw

        <br>

        Powered By BAR POS SYSTEM

    </div>

    <!-- PRINT BUTTON -->

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
