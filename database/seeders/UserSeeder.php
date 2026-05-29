<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('code', 'ADMIN')
            ->orWhere('name', 'ADMIN')
            ->orWhere('name', 'Administrator')
            ->first();

        if (!$adminRole) {
            return;
        }

        $email = env('ADMIN_EMAIL', 'admin@agnesbar.com');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => 'ADMIN',
                'role_id' => $adminRole->id,
                'status' => 'ACTIVE',
                'active' => true,
            ]
        );

        $updates = [];

        if (!$user->role) {
            $updates['role'] = 'ADMIN';
        }

        if (!$user->role_id) {
            $updates['role_id'] = $adminRole->id;
        }

        if (!$user->status) {
            $updates['status'] = 'ACTIVE';
        }

        if (!$user->active) {
            $updates['active'] = true;
        }

        $adminPassword = env('ADMIN_PASSWORD');

        if (is_string($adminPassword) && trim($adminPassword) !== '') {
            $updates['password'] = Hash::make($adminPassword);
        }

        if (!empty($updates)) {
            $user->forceFill($updates)->save();
        }

        if (!$user->hasRole($adminRole->name)) {
            $user->syncRoles([$adminRole->name]);
        }
    }
}
