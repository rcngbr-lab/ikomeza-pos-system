<?php

namespace App\Services;

use App\Models\Product;

use Illuminate\Support\Facades\DB;

class StockService
{
    /*
    |--------------------------------------------------------------------------
    | REDUCE STOCK
    |--------------------------------------------------------------------------
    */

    public function reduceStock(
        Product $product,
        int $quantity,
        ?string $reason = null
    ): void {

        DB::transaction(function () use (

            $product,
            $quantity,
            $reason

        ) {

            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if (

                $quantity <= 0

            ) {

                throw new \Exception(
                    'Quantity must be greater than zero.'
                );
            }

            if (

                $product->stock
                <
                $quantity

            ) {

                throw new \Exception(

                    'Insufficient stock for '
                    .
                    $product->name

                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE STOCK
            |--------------------------------------------------------------------------
            */

            $product->decrement(

                'stock',

                $quantity

            );

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            \App\Services\AuditService::log(

                'STOCK_OUT',

                'Product',

                'Reduced stock for '
                .
                $product->name
                .
                ' by '
                .
                $quantity
                .
                (
                    $reason
                    ? ' (' . $reason . ')'
                    : ''
                )

            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | INCREASE STOCK
    |--------------------------------------------------------------------------
    */

    public function increaseStock(
        Product $product,
        int $quantity,
        ?string $reason = null
    ): void {

        DB::transaction(function () use (

            $product,
            $quantity,
            $reason

        ) {

            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if (

                $quantity <= 0

            ) {

                throw new \Exception(
                    'Quantity must be greater than zero.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE STOCK
            |--------------------------------------------------------------------------
            */

            $product->increment(

                'stock',

                $quantity

            );

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            \App\Services\AuditService::log(

                'STOCK_IN',

                'Product',

                'Increased stock for '
                .
                $product->name
                .
                ' by '
                .
                $quantity
                .
                (
                    $reason
                    ? ' (' . $reason . ')'
                    : ''
                )

            );
        });
    }
}
