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

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $analytics = $this->analyticsFor($user);
        $selectedDepartmentId = app(DepartmentAccessService::class)->selectedDepartmentId($user);

        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')) {
            return view('dashboard.admin', array_merge($analytics, [
                'totalProducts' => Product::count(),
                'totalStock' => Product::sum('stock'),
                'totalSales' => Sale::count(),
                'totalRevenue' => Sale::sum('grand_total'),
                'totalUsers' => User::count(),
                'totalCategories' => Category::count(),
                'cashierPerformance' => $this->cashierPerformance(),
                'shiftDifferences' => $this->recentShiftDifferences(),
                'pendingRefunds' => $this->pendingRefunds(),
                'recentMovements' => StockMovement::with('product', 'user')->latest()->take(6)->get(),
                'recentAuditLogs' => AuditLog::with('user')->latest()->take(6)->get(),
            ]));
        }

        if ($user->hasOperationalRole('MANAGER', 'KITCHEN_MANAGER', 'KITCHEN_CHIEF', 'BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            return view('dashboard.manager', [
                'todayRevenue' => $analytics['todayRevenue'],
                'todayTransactions' => $analytics['todayTransactions'],
                'lowStock' => $analytics['lowStockProducts'],
                'paymentBreakdown' => $analytics['paymentBreakdown'],
                'recentSales' => $analytics['recentSales'],
                'cashierPerformance' => $this->cashierPerformance(),
                'shiftDifferences' => $this->recentShiftDifferences(),
                'pendingRefunds' => $this->pendingRefunds(),
                'recentMovements' => StockMovement::with('product', 'department', 'user')
                    ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
                    ->latest()
                    ->take(6)
                    ->get(),
            ]);
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
                ->where('sale_status', 'COMPLETED')
                ->whereDate('created_at', today());

            $shiftCashSales = $activeShift
                ? Sale::where('shift_id', $activeShift->id)
                    ->where('sale_status', 'COMPLETED')
                    ->where('payment_method', 'CASH')
                    ->sum('grand_total')
                : 0;

            return view('dashboard.cashier', [
                'todayRevenue' => (clone $todaySales)->sum('grand_total'),
                'todayTransactions' => (clone $todaySales)->count(),
                'paymentBreakdown' => (clone $todaySales)
                    ->selectRaw('payment_method, SUM(grand_total) as total, COUNT(*) as count')
                    ->groupBy('payment_method')
                    ->orderByDesc('total')
                    ->get(),
                'recentSales' => Sale::where('user_id', $user->id)
                    ->latest()
                    ->take(8)
                    ->get(),
                'activeShift' => $activeShift,
                'expectedCash' => $activeShift
                    ? (float) $activeShift->opening_cash + (float) $shiftCashSales
                    : 0,
                'lowStockProducts' => Product::where('active', true)
                    ->whereColumn('stock', '<=', 'alert_stock')
                    ->orderBy('stock')
                    ->take(5)
                    ->get(['id', 'name', 'stock', 'alert_stock']),
            ]);
        }

        abort(403);
    }

    private function analyticsFor($user): array
    {
        $selectedDepartmentId = app(DepartmentAccessService::class)->selectedDepartmentId($user);
        $salesQuery = Sale::query();

        if ($selectedDepartmentId) {
            $salesQuery->whereHas('items', fn ($items) => $items->where('department_id', $selectedDepartmentId));
        }

        if ($user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            $salesQuery->where('user_id', $user->id);
        }

        $recentSales = (clone $salesQuery)
            ->with('user', 'items.department')
            ->latest()
            ->take(10)
            ->get();

        $todayTransactions = (clone $salesQuery)->whereDate('created_at', today())->count();

        if ($selectedDepartmentId) {
            $todayRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereDate('created_at', today()));
            $weekRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]));
            $monthRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year));
            $yearRevenue = $this->departmentRevenueFor($salesQuery, $selectedDepartmentId, fn ($sale) => $sale->whereYear('created_at', now()->year));
        } else {
            $todayRevenue = (clone $salesQuery)->whereDate('created_at', today())->sum('grand_total');
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
                ->selectRaw('sales.payment_method, SUM(sale_items.subtotal) as total, COUNT(DISTINCT sales.id) as count')
                ->groupBy('sales.payment_method')
                ->orderByDesc('total')
                ->get()
            : Sale::query()
                ->selectRaw('payment_method, SUM(grand_total) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->get();

        $profit = SaleItem::when($selectedDepartmentId, fn ($items) => $items->where('department_id', $selectedDepartmentId))->sum('profit');
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

    private function cashierPerformance()
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
                'sales as transactions_today' => function ($query) {
                    $query->where('sale_status', 'COMPLETED')
                        ->whereDate('created_at', today());
                },
            ])
            ->withSum([
                'sales as revenue_today' => function ($query) {
                    $query->where('sale_status', 'COMPLETED')
                        ->whereDate('created_at', today());
                },
            ], 'grand_total')
            ->orderByDesc('revenue_today')
            ->take(8)
            ->get();
    }

    private function departmentRevenueFor($salesQuery, int $departmentId, callable $dateScope): float
    {
        $scopedSales = $dateScope(clone $salesQuery);

        return (float) SaleItem::where('department_id', $departmentId)
            ->whereHas('sale', function ($sale) use ($scopedSales) {
                $sale->whereIn('id', $scopedSales->select('id'));
            })
            ->sum('subtotal');
    }

    private function recentShiftDifferences()
    {
        return Shift::with('user')
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->take(6)
            ->get();
    }

    private function pendingRefunds()
    {
        return Refund::with('sale.user', 'user')
            ->where('status', Refund::STATUS_PENDING)
            ->latest()
            ->take(6)
            ->get();
    }
}
