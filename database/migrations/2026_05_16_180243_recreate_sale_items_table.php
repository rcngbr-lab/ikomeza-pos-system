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
        | DISABLE FK
        |--------------------------------------------------------------------------
        */

        DB::statement('PRAGMA foreign_keys=OFF');

        /*
        |--------------------------------------------------------------------------
        | DROP BROKEN TABLE
        |--------------------------------------------------------------------------
        */

        Schema::dropIfExists('sale_items');

        /*
        |--------------------------------------------------------------------------
        | CREATE CLEAN TABLE
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
        | ENABLE FK
        |--------------------------------------------------------------------------
        */

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};