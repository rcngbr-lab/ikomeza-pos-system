<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate([
            'code' => 'ADMIN',
        ], [
            'name' => 'Administrator',

            'slug' => 'administrator',

            'description' => 'Full system access',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'MANAGER',
        ], [

            'name' => 'Manager',

            'slug' => 'manager',

            'description' => 'Operations and reporting access',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'CASHIER',
        ], [

            'name' => 'Cashier',

            'slug' => 'cashier',

            'description' => 'POS cashier',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'BARTENDER',
        ], [

            'name' => 'Bartender',

            'slug' => 'bartender',

            'description' => 'Bar operations',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'WAITER',
        ], [

            'name' => 'Waiter',

            'slug' => 'waiter',

            'description' => 'Unified POS ordering and receipt access',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'SERVER',
        ], [

            'name' => 'Server',

            'slug' => 'server',

            'description' => 'Restaurant and bar table service POS access',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'KITCHEN_MANAGER',
        ], [

            'name' => 'Kitchen Manager',

            'slug' => 'kitchen-manager',

            'description' => 'Kitchen sales, orders, stock, and reports',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'BAR_MANAGER',
        ], [

            'name' => 'Bar Manager',

            'slug' => 'bar-manager',

            'description' => 'Bar sales, stock, and reports',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'KITCHEN_CHIEF',
        ], [

            'name' => 'Kitchen Chief',

            'slug' => 'kitchen-chief',

            'description' => 'Kitchen department chief operations',

            'is_system' => true,

            'active' => true,
        ]);

        Role::updateOrCreate([
            'code' => 'BAR_CHIEF',
        ], [

            'name' => 'Bar Chief',

            'slug' => 'bar-chief',

            'description' => 'Bar department chief operations',

            'is_system' => true,

            'active' => true,
        ]);
    }
}
