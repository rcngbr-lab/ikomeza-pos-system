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
    }
}
