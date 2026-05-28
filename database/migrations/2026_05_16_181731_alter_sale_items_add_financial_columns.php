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
        Schema::table('sale_items', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | UNIT PRICE
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'unit_price')) {

                $table->decimal(
                    'unit_price',
                    15,
                    2
                )->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | COST PRICE
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'cost_price')) {

                $table->decimal(
                    'cost_price',
                    15,
                    2
                )->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | DISCOUNT
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'discount')) {

                $table->decimal(
                    'discount',
                    15,
                    2
                )->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | TAX
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'tax')) {

                $table->decimal(
                    'tax',
                    15,
                    2
                )->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | TOTAL PRICE
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'subtotal')) {

                $table->decimal(
                    'subtotal',
                    15,
                    2
                )->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | PROFIT
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'profit')) {

                $table->decimal(
                    'profit',
                    15,
                    2
                )->default(0);
            }

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sale_items', 'status')) {

                $table->string('status')
                    ->default('ACTIVE');
            }

        });
    }

    /**
     * Reverse migrations.
     */

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {

            $columns = [

                'unit_price',

                'cost_price',

                'discount',

                'tax',

                'subtotal',

                'profit',

                'status',

            ];

            foreach ($columns as $column) {

                if (Schema::hasColumn('sale_items', $column)) {

                    $table->dropColumn($column);
                }
            }
        });
    }
};