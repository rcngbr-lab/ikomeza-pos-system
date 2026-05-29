<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Department;

class CategoryCatalogService
{
    public function ensureDefaults(): void
    {
        app(DepartmentCatalogService::class)->ensureDefaults();

        foreach ($this->defaults() as $category) {
            $model = Category::where('code', $category['code'])
                ->orWhere('name', $category['name'])
                ->first();

            if (!$model) {
                $model = new Category();
            }

            $model->forceFill([
                'code' => $category['code'],
                'name' => $category['name'],
                'description' => $model->description ?: $category['name'] . ' products',
                'department_id' => Department::where('code', $category['department'])->value('id'),
                'sort_order' => $category['sort_order'],
                'active' => true,
            ])->save();
        }
    }

    public function defaults(): array
    {
        return [
            ['code' => 'BEER', 'name' => 'Beer', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 10],
            ['code' => 'LIQUEUR', 'name' => 'Liqueur', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 20],
            ['code' => 'WINE', 'name' => 'Wine', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 30],
            ['code' => 'SOFT_DRINKS', 'name' => 'Soft Drinks', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 40],
            ['code' => 'COCKTAILS', 'name' => 'Cocktails', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 45],
            ['code' => 'FOOD', 'name' => 'Food', 'department' => DepartmentCatalogService::KITCHEN, 'sort_order' => 50],
            ['code' => 'MEALS', 'name' => 'Meals', 'department' => DepartmentCatalogService::KITCHEN, 'sort_order' => 55],
            ['code' => 'RECIPE_INGREDIENTS', 'name' => 'Recipe Ingredients', 'department' => DepartmentCatalogService::KITCHEN, 'sort_order' => 58],
            ['code' => 'RETAIL', 'name' => 'Retail', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 60],
            ['code' => 'SUPPLIES', 'name' => 'Supplies', 'department' => DepartmentCatalogService::BAR, 'sort_order' => 70],
            ['code' => 'SERVICE', 'name' => 'Service', 'department' => DepartmentCatalogService::KITCHEN, 'sort_order' => 80],
        ];
    }
}
