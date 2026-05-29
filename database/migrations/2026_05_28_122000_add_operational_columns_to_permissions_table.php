<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'module')) {
                $table->string('module')->nullable()->after('guard_name');
            }

            if (!Schema::hasColumn('permissions', 'code')) {
                $table->string('code')->nullable()->after('module');
            }

            if (!Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('code');
            }

            if (!Schema::hasColumn('permissions', 'active')) {
                $table->boolean('active')->default(true)->after('description');
            }
        });

        DB::table('permissions')
            ->orderBy('id')
            ->get()
            ->each(function ($permission) {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update([
                        'code' => $permission->code ?: Str::upper(Str::slug((string) $permission->name, '_')),
                        'module' => $permission->module ?: 'SYSTEM',
                        'active' => $permission->active ?? true,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            foreach (['active', 'description', 'code', 'module'] as $column) {
                if (Schema::hasColumn('permissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
