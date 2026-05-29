<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELATIONS
            |--------------------------------------------------------------------------
            */

            $table->foreignId('sale_id')

                ->constrained()

                ->cascadeOnDelete();

            $table->foreignId('user_id')

                ->nullable()

                ->constrained();

            /*
            |--------------------------------------------------------------------------
            | REFUND
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'amount',
                15,
                2
            );

            $table->text(
                'reason'
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $table->enum(
                'status',
                [
                    'PENDING',
                    'APPROVED',
                    'REJECTED',
                    'COMPLETED'
                ]
            )->default('COMPLETED');

            $table->timestamp('refunded_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'refunds'
        );
    }
};
