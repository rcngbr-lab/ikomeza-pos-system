<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;

class StockMovementService
{
    public static function record(

        Product $product,

        int $quantity,

        string $type,

        ?string $reason = null,

        ?string $referenceType = null,

        ?int $referenceId = null

    ): void {

        /*
        |--------------------------------------------------------------------------
        | CURRENT STOCK
        |--------------------------------------------------------------------------
        */

        $before =
            $product->stock;

        /*
        |--------------------------------------------------------------------------
        | STOCK CALCULATION
        |--------------------------------------------------------------------------
        */

        if (in_array($type, [

            'SALE',

            'DAMAGE',

            'TRANSFER'

        ])) {

            $after =
                $before - $quantity;

        } else {

            $after =
                $before + $quantity;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE PRODUCT STOCK
        |--------------------------------------------------------------------------
        */

        $product->update([

            'stock' => $after

        ]);

        /*
        |--------------------------------------------------------------------------
        | RECORD MOVEMENT
        |--------------------------------------------------------------------------
        */

        StockMovement::create([

            'product_id' => $product->id,

            'department_id' => $product->department_id,

            'branch_id' =>
                auth()->user()->branch_id
                ?? null,

            'user_id' =>
                auth()->id(),

            'type' => $type,

            'quantity' => $quantity,

            'before_stock' => $before,

            'after_stock' => $after,

            'reference_type' => $referenceType,

            'reference_id' => $referenceId,

            'reason' => $reason,

        ]);
    }
}
