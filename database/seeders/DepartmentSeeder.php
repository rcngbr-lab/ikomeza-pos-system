<?php

namespace Database\Seeders;

use App\Services\DepartmentCatalogService;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        app(DepartmentCatalogService::class)->ensureDefaults();
    }
}
