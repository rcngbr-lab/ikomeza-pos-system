<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $query = Sale::with('user')
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'receipt_no',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'payment_method',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhereHas('user', function ($u) use ($request) {

                    $u->where(
                        'name',
                        'like',
                        '%' . $request->search . '%'
                    );
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        $filter = $request->filter ?? 'daily';

        if (in_array($filter, ['daily', 'today'], true)) {

            $query->whereDate(
                'created_at',
                Carbon::today()
            );
        }

        if (in_array($filter, ['weekly', 'week'], true)) {

            $query->whereBetween(
                'created_at',
                [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]
            );
        }

        if (in_array($filter, ['monthly', 'month'], true)) {

            $query->whereMonth(
                'created_at',
                Carbon::now()->month
            );
        }

        if (in_array($filter, ['yearly', 'year'], true)) {

            $query->whereYear(
                'created_at',
                Carbon::now()->year
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOM DATE RANGE
        |--------------------------------------------------------------------------
        */

        if (
            $request->start_date &&
            $request->end_date
        ) {

            $query->whereBetween(
                'created_at',
                [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CASHIER RESTRICTION
        |--------------------------------------------------------------------------
        */

        if (
            auth()->user()->hasOperationalRole('CASHIER')
        ) {

            $query->where(
                'user_id',
                auth()->id()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SALES LIST
        |--------------------------------------------------------------------------
        */

        $sales = (clone $query)
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        $totalRevenue = (clone $query)
            ->sum('grand_total');

        $totalTransactions = (clone $query)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PAYMENT METHODS
        |--------------------------------------------------------------------------
        */

        $cashSales = (clone $query)
            ->where('payment_method', 'CASH')
            ->sum('grand_total');

        $momoSales = (clone $query)
            ->where('payment_method', 'MOMO')
            ->sum('grand_total');

        $visaSales = (clone $query)
            ->where('payment_method', 'VISA')
            ->sum('grand_total');

        $masterSales = (clone $query)
            ->where('payment_method', 'MASTER_CARD')
            ->sum('grand_total');

        $airtelSales = (clone $query)
            ->where('payment_method', 'AIRTEL_MONEY')
            ->sum('grand_total');

        $bankSales = (clone $query)
            ->where('payment_method', 'BANK_TRANSFER')
            ->sum('grand_total');

        /*
        |--------------------------------------------------------------------------
        | PERFORMANCE DATA
        |--------------------------------------------------------------------------
        */

        $profit = SaleItem::whereHas('sale', function ($saleQuery) use ($query) {
            $saleQuery->whereIn(
                'id',
                (clone $query)->select('id')
            );
        })->sum('profit');

        $topProducts = SaleItem::with('product')
            ->whereHas('sale', function ($saleQuery) use ($query) {
                $saleQuery->whereIn(
                    'id',
                    (clone $query)->select('id')
                );
            })
            ->selectRaw('product_id, SUM(quantity) as units_sold, SUM(subtotal) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->take(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'reports.index',
            compact(
                'sales',
                'totalRevenue',
                'totalTransactions',
                'cashSales',
                'momoSales',
                'visaSales',
                'masterSales',
                'airtelSales',
                'bankSales',
                'filter',
                'profit',
                'topProducts'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT REPORT
    |--------------------------------------------------------------------------
    */

    public function print()
    {
        $sales = Sale::with('user')
            ->latest()
            ->get();

        return view(
            'reports.print',
            compact('sales')
        );
    }

    public function myReport(Request $request)
    {
        return redirect()->route('sales.index', [
            'filter' => $request->filter ?? 'daily',
        ]);
    }
}
