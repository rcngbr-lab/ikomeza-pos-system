<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $table->boolean('is_refunded')
                ->default(false);

            $table->decimal(
                'refund_amount',
                12,
                2
            )->default(0);

            $table->text('refund_reason')
                ->nullable();

            $table->timestamp('refunded_at')
                ->nullable();

            $table->unsignedBigInteger('refunded_by')
                ->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $table->dropColumn([

                'is_refunded',
                'refund_amount',
                'refund_reason',
                'refunded_at',
                'refunded_by'

            ]);

        });
    }
};