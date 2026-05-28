<!DOCTYPE html>
<html>

<head>

    <title>
        Shift Report
    </title>

    <style>

        body{
            font-family:Arial,sans-serif;
            padding:40px;
        }

        .title{
            text-align:center;
            margin-bottom:40px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th, td{
            border:1px solid #d1d5db;
            padding:14px;
            text-align:left;
        }

        th{
            background:#071133;
            color:white;
            width:250px;
        }

        .positive{
            color:green;
            font-weight:bold;
        }

        .negative{
            color:red;
            font-weight:bold;
        }

    </style>

</head>

<body onload="window.print()">

    <div class="title">

        <h1>
            AGNES BAR & RESTAURANT
        </h1>

        <h2>
            Shift Financial Report
        </h2>

    </div>

    <table>

        <tr>
            <th>Shift ID</th>
            <td>#{{ $shift->id }}</td>
        </tr>

        <tr>
            <th>Cashier</th>
            <td>{{ $shift->user->name ?? 'N/A' }}</td>
        </tr>

        <tr>
            <th>Status</th>
            <td>{{ $shift->status }}</td>
        </tr>

        <tr>
            <th>Opening Balance</th>
            <td>{{ number_format($shift->opening_balance) }} RWF</td>
        </tr>

        <tr>
            <th>Total Sales</th>
            <td>{{ number_format($shift->total_sales) }} RWF</td>
        </tr>

        <tr>
            <th>Expected Cash</th>
            <td>{{ number_format($shift->expected_cash) }} RWF</td>
        </tr>

        <tr>
            <th>Closing Balance</th>
            <td>{{ number_format($shift->closing_balance) }} RWF</td>
        </tr>

        <tr>

            <th>Difference</th>

            <td>

                @if($shift->difference < 0)

                    <span class="negative">

                        {{ number_format($shift->difference) }} RWF

                    </span>

                @else

                    <span class="positive">

                        {{ number_format($shift->difference) }} RWF

                    </span>

                @endif

            </td>

        </tr>

        <tr>
            <th>Opened At</th>
            <td>{{ $shift->opened_at }}</td>
        </tr>

        <tr>
            <th>Closed At</th>
            <td>{{ $shift->closed_at }}</td>
        </tr>

    </table>

</body>

</html>