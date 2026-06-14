<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DepartmentAccessService;
use App\Services\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $dateRange = $this->dashboardDateRange($request);
        $branchAccess = app(BranchAccessService::class);
        $selectedBranchId = $branchAccess->selectedBranchId($user, $request->integer('branch_id') ?: null);
        $analytics = $this->analyticsFor($user, $dateRange, $selectedBranchId);
        $selectedDepartmentId = app(DepartmentAccessService::class)->selectedDepartmentId($user);
        $dashboardContext = [
            'dateRange' => $dateRange,
            'dateLabel' => $dateRange['label'],
            'dateFilter' => $dateRange['filter'],
            'selectedBranchId' => $selectedBranchId,
            'branches' => $branchAccess->visibleBranches($user),
        ];

        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            $salesForPeriod = Sale::query()->revenueBearing();
            $branchAccess->apply($salesForPeriod, $user, $selectedBranchId);
            $this->applyDateRange($salesForPeriod, $dateRange);

            $refundedForPeriod = Sale::query()->refundedOnly();
            $branchAccess->apply($refundedForPeriod, $user, $selectedBranchId);
            $this->applyDateRange($refundedForPeriod, $dateRange, 'refunded_at');

            $movementsForPeriod = StockMovement::with('product', 'user')->latest();
            $branchAccess->apply($movementsForPeriod, $user, $selectedBranchId);
            $this->applyDateRange($movementsForPeriod, $dateRange);

            $auditForPeriod = AuditLog::with('user')->latest();
            $branchAccess->apply($auditForPeriod, $user, $selectedBranchId);
            $this->applyDateRange($auditForPeriod, $dateRange);

            return view('dashboard.admin', array_merge($analytics, $dashboardContext, [
                'totalProducts' => Product::count(),
                'totalStock' => Product::sum('stock'),
                'totalSales' => (clone $salesForPeriod)->count(),
                'totalRevenue' => (clone $salesForPeriod)->sum('grand_total'),
                'totalRefunds' => Sale::refundedAmountFor($refundedForPeriod),
                'totalUsers' => User::count(),
                'totalCategories' => Category::count(),
                'cashierPerformance' => $this->cashierPerformance($dateRange),
                'shiftDifferences' => $this->recentShiftDifferences($dateRange),
                'pendingRefunds' => $this->pendingRefunds($dateRange),
                'recentMovements' => $movementsForPeriod->take(6)->get(),
                'recentAuditLogs' => $auditForPeriod->take(6)->get(),
            ]));
        }

        if ($user->hasOperationalRole('MANAGER', 'STORE_KEEPER', 'KITCHEN_MANAGER', 'KITCHEN_CHIEF', 'BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            $movementsForPeriod = StockMovement::with('product', 'department', 'user')
                ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
                ->latest();
            $branchAccess->apply($movementsForPeriod, $user, $selectedBranchId);

            $this->applyDateRange($movementsForPeriod, $dateRange);

            return view('dashboard.manager', array_merge($dashboardContext, [
                'todayRevenue' => $analytics['todayRevenue'],
                'todayTransactions' => $analytics['todayTransactions'],
                'lowStock' => $analytics['lowStockProducts'],
                'paymentBreakdown' => $analytics['paymentBreakdown'],
                'recentSales' => $analytics['recentSales'],
                'cashierPerformance' => $this->cashierPerformance($dateRange),
                'shiftDifferences' => $this->recentShiftDifferences($dateRange),
                'pendingRefunds' => $this->pendingRefunds($dateRange),
                'recentMovements' => $movementsForPeriod->take(6)->get(),
            ]));
        }

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            $activeShift = Shift::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('is_open', true)
                        ->orWhere('status', 'OPEN');
                })
                ->latest()
                ->first();

            $todaySales = Sale::where('user_id', $user->id)
                ->revenueBearing();

            $this->applyDateRange($todaySales, $dateRange);

            $shiftCashSales = $activeShift
                ? Sale::where('shift_id', $activeShift->id)
                    ->where('sale_status', 'COMPLETED')
                    ->where('payment_method', 'CASH')
                    ->sum('grand_total')
                : 0;

            $recentSales = Sale::where('user_id', $user->id)
                ->latest();

            $this->applyDateRange($recentSales, $dateRange);

            return view('dashboard.cashier', array_merge($dashboardContext, [
                'todayRevenue' => (clone $todaySales)->sum('grand_total'),
                'todayTransactions' => (clone $todaySales)->count(),
                'paymentBreakdown' => (clone $todaySales)
                    ->selectRaw('payment_method, SUM(grand_total) as total, COUNT(*) as count')
                    ->groupBy('payment_method')
                    ->orderByDesc('total')
                    ->get(),
                'recentSales' => $recentSales->take(8)->get(),
                'activeShift' => $activeShift,
                'expectedCash' => $activeShift
                    ? (float) $activeShift->opening_cash + (float) $shiftCashSales
                    : 0,
                'lowStockProducts' => Product::where('active', true)
                    ->whereColumn('stock', '<=', 'alert_stock')
                    ->orderBy('stock')
                    ->take(5)
                    ->get(['id', 'name', 'stock', 'alert_stock']),
            ]));
        }

        abort(403);
    }

    private function analyticsFor($user, array $dateRange, ?int $selectedBranchId = null): array
    {
        $selectedDepartmentId = app(DepartmentAccessService::class)->selectedDepartmentId($user);
        $branchAccess = app(BranchAccessService::class);
        $salesQuery = Sale::query()->revenueBearing();
        $branchAccess->apply($salesQuery, $user, $selectedBranchId);

        if ($selectedDepartmentId) {
            $salesQuery->whereHas('items', fn ($items) => $items->where('department_id', $selectedDepartmentId));
        }

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            $salesQuery->where('user_id', $user->id);
        }

        $periodSalesQuery = clone $salesQuery;
        $this->applyDateRange($periodSalesQuery, $dateRange);

        $recentSales = (clone $periodSalesQuery)
            ->with('user', 'items.department')
            ->latest()
            ->take(10)
            ->get();

        $todayTransactions = (clone $periodSalesQuery)->count();

        if ($selectedDepartmentId) {
            $todayRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, function ($sale) use ($dateRange) {
                $this->applyDateRange($sale, $dateRange);
            });
            $weekRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]));
            $monthRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year));
            $yearRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereYear('created_at', now()->year));
        } else {
            $todayRevenue = (clone $periodSalesQuery)->sum('grand_total');
            $weekRevenue = (clone $salesQuery)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('grand_total');
            $monthRevenue = (clone $salesQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('grand_total');
            $yearRevenue = (clone $salesQuery)
                ->whereYear('created_at', now()->year)
                ->sum('grand_total');
        }

        $topProducts = SaleItem::with('product.department')
            ->whereHas('sale', function ($sale) use ($dateRange) {
                $sale->revenueBearing();
                $this->applyDateRange($sale, $dateRange);
            })
            ->whereHas('sale', fn ($sale) => app(BranchAccessService::class)->apply($sale, $user, $selectedBranchId))
            ->when($selectedDepartmentId, fn ($items) => $items->where('department_id', $selectedDepartmentId))
            ->selectRaw('product_id, SUM(quantity) as units_sold, SUM(subtotal) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->take(5)
            ->get();

        $paymentBreakdown = $selectedDepartmentId
            ? SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.department_id', $selectedDepartmentId)
                ->where('sales.sale_status', Sale::STATUS_COMPLETED)
                ->when($selectedBranchId, fn ($query) => $query->where('sales.branch_id', $selectedBranchId))
                ->where(function ($query) {
                    $query->whereNull('sales.is_refunded')
                        ->orWhere('sales.is_refunded', false);
                })
                ->when($dateRange['start'], fn ($query) => $query->where('sales.created_at', '>=', $dateRange['start']))
                ->when($dateRange['end'], fn ($query) => $query->where('sales.created_at', '<=', $dateRange['end']))
                ->selectRaw('sales.payment_method, SUM(sale_items.subtotal) as total, COUNT(DISTINCT sales.id) as count')
                ->groupBy('sales.payment_method')
                ->orderByDesc('total')
                ->get()
            : (clone $periodSalesQuery)
                ->selectRaw('payment_method, SUM(grand_total) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->get();

        $profit = SaleItem::whereHas('sale', function ($sale) use ($dateRange) {
                $sale->revenueBearing();
                $this->applyDateRange($sale, $dateRange);
            })
            ->whereHas('sale', fn ($sale) => app(BranchAccessService::class)->apply($sale, $user, $selectedBranchId))
            ->when($selectedDepartmentId, fn ($items) => $items->where('department_id', $selectedDepartmentId))
            ->sum('profit');
        $lowStockProducts = Product::with('department')
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->whereColumn('stock', '<=', 'alert_stock')
            ->orderBy('stock')
            ->take(10)
            ->get();

        return [
            'todayRevenue' => $todayRevenue,
            'todayTransactions' => $todayTransactions,
            'weekRevenue' => $weekRevenue,
            'monthRevenue' => $monthRevenue,
            'yearRevenue' => $yearRevenue,
            'profit' => $profit,
            'averageSale' => $todayTransactions > 0 ? $todayRevenue / $todayTransactions : 0,
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
            'paymentBreakdown' => $paymentBreakdown,
            'lowStockProducts' => $lowStockProducts,
            'inventoryValue' => Product::when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
                ->selectRaw('SUM(buy_price * stock) as total')
                ->value('total') ?? 0,
            'openShifts' => Shift::where(function ($query) {
                $query->where('is_open', true)
                    ->orWhere('status', 'OPEN');
            })->count(),
        ];
    }

    private function cashierPerformance(array $dateRange)
    {
        return User::query()
            ->where(function ($query) {
                $query->whereRaw('UPPER(role) = ?', ['CASHIER'])
                    ->orWhereRaw('UPPER(role) = ?', ['WAITER'])
                    ->orWhereRaw('UPPER(role) = ?', ['SERVER'])
                    ->orWhereHas('roles', function ($roles) {
                        $roles->whereRaw('UPPER(name) = ?', ['CASHIER'])
                            ->orWhereRaw('UPPER(name) = ?', ['WAITER'])
                            ->orWhereRaw('UPPER(name) = ?', ['SERVER'])
                            ->orWhereRaw('UPPER(code) = ?', ['CASHIER'])
                            ->orWhereRaw('UPPER(code) = ?', ['WAITER'])
                            ->orWhereRaw('UPPER(code) = ?', ['SERVER']);
                    });
            })
            ->withCount([
                'sales as transactions_today' => function ($query) use ($dateRange) {
                    $query->revenueBearing();
                    $this->applyDateRange($query, $dateRange);
                },
            ])
            ->withSum([
                'sales as revenue_today' => function ($query) use ($dateRange) {
                    $query->revenueBearing();
                    $this->applyDateRange($query, $dateRange);
                },
            ], 'grand_total')
            ->orderByDesc('revenue_today')
            ->take(8)
            ->get();
    }

    private function departmentRevenueFor($salesQuery, int $departmentId, callable $dateScope): float
    {
        $scopedSales = clone $salesQuery;
        $dateScope($scopedSales);

        return (float) SaleItem::where('department_id', $departmentId)
            ->whereHas('sale', function ($sale) use ($scopedSales) {
                $sale->whereIn('id', $scopedSales->select('id'));
            })
            ->sum('subtotal');
    }

    private function recentShiftDifferences(array $dateRange)
    {
        $query = Shift::with('user')
            ->whereNotNull('closed_at')
            ->latest('closed_at');

        $this->applyDateRange($query, $dateRange, 'closed_at');

        return $query
            ->take(6)
            ->get();
    }

    private function pendingRefunds(array $dateRange)
    {
        $query = Refund::with('sale.user', 'user')
            ->where('status', Refund::STATUS_PENDING)
            ->latest();

        $this->applyDateRange($query, $dateRange);

        return $query
            ->take(6)
            ->get();
    }

    private function dashboardDateRange(Request $request): array
    {
        $filter = $request->input('filter', 'today');
        $start = null;
        $end = null;
        $label = 'Today';

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $filter = 'range';
        }

        switch ($filter) {
            case 'all':
                $label = 'All Time';
                break;
            case 'yesterday':
                $start = today()->subDay()->startOfDay();
                $end = today()->subDay()->endOfDay();
                $label = 'Yesterday';
                break;
            case 'week':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                $label = 'This Week';
                break;
            case 'last_week':
                $start = now()->subWeek()->startOfWeek();
                $end = now()->subWeek()->endOfWeek();
                $label = 'Last Week';
                break;
            case 'month':
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                $label = 'This Month';
                break;
            case 'last_month':
                $start = now()->subMonthNoOverflow()->startOfMonth();
                $end = now()->subMonthNoOverflow()->endOfMonth();
                $label = 'Last Month';
                break;
            case 'year':
                $start = now()->startOfYear();
                $end = now()->endOfYear();
                $label = 'This Year';
                break;
            case 'range':
                $start = $request->filled('start_date')
                    ? Carbon::parse($request->start_date)->startOfDay()
                    : null;
                $end = $request->filled('end_date')
                    ? Carbon::parse($request->end_date)->endOfDay()
                    : null;
                $label = $this->customDateLabel($start, $end);
                break;
            case 'today':
            default:
                $filter = 'today';
                $start = today()->startOfDay();
                $end = today()->endOfDay();
                $label = 'Today';
                break;
        }

        return [
            'filter' => $filter,
            'start' => $start,
            'end' => $end,
            'label' => $label,
        ];
    }

    private function applyDateRange($query, array $dateRange, string $column = 'created_at'): void
    {
        if ($dateRange['start']) {
            $query->where($column, '>=', $dateRange['start']);
        }

        if ($dateRange['end']) {
            $query->where($column, '<=', $dateRange['end']);
        }
    }

    private function customDateLabel(?Carbon $start, ?Carbon $end): string
    {
        if ($start && $end) {
            return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
        }

        if ($start) {
            return 'From ' . $start->format('M d, Y');
        }

        if ($end) {
            return 'Until ' . $end->format('M d, Y');
        }

        return 'Custom Range';
    }
}
