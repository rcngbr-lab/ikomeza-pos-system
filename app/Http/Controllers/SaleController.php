<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Refund;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

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

        $query = Sale::with('user');

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
            $user->hasOperationalRole('CASHIER')
            
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

        $totalSales = (clone $query)
            ->sum('grand_total');

        $totalTransactions = (clone $query)
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
                'totalTransactions',
                'filter',
                'search'
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
            'items.product',
            'user'
        ])->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | CASHIER SECURITY
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        if (
            $user->hasOperationalRole('CASHIER')
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


public function refund(Request $request, $id)
{
    $request->validate([
        'refund_reason' => ['nullable', 'string', 'max:500'],
    ]);

    $sale = Sale::with('items')->findOrFail($id);

    if ($sale->is_refunded) {

        return back()->with(
            'error',
            'Sale already refunded.'
        );
    }

    DB::transaction(function () use ($sale, $request) {
        $refund = Refund::create([

            'sale_id' => $sale->id,

            'user_id' => auth()->id(),

            'amount' => $sale->grand_total,

            'reason' => $request->refund_reason,

            'status' => 'COMPLETED',

            'refunded_at' => now(),

        ]);

    foreach ($sale->items as $item) {

        $product = Product::find(
            $item->product_id
        );

        if ($product) {

            $before = $product->stock;

            $product->increment(
                'stock',
                $item->quantity
            );

            $product->refresh();

            Stock::create([
                'product_id' => $product->id,
                'type' => 'refund',
                'quantity' => $item->quantity,
                'before_stock' => $before,
                'after_stock' => $product->stock,
                'note' => 'Refund for ' . $sale->receipt_no,
                'user_id' => auth()->id(),
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'branch_id' => auth()->user()->branch_id,
                'user_id' => auth()->id(),
                'type' => 'REFUND',
                'quantity' => $item->quantity,
                'before_stock' => $before,
                'after_stock' => $product->stock,
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'reason' => $request->refund_reason,
            ]);
        }
    }

    $sale->update([

        'is_refunded' => true,

        'refund_amount' => $sale->grand_total,

        'refund_reason' => $request->refund_reason,

        'refunded_at' => now(),

        'refunded_by' => auth()->id(),

        'sale_status' => 'REFUNDED',

    ]);
    });

    return back()->with(
        'success',
        'Sale refunded successfully.'
    );
}





/*
|--------------------------------------------------------------------------
| SALE RECEIPT
|--------------------------------------------------------------------------
*/

public function receipt($id)
{
    $sale = Sale::with([

        'items.product',
        'user'

    ])->findOrFail($id);

    if (
        auth()->user()->hasOperationalRole('CASHIER')
        && $sale->user_id !== auth()->id()
    ) {
        abort(403);
    }

    return view(

        'sales.receipt',

        compact('sale')

    );
}





















}
