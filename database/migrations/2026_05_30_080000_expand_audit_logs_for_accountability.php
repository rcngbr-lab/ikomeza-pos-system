<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $this->addString($table, 'role_name');
            $this->addUnsignedBigInteger($table, 'department_id');
            $this->addString($table, 'event_type');
            $this->addString($table, 'action');
            $this->addString($table, 'module');
            $this->addString($table, 'model_type');
            $this->addString($table, 'reference');
            $this->addLongText($table, 'metadata');
            $this->addDecimal($table, 'amount');
            $this->addDecimal($table, 'quantity_before');
            $this->addDecimal($table, 'quantity_changed');
            $this->addDecimal($table, 'quantity_after');
            $this->addString($table, 'device');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        $columns = [
            'role_name',
            'department_id',
            'event_type',
            'action',
            'module',
            'model_type',
            'reference',
            'metadata',
            'amount',
            'quantity_before',
            'quantity_changed',
            'quantity_after',
            'device',
        ];

        Schema::table('audit_logs', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addString(Blueprint $table, string $column): void
    {
        if (!Schema::hasColumn('audit_logs', $column)) {
            $table->string($column)->nullable()->index();
        }
    }

    private function addLongText(Blueprint $table, string $column): void
    {
        if (!Schema::hasColumn('audit_logs', $column)) {
            $table->longText($column)->nullable();
        }
    }

    private function addUnsignedBigInteger(Blueprint $table, string $column): void
    {
        if (!Schema::hasColumn('audit_logs', $column)) {
            $table->unsignedBigInteger($column)->nullable()->index();
        }
    }

    private function addDecimal(Blueprint $table, string $column): void
    {
        if (!Schema::hasColumn('audit_logs', $column)) {
            $table->decimal($column, 15, 2)->nullable();
        }
    }
};
