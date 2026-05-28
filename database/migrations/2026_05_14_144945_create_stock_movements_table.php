<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELATIONSHIPS
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id');

            $table->foreignId('branch_id')
                ->nullable();

            $table->foreignId('user_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | MOVEMENT
            |--------------------------------------------------------------------------
            */

            $table->string('type');

            /*
                SALE
                REFUND
                ADJUSTMENT
                DAMAGE
                TRANSFER
            */

            $table->integer('quantity');

            /*
            |--------------------------------------------------------------------------
            | STOCK SNAPSHOT
            |--------------------------------------------------------------------------
            */

            $table->integer('before_stock');

            $table->integer('after_stock');

            /*
            |--------------------------------------------------------------------------
            | REFERENCES
            |--------------------------------------------------------------------------
            */

            $table->string('reference_type')
                ->nullable();

            /*
                Sale
                Refund
                Adjustment
                Transfer
            */

            $table->unsignedBigInteger('reference_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | NOTES
            |--------------------------------------------------------------------------
            */

            $table->text('reason')
                ->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'stock_movements'
        );
    }
};