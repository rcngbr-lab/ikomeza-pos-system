<?php

namespace Database\Seeders;

use App\Services\CategoryCatalogService;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        app(CategoryCatalogService::class)->ensureDefaults();
    }
}
