<?php

namespace Database\Seeders;

use App\Services\DefaultRolePermissionService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(DefaultRolePermissionService::class)->ensurePermissions();
    }
}
