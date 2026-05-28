<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SaleItem;

class BartenderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PENDING ORDERS
    |--------------------------------------------------------------------------
    */

    public function orders()
    {
        $orders = SaleItem::with([
                'sale',
                'product'
            ])
            ->latest()
            ->get();

        return view(
            'bartender.orders',
            compact('orders')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PREPARE ORDER
    |--------------------------------------------------------------------------
    */

    public function prepare($id)
    {
        $item = SaleItem::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        $item->update([

            'status' => 'PREPARED'

        ]);

        /*
        |--------------------------------------------------------------------------
        | AUDIT LOG
        |--------------------------------------------------------------------------
        */

        \App\Services\AuditService::log(

            'UPDATE',

            'SaleItem',

            'Prepared drink/order #' . $item->id

        );

        return back()->with(

            'success',

            'Drink prepared successfully'

        );
    }
}