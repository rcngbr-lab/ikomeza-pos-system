<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {

            $table->decimal(
                'cash_sales',
                15,
                2
            )->default(0);

            $table->decimal(
                'momo_sales',
                15,
                2
            )->default(0);

            $table->decimal(
                'airtel_sales',
                15,
                2
            )->default(0);

            $table->decimal(
                'visa_sales',
                15,
                2
            )->default(0);

            $table->decimal(
                'mastercard_sales',
                15,
                2
            )->default(0);

            $table->decimal(
                'bank_transfer_sales',
                15,
                2
            )->default(0);

        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {

            $table->dropColumn([

                'cash_sales',
                'momo_sales',
                'airtel_sales',
                'visa_sales',
                'mastercard_sales',
                'bank_transfer_sales',

            ]);

        });
    }
};