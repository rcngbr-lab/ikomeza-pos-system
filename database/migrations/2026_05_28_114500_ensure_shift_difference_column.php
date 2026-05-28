<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shifts', 'difference')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->decimal('difference', 15, 2)
                    ->default(0)
                    ->after('expected_cash');
            });

            if (Schema::hasColumn('shifts', 'cash_difference')) {
                DB::table('shifts')->update([
                    'difference' => DB::raw('cash_difference'),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn('shifts', 'difference')
            && !Schema::hasColumn('shifts', 'cash_difference')
        ) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->dropColumn('difference');
            });
        }
    }
};
