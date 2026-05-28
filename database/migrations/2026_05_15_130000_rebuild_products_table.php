<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DISABLE FOREIGN KEYS
        |--------------------------------------------------------------------------
        */

        DB::statement('PRAGMA foreign_keys=OFF');

        /*
        |--------------------------------------------------------------------------
        | RENAME OLD TABLE
        |--------------------------------------------------------------------------
        */

        Schema::rename(
            'sale_items',
            'sale_items_old'
        );

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW TABLE
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | COPY OLD DATA
        |--------------------------------------------------------------------------
        */

        DB::statement('

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
                quantity,
                price,
                total,
                created_at,
                updated_at

            FROM sale_items_old

        ');

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

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        //
    }
};