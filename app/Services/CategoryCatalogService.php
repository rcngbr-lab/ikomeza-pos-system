<?php

namespace App\Services;

use App\Models\Category;

class CategoryCatalogService
{
    public function ensureDefaults(): void
    {
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
                'sort_order' => $category['sort_order'],
                'active' => true,
            ])->save();
        }
    }

    public function defaults(): array
    {
        return [
            ['code' => 'BEER', 'name' => 'Beer', 'sort_order' => 10],
            ['code' => 'LIQUEUR', 'name' => 'Liqueur', 'sort_order' => 20],
            ['code' => 'WINE', 'name' => 'Wine', 'sort_order' => 30],
            ['code' => 'SOFT_DRINKS', 'name' => 'Soft Drinks', 'sort_order' => 40],
            ['code' => 'FOOD', 'name' => 'Food', 'sort_order' => 50],
            ['code' => 'RETAIL', 'name' => 'Retail', 'sort_order' => 60],
            ['code' => 'SUPPLIES', 'name' => 'Supplies', 'sort_order' => 70],
            ['code' => 'SERVICE', 'name' => 'Service', 'sort_order' => 80],
        ];
    }
}
