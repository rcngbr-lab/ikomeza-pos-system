<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CategoryCatalogService;
use App\Services\DepartmentAccessService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        app(CategoryCatalogService::class)->ensureDefaults();

        $departmentAccess = app(DepartmentAccessService::class);

        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $request->user(),
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($request->user());

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $query = Sale::with('user', 'items.department')
            ->latest();

        if ($selectedDepartmentId) {
            $query->whereHas('items', fn ($items) => $items->where('department_id', $selectedDepartmentId));
        }

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

        if (auth()->user()->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {

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

        $printSales = (clone $query)
            ->with('user', 'items.department')
            ->limit(500)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        $saleIds = (clone $query)->select('id');

        $departmentItemQuery = SaleItem::whereHas('sale', function ($saleQuery) use ($query) {
            $saleQuery->whereIn(
                'id',
                (clone $query)->select('id')
            );
        })->when($selectedDepartmentId, fn ($items) => $items->where('department_id', $selectedDepartmentId));

        $totalRevenue = $selectedDepartmentId
            ? (clone $departmentItemQuery)->sum('subtotal')
            : (clone $query)->sum('grand_total');

        $totalTransactions = (clone $query)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PAYMENT METHODS
        |--------------------------------------------------------------------------
        */

        $paymentSums = $selectedDepartmentId
            ? SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereIn('sale_items.sale_id', $saleIds)
                ->where('sale_items.department_id', $selectedDepartmentId)
                ->selectRaw('sales.payment_method, SUM(sale_items.subtotal) as total')
                ->groupBy('sales.payment_method')
                ->pluck('total', 'payment_method')
            : collect([
                'CASH' => (clone $query)->where('payment_method', 'CASH')->sum('grand_total'),
                'MOMO' => (clone $query)->where('payment_method', 'MOMO')->sum('grand_total'),
                'VISA' => (clone $query)->where('payment_method', 'VISA')->sum('grand_total'),
                'MASTER_CARD' => (clone $query)->where('payment_method', 'MASTER_CARD')->sum('grand_total'),
                'AIRTEL_MONEY' => (clone $query)->where('payment_method', 'AIRTEL_MONEY')->sum('grand_total'),
                'BANK_TRANSFER' => (clone $query)->where('payment_method', 'BANK_TRANSFER')->sum('grand_total'),
            ]);

        $cashSales = (float) ($paymentSums['CASH'] ?? 0);
        $momoSales = (float) ($paymentSums['MOMO'] ?? 0);
        $visaSales = (float) ($paymentSums['VISA'] ?? 0);
        $masterSales = (float) ($paymentSums['MASTER_CARD'] ?? 0);
        $airtelSales = (float) ($paymentSums['AIRTEL_MONEY'] ?? 0);
        $bankSales = (float) ($paymentSums['BANK_TRANSFER'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | PERFORMANCE DATA
        |--------------------------------------------------------------------------
        */

        $profit = (clone $departmentItemQuery)->sum('profit');

        $topProducts = (clone $departmentItemQuery)
            ->with('product.department')
            ->selectRaw('product_id, SUM(quantity) as units_sold, SUM(subtotal) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->take(5)
            ->get();

        $departmentBreakdown = SaleItem::query()
            ->with('department')
            ->whereHas('sale', function ($saleQuery) use ($query) {
                $saleQuery->whereIn(
                    'id',
                    (clone $query)->select('id')
                );
            })
            ->selectRaw('department_id, SUM(subtotal) as revenue, SUM(profit) as profit, SUM(quantity) as units_sold')
            ->groupBy('department_id')
            ->orderByDesc('revenue')
            ->get();

        $reportPeriod = $this->periodLabel($filter, $request);
        $reportDepartment = $departments
            ->firstWhere('id', (int) $selectedDepartmentId)
            ?->name ?? 'All Departments';

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
                'topProducts',
                'departments',
                'selectedDepartmentId',
                'departmentBreakdown',
                'printSales',
                'reportPeriod',
                'reportDepartment'
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

    private function periodLabel(string $filter, Request $request): string
    {
        if ($request->start_date && $request->end_date) {
            return $request->start_date . ' to ' . $request->end_date;
        }

        return match ($filter) {
            'weekly', 'week' => 'This Week',
            'monthly', 'month' => 'This Month',
            'yearly', 'year' => 'This Year',
            'range' => 'Custom Range',
            default => 'Today',
        };
    }
}
