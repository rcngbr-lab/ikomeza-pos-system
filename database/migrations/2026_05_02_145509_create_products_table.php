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
        Schema::create('products', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | PRIMARY KEY
            |--------------------------------------------------------------------------
            */

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | PRODUCT IDENTIFIERS
            |--------------------------------------------------------------------------
            */

            $table->string('product_code')
                  ->unique();

            $table->string('barcode')
                  ->nullable()
                  ->unique();

            /*
            |--------------------------------------------------------------------------
            | BASIC INFORMATION
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->text('description')
                  ->nullable();

            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | PRODUCT TYPE
            |--------------------------------------------------------------------------
            */

            $table->enum('product_type', [

                'FINISHED_PRODUCT',
                'RAW_MATERIAL',
                'SERVICE'

            ])->default('FINISHED_PRODUCT');

            /*
            |--------------------------------------------------------------------------
            | PRICING
            |--------------------------------------------------------------------------
            */

            $table->decimal('buy_price', 15, 2)
                  ->default(0);

            $table->decimal('selling_price', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | INVENTORY
            |--------------------------------------------------------------------------
            */

            $table->boolean('track_stock')
                  ->default(true);

            $table->decimal('stock', 15, 2)
                  ->default(0);

            $table->decimal('alert_stock', 15, 2)
                  ->default(5);

            $table->string('unit')
                  ->default('pcs');

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $table->boolean('active')
                  ->default(true);

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
        Schema::dropIfExists('products');
    }
};