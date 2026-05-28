<!DOCTYPE html>
<html>
<head>

    <title>Inventory Stock History</title>

    <meta charset="UTF-8">

    <style>

        @page{
            size:A4 portrait;
            margin:15mm;
        }

        body{
            font-family: Arial, sans-serif;
            color:#111827;
            margin:0;
            padding:0;
            background:white;
        }

        .header{
            margin-bottom:25px;
        }

        .title{
            font-size:28px;
            font-weight:800;
            margin-bottom:6px;
        }

        .subtitle{
            font-size:13px;
            color:#6b7280;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            background:#0f172a;
            color:white;
            padding:12px;
            text-align:left;
            font-size:13px;
        }

        td{
            padding:12px;
            border:1px solid #e5e7eb;
            font-size:13px;
        }

        tr:nth-child(even){
            background:#f8fafc;
        }

        .sale{
            color:#dc2626;
            font-weight:700;
        }

        .stockin{
            color:#16a34a;
            font-weight:700;
        }

        .footer{
            margin-top:25px;
            text-align:center;
            font-size:12px;
            color:#6b7280;
        }

        @media print {

            body{
                zoom:95%;
            }

        }

    </style>

</head>

<body>

    <div class="header">

        <div class="title">

            IKOMEZA POS

        </div>

        <div class="subtitle">

            Inventory Stock History Report

        </div>

        <div class="subtitle">

            Generated:
            {{ now()->format('Y-m-d H:i A') }}

        </div>

    </div>

    <table>

        <thead>

            <tr>

                <th>Product</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Before</th>
                <th>After</th>
                <th>User</th>
                <th>Date</th>

            </tr>

        </thead>

        <tbody>

            @foreach($stockHistory as $history)

                <tr>

                    <td>
                        {{ $history->product->name ?? '-' }}
                    </td>

                    <td>

                        <span class="
                            {{ strtolower($history->type) == 'sale'
                                ? 'sale'
                                : 'stockin'
                            }}
                        ">

                            {{ strtoupper($history->type) }}

                        </span>

                    </td>

                    <td>
                        {{ $history->quantity }}
                    </td>

                    <td>
                        {{ $history->before_stock }}
                    </td>

                    <td>
                        {{ $history->after_stock }}
                    </td>

                    <td>
                        {{ $history->user->name ?? '-' }}
                    </td>

                    <td>
                        {{ $history->created_at->format('Y-m-d H:i') }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

    <div class="footer">

        IKOMEZA POS SYSTEM

    </div>

   <div style="margin-top:30px;text-align:center;">

    <button onclick="window.print()"
        style="
            padding:12px 25px;
            border:none;
            background:#111827;
            color:white;
            border-radius:10px;
            cursor:pointer;
            font-weight:bold;
        ">

        Print Report

    </button>

    <a href="/inventory"
       style="
            padding:12px 25px;
            background:#e5e7eb;
            color:#111827;
            text-decoration:none;
            border-radius:10px;
            margin-left:10px;
            font-weight:bold;
       ">

        Back

    </a>

</div>








</body>
</html>