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
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'code')) {
                $table->string('code')->nullable()->after('guard_name');
            }

            if (!Schema::hasColumn('roles', 'slug')) {
                $table->string('slug')->nullable()->after('code');
            }

            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }

            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('description');
            }

            if (!Schema::hasColumn('roles', 'active')) {
                $table->boolean('active')->default(true)->after('is_system');
            }
        });

        DB::table('roles')
            ->orderBy('id')
            ->get()
            ->each(function ($role) {
                $name = strtoupper((string) $role->name);
                $code = match (true) {
                    str_contains($name, 'ADMIN'),
                    str_contains($name, 'CEO') => 'ADMIN',
                    str_contains($name, 'MANAGER') => 'MANAGER',
                    str_contains($name, 'CASHIER') => 'CASHIER',
                    str_contains($name, 'BARTENDER') => 'BARTENDER',
                    str_contains($name, 'AUDITOR') => 'AUDITOR',
                    default => Str::upper(Str::slug((string) $role->name, '_')),
                };

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update([
                        'code' => $role->code ?: $code,
                        'slug' => $role->slug ?: Str::slug((string) $role->name),
                        'active' => $role->active ?? true,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            foreach (['active', 'is_system', 'description', 'slug', 'code'] as $column) {
                if (Schema::hasColumn('roles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
