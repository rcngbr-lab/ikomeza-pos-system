<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('refunds')) {
            return;
        }

        Schema::table('refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('refunds', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('refunds') || !Schema::hasColumn('refunds', 'refunded_at')) {
            return;
        }

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });
    }
};
