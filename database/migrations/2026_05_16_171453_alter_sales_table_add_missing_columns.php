<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | CUSTOMER
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'customer_name')) {

                $table->string('customer_name')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | PAYMENT
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'amount_paid')) {

                $table->decimal(
                    'amount_paid',
                    15,
                    2
                )->default(0);
            }

            if (!Schema::hasColumn('sales', 'change_amount')) {

                $table->decimal(
                    'change_amount',
                    15,
                    2
                )->default(0);
            }

            if (!Schema::hasColumn('sales', 'payment_method')) {

                $table->string('payment_method')
                    ->default('cash');
            }

            if (!Schema::hasColumn('sales', 'payment_status')) {

                $table->string('payment_status')
                    ->default('paid');
            }

            /*
            |--------------------------------------------------------------------------
            | SALE STATUS
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'sale_status')) {

                $table->string('sale_status')
                    ->default('completed');
            }

            /*
            |--------------------------------------------------------------------------
            | NOTES
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'notes')) {

                $table->text('notes')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | REFUNDS
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'is_refunded')) {

                $table->boolean('is_refunded')
                    ->default(false);
            }

            if (!Schema::hasColumn('sales', 'refund_amount')) {

                $table->decimal(
                    'refund_amount',
                    15,
                    2
                )->default(0);
            }

            if (!Schema::hasColumn('sales', 'refund_reason')) {

                $table->text('refund_reason')
                    ->nullable();
            }

            if (!Schema::hasColumn('sales', 'refunded_at')) {

                $table->timestamp('refunded_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('sales', 'refunded_by')) {

                $table->unsignedBigInteger('refunded_by')
                    ->nullable();
            }

            /*
            |--------------------------------------------------------------------------
            | APPROVAL
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'approved_by')) {

                $table->unsignedBigInteger('approved_by')
                    ->nullable();
            }

        });
    }

    /**
     * Reverse migrations.
     */

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $columns = [

                'customer_name',

                'amount_paid',

                'change_amount',

                'payment_method',

                'payment_status',

                'sale_status',

                'notes',

                'is_refunded',

                'refund_amount',

                'refund_reason',

                'refunded_at',

                'refunded_by',

                'approved_by',

            ];

            foreach ($columns as $column) {

                if (Schema::hasColumn('sales', $column)) {

                    $table->dropColumn($column);
                }
            }
        });
    }
};