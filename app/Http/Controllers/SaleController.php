<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Refund;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\DepartmentAccessService;
use App\Services\BranchAccessService;
use App\Services\RefundWorkflowService;
use App\Services\StoreStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Services\SaleService;
class SaleController extends Controller
{
    protected $saleService;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        SaleService $saleService
    ) {
        $this->saleService = $saleService;
    }

    /*
    |--------------------------------------------------------------------------
    | SALES LIST
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTER + SEARCH
        |--------------------------------------------------------------------------
        */

        $filter = $request->filter ?? 'all';

        $search = $request->search ?? '';

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $departmentAccess = app(DepartmentAccessService::class);

        $selectedDepartmentId = $departmentAccess->selectedDepartmentId(
            $request->user(),
            $request->integer('department_id') ?: null
        );

        $departments = $departmentAccess->visibleDepartments($request->user());

        $query = Sale::with('user', 'items.department');
        app(BranchAccessService::class)->apply(
            $query,
            $request->user(),
            $request->integer('branch_id') ?: null
        );

        if ($selectedDepartmentId) {
            $query->whereHas('items', fn ($items) => $items->where('department_id', $selectedDepartmentId));
        }

        /*
        |--------------------------------------------------------------------------
        | USER
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | CASHIER RESTRICTION
        |--------------------------------------------------------------------------
        */

        if (
            $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
            
        ) {

            $query->where(
                'user_id',
                $user->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTERS
        |--------------------------------------------------------------------------
        */

        if ($filter == 'daily') {

            $query->whereDate(
                'created_at',
                today()
            );
        }

        elseif ($filter == 'weekly') {

            $query->whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]
            );
        }

        elseif ($filter == 'monthly') {

            $query->whereMonth(
                'created_at',
                now()->month
            )->whereYear(
                'created_at',
                now()->year
            );
        }

        elseif ($filter == 'yearly') {

            $query->whereYear(
                'created_at',
                now()->year
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if (!empty($search)) {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'receipt_no',
                    'LIKE',
                    '%' . $search . '%'
                )

                ->orWhere(
                    'sale_status',
                    'LIKE',
                    '%' . $search . '%'
                )

                ->orWhereHas('user', function ($u) use ($search) {

                    $u->where(
                        'name',
                        'LIKE',
                        '%' . $search . '%'
                    );

                });

            });
        }

        /*
        |--------------------------------------------------------------------------
        | RESULTS
        |--------------------------------------------------------------------------
        */

        $sales = $query
            ->latest()
            ->paginate(10);

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        $grossSales = (clone $query)
            ->sum('grand_total');

        $refundedSales = Sale::refundedAmountFor($query);

        $totalSales = (clone $query)
            ->revenueBearing()
            ->sum('grand_total');

        $totalTransactions = (clone $query)
            ->revenueBearing()
            ->count();

        $grossTransactions = (clone $query)
            ->count();

        $refundedTransactions = (clone $query)
            ->refundedOnly()
            ->count();

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'sales.index',
            compact(
                'sales',
                'totalSales',
                'grossSales',
                'refundedSales',
                'totalTransactions',
                'grossTransactions',
                'refundedTransactions',
                'filter',
                'search',
                'selectedDepartmentId',
                'departments'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT RECEIPT
    |--------------------------------------------------------------------------
    */

    public function print($id)
    {
        $sale = Sale::with([
            'items.product.department',
            'items.department',
            'user',
            'payments',
            'customer',
            'table'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | CASHIER SECURITY
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        if (!$user->hasOperationalRole('ADMIN', 'ADMINISTRATOR') && (int) $sale->branch_id !== (int) $user->branch_id) {
            abort(403);
        }

        if (
            $user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
            &&
            $sale->user_id != $user->id
        ) {

            abort(403);
        }

        return view(
            'sales.print',
            compact('sale')
        );
    }


public function refund(Request $request, $id, RefundWorkflowService $refundWorkflow)
{
    $request->validate([
        'refund_reason' => ['nullable', 'string', 'max:500'],
    ]);

    $sale = Sale::with('items.department')->findOrFail($id);

    try {
        $refundRequest = $refundWorkflow->request(
            $sale,
            $request->user(),
            $request->refund_reason
        );
    } catch (\Throwable $exception) {
        report($exception);

        return back()->with(
            'error',
            $exception->getMessage() ?: 'Refund request failed. No database data was deleted.'
        );
    }

    return back()->with(
        'success',
        'Refund request ' . $refundRequest->request_number . ' sent for approval. Stock will restore only after approval.'
    );
}

private function onlyExistingColumns(string $table, array $data): array
{
    if (!Schema::hasTable($table)) {
        return [];
    }

    return collect($data)
        ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
        ->all();
}





/*
|--------------------------------------------------------------------------
| SALE RECEIPT
|--------------------------------------------------------------------------
*/

public function receipt($id)
{
    $sale = Sale::with([

        'items.product.department',
        'items.department',
        'user',
        'payments',
        'customer',
        'table'

    ])->findOrFail($id);

    if (
        auth()->user()->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')
        && $sale->user_id !== auth()->id()
    ) {
        abort(403);
    }

    if (
        !auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
        && (int) $sale->branch_id !== (int) auth()->user()->branch_id
    ) {
        abort(403);
    }

    return view(

        'sales.receipt',

        compact('sale')

    );
}





















}
