<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        /*
        |--------------------------------------------------------------------------
        | DISABLE FOREIGN KEYS
        |--------------------------------------------------------------------------
        */

        Schema::disableForeignKeyConstraints();

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        /*
        |--------------------------------------------------------------------------
        | RENAME OLD TABLE
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('sale_items_old')
            && Schema::hasTable('sale_items')
        ) {
            Schema::drop('sale_items_old');
        }

        if (
            !Schema::hasTable('sale_items_old')
            && Schema::hasTable('sale_items')
        ) {
            Schema::rename(
                'sale_items',
                'sale_items_old'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW TABLE
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | RELATIONSHIPS
                |--------------------------------------------------------------------------
                */

                $table->foreignId('sale_id')
                    ->constrained('sales')
                    ->cascadeOnDelete();

                $table->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | ITEM DATA
                |--------------------------------------------------------------------------
                */

                $table->integer('quantity')
                    ->default(1);

                $table->decimal(
                    'price',
                    15,
                    2
                )->default(0);

                $table->decimal(
                    'total',
                    15,
                    2
                )->default(0);

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | COPY OLD DATA
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('sale_items_old')) {
            $oldColumns = Schema::getColumnListing('sale_items_old');
            $priceColumn = in_array('price', $oldColumns, true)
                ? 'price'
                : (in_array('unit_price', $oldColumns, true) ? 'unit_price' : '0');
            $totalColumn = in_array('total', $oldColumns, true)
                ? 'total'
                : (in_array('subtotal', $oldColumns, true) ? 'subtotal' : '0');
            $quantityColumn = in_array('quantity', $oldColumns, true)
                ? 'quantity'
                : '1';
            $createdAtColumn = in_array('created_at', $oldColumns, true)
                ? 'created_at'
                : 'CURRENT_TIMESTAMP';
            $updatedAtColumn = in_array('updated_at', $oldColumns, true)
                ? 'updated_at'
                : 'CURRENT_TIMESTAMP';

            DB::statement("

                INSERT INTO sale_items (

                    id,
                    sale_id,
                    product_id,
                    quantity,
                    price,
                    total,
                    created_at,
                    updated_at

                )

                SELECT

                    id,
                    sale_id,
                    product_id,
                    {$quantityColumn},
                    {$priceColumn},
                    {$totalColumn},
                    {$createdAtColumn},
                    {$updatedAtColumn}

                FROM sale_items_old

            ");
        }

        /*
        |--------------------------------------------------------------------------
        | DROP OLD TABLE
        |--------------------------------------------------------------------------
        */

        Schema::dropIfExists(
            'sale_items_old'
        );

        /*
        |--------------------------------------------------------------------------
        | ENABLE FOREIGN KEYS
        |--------------------------------------------------------------------------
        */

        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys=ON');
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        //
    }
};
