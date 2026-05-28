<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal(
                'opening_cash',
                12,
                2
            )->default(0);

            $table->decimal(
                'closing_cash',
                12,
                2
            )->nullable();

            $table->decimal(
                'expected_cash',
                12,
                2
            )->default(0);

            $table->decimal(
                'cash_difference',
                12,
                2
            )->default(0);

            $table->timestamp('opened_at');

            $table->timestamp('closed_at')
                ->nullable();

            $table->enum(
                'status',
                [
                    'OPEN',
                    'CLOSED'
                ]
            )->default('OPEN');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};