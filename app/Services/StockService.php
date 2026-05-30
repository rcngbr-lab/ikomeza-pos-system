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

            $beforeStock = (int) $product->stock;

            $product->decrement(

                'stock',

                $quantity

            );

            $product->refresh();

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
                ),
                $product->id,
                ['stock' => $beforeStock],
                ['stock' => $product->stock],
                'INFO',
                [
                    'module' => 'Inventory',
                    'department_id' => $product->department_id,
                    'reference' => $product->product_code ?? $product->barcode,
                    'quantity_before' => $beforeStock,
                    'quantity_changed' => -$quantity,
                    'quantity_after' => $product->stock,
                    'metadata' => [
                        'product' => $product->name,
                        'reason' => $reason,
                    ],
                ]

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

            $beforeStock = (int) $product->stock;

            $product->increment(

                'stock',

                $quantity

            );

            $product->refresh();

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
                ),
                $product->id,
                ['stock' => $beforeStock],
                ['stock' => $product->stock],
                'INFO',
                [
                    'module' => 'Inventory',
                    'department_id' => $product->department_id,
                    'reference' => $product->product_code ?? $product->barcode,
                    'quantity_before' => $beforeStock,
                    'quantity_changed' => $quantity,
                    'quantity_after' => $product->stock,
                    'metadata' => [
                        'product' => $product->name,
                        'reason' => $reason,
                    ],
                ]

            );
        });
    }
}
