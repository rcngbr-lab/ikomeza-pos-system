<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;

class StockLogController extends Controller
{
    public function index()
    {
        $logs = StockMovement::with([

            'product',
            'user'

        ])

        ->latest()

        ->paginate(50);

        return view(

            'stock.logs',

            compact('logs')

        );
    }
}