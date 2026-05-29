<?php

namespace App\Services;

use App\Models\Department;

class DepartmentCatalogService
{
    public const KITCHEN = 'KITCHEN';
    public const BAR = 'BAR';

    public function ensureDefaults(): void
    {
        foreach ($this->defaults() as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'active' => true,
                    'sort_order' => $department['sort_order'],
                ]
            );
        }
    }

    public function defaults(): array
    {
        return [
            [
                'code' => self::KITCHEN,
                'name' => 'Kitchen',
                'description' => 'Food, meals, recipes, kitchen ingredients, and kitchen orders',
                'sort_order' => 10,
            ],
            [
                'code' => self::BAR,
                'name' => 'Bar',
                'description' => 'Beer, soft drinks, liquor, wine, cocktails, mixers, and bar stock',
                'sort_order' => 20,
            ],
        ];
    }

    public function departmentCodeForCategory(?string $code, ?string $name = null): string
    {
        $value = strtoupper(trim((string) ($code ?: $name)));

        return match (true) {
            str_contains($value, 'FOOD'),
            str_contains($value, 'MEAL'),
            str_contains($value, 'KITCHEN'),
            str_contains($value, 'RECIPE'),
            str_contains($value, 'FRIES'),
            str_contains($value, 'CHICKEN') => self::KITCHEN,
            default => self::BAR,
        };
    }

    public function idForCode(string $code): ?int
    {
        $this->ensureDefaults();

        return Department::where('code', strtoupper($code))->value('id');
    }
}
