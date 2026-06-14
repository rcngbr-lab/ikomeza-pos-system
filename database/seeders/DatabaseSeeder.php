<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && !filter_var(env('ALLOW_PRODUCTION_SEEDING', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('Production seeding blocked. Set ALLOW_PRODUCTION_SEEDING=true only during a planned, backed-up maintenance window.');

            return;
        }

        $this->call([

            RoleSeeder::class,

            PermissionSeeder::class,

            RolePermissionSeeder::class,

            DepartmentSeeder::class,

            CategorySeeder::class,

            UserSeeder::class,

            DemoEnvironmentSeeder::class,

            DemoUserSeeder::class,
        ]);
    }
}
