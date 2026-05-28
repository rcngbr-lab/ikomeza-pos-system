<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\StockMovement;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with([

            'product',
            'user',

        ]);

        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->whereHas(
                    'product',
                    function ($product) use ($request) {

                        $product->where(
                            'name',
                            'like',
                            '%' . $request->search . '%'
                        );
                    }
                )

                ->orWhere(
                    'reference',
                    'like',
                    '%' . $request->search . '%'
                );

            });
        }

        if ($request->filter == 'today') {

            $query->whereDate(
                'created_at',
                today()
            );
        }

        elseif ($request->filter == 'weekly') {

            $query->whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]
            );
        }

        elseif ($request->filter == 'monthly') {

            $query->whereMonth(
                'created_at',
                now()->month
            )

            ->whereYear(
                'created_at',
                now()->year
            );
        }

        elseif ($request->filter == 'yearly') {

            $query->whereYear(
                'created_at',
                now()->year
            );
        }

        if ($request->start_date) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->start_date
            );
        }

        if ($request->end_date) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->end_date
            );
        }

        $logs = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view(
            'stock_logs.index',
            compact('logs')
        );
    }
}