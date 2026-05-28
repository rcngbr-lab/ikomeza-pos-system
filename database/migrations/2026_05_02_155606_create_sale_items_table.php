<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations
     */
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | PRIMARY KEY
            |--------------------------------------------------------------------------
            */

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELATIONSHIPS
            |--------------------------------------------------------------------------
            */

            $table->foreignId('sale_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | PRODUCT QUANTITIES
            |--------------------------------------------------------------------------
            */

            $table->decimal('quantity', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | FINANCIALS
            |--------------------------------------------------------------------------
            */

            $table->decimal('unit_price', 15, 2);

            $table->decimal('cost_price', 15, 2)
                  ->default(0);

            $table->decimal('discount', 15, 2)
                  ->default(0);

            $table->decimal('tax', 15, 2)
                  ->default(0);

            $table->decimal('subtotal', 15, 2);

            $table->decimal('profit', 15, 2)
                  ->default(0);

            /*
            |--------------------------------------------------------------------------
            | ITEM STATUS
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'ACTIVE',
                'VOID'

            ])->default('ACTIVE');

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
        });
    }

    /**
     * Reverse migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};