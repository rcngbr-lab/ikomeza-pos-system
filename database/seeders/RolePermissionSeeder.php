<?php

namespace Database\Seeders;

use App\Services\DefaultRolePermissionService;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(DefaultRolePermissionService::class)->ensureRolePermissions();
    }
}
