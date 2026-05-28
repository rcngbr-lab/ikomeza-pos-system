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
        Schema::table('sales', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | BRANCH
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'branch_id')) {

                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id');
            }

            /*
            |--------------------------------------------------------------------------
            | PAYMENT TRACKING
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'amount_paid')) {

                $table->decimal(
                    'amount_paid',
                    12,
                    2
                )->default(0);
            }

            if (!Schema::hasColumn('sales', 'change_amount')) {

                $table->decimal(
                    'change_amount',
                    12,
                    2
                )->default(0);
            }

            if (!Schema::hasColumn('sales', 'payment_method')) {

                $table->string('payment_method')
                    ->default('CASH');
            }

            /*
            |--------------------------------------------------------------------------
            | APPROVALS
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('sales', 'approved_by')) {

                $table->foreignId('approved_by')
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

            $dropColumns = [];

            foreach ([

                'branch_id',

                'amount_paid',

                'change_amount',

                'payment_method',

                'approved_by'

            ] as $column) {

                if (Schema::hasColumn('sales', $column)) {

                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {

                $table->dropColumn($dropColumns);
            }

        });
    }
};