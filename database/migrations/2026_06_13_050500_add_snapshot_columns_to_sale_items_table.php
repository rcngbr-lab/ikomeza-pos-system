<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }

            if (!Schema::hasColumn('sale_items', 'product_code')) {
                $table->string('product_code')->nullable()->after('product_name');
            }

            if (!Schema::hasColumn('sale_items', 'department_name')) {
                $table->string('department_name')->nullable()->after('department_id');
            }
        });

        if (Schema::hasTable('products')) {
            DB::table('sale_items')
                ->whereNull('product_name')
                ->orderBy('id')
                ->chunkById(200, function ($items) {
                    $products = DB::table('products')
                        ->whereIn('id', $items->pluck('product_id')->filter()->unique()->all())
                        ->get()
                        ->keyBy('id');

                    $departments = Schema::hasTable('departments')
                        ? DB::table('departments')
                            ->whereIn('id', $items->pluck('department_id')->filter()->unique()->all())
                            ->get()
                            ->keyBy('id')
                        : collect();

                    foreach ($items as $item) {
                        $product = $products->get($item->product_id);
                        $department = $departments->get($item->department_id);

                        DB::table('sale_items')
                            ->where('id', $item->id)
                            ->update([
                                'product_name' => $product->name ?? null,
                                'product_code' => $product->product_code ?? null,
                                'department_name' => $department->name ?? null,
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            foreach (['department_name', 'product_code', 'product_name'] as $column) {
                if (Schema::hasColumn('sale_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
