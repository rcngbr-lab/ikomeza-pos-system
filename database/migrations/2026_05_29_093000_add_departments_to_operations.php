<?php

use App\Services\DepartmentCatalogService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();

        DB::table('departments')->updateOrInsert(
            ['code' => DepartmentCatalogService::KITCHEN],
            [
                'name' => 'Kitchen',
                'description' => 'Food, meals, recipes, kitchen ingredients, and kitchen orders',
                'active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('departments')->updateOrInsert(
            ['code' => DepartmentCatalogService::BAR],
            [
                'name' => 'Bar',
                'description' => 'Beer, soft drinks, liquor, wine, cocktails, mixers, and bar stock',
                'active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        foreach (['categories', 'products', 'users', 'sale_items', 'stocks', 'stock_movements'] as $table) {
            $this->addDepartmentColumn($table);
        }

        $this->backfillDepartments();
    }

    public function down(): void
    {
        foreach (['stock_movements', 'stocks', 'sale_items', 'users', 'products', 'categories'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'department_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('department_id');
                });
            }
        }

        Schema::dropIfExists('departments');
    }

    private function addDepartmentColumn(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'department_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->index();
        });
    }

    private function backfillDepartments(): void
    {
        $kitchenId = DB::table('departments')
            ->where('code', DepartmentCatalogService::KITCHEN)
            ->value('id');
        $barId = DB::table('departments')
            ->where('code', DepartmentCatalogService::BAR)
            ->value('id');

        if (!$kitchenId || !$barId) {
            return;
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'department_id')) {
            $categories = DB::table('categories')->get(['id', 'code', 'name']);

            foreach ($categories as $category) {
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update([
                        'department_id' => $this->isKitchenCategory($category->code, $category->name)
                            ? $kitchenId
                            : $barId,
                    ]);
            }
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'department_id')) {
            $products = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'products.id',
                    'products.name',
                    'products.category_id',
                    'products.product_type',
                    'categories.department_id as category_department_id'
                )
                ->get();

            foreach ($products as $product) {
                $departmentId = $product->category_department_id
                    ?: ($this->isKitchenCategory(null, $product->name) ? $kitchenId : $barId);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['department_id' => $departmentId]);
            }
        }

        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'department_id')) {
            $items = DB::table('sale_items')
                ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                ->select('sale_items.id', 'products.department_id')
                ->get();

            foreach ($items as $item) {
                DB::table('sale_items')
                    ->where('id', $item->id)
                    ->update(['department_id' => $item->department_id ?: $barId]);
            }
        }

        foreach (['stocks', 'stock_movements'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'department_id')) {
                continue;
            }

            $records = DB::table($table)
                ->leftJoin('products', "{$table}.product_id", '=', 'products.id')
                ->select("{$table}.id", 'products.department_id')
                ->get();

            foreach ($records as $record) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update(['department_id' => $record->department_id ?: $barId]);
            }
        }
    }

    private function isKitchenCategory(?string $code, ?string $name): bool
    {
        $value = strtoupper(trim((string) ($code ?: $name)));

        return str_contains($value, 'FOOD')
            || str_contains($value, 'MEAL')
            || str_contains($value, 'KITCHEN')
            || str_contains($value, 'RECIPE')
            || str_contains($value, 'FRIES')
            || str_contains($value, 'CHICKEN');
    }
};
