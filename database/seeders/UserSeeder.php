<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where(
            'code',
            'ADMIN'
        )->first();

        User::create([

            'name' => 'Administrator',

            'email' => 'admin@agnesbar.com',

            'password' => 'password',

            'role_id' => $adminRole->id,

            'active' => true,
        ]);
    }
}