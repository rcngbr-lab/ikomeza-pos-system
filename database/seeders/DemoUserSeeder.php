<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'MyStrongPassword123';

        foreach ($this->users() as $demoUser) {
            $role = Role::where('code', $demoUser['role'])
                ->orWhere('name', $demoUser['role'])
                ->first();

            if (!$role) {
                continue;
            }

            $role->forceFill([
                'guard_name' => $role->guard_name ?: 'web',
            ])->save();

            $user = User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'role' => $demoUser['role'],
                    'role_id' => $role->id,
                    'department_id' => null,
                    'status' => 'ACTIVE',
                    'active' => true,
                ]
            );

            $user->syncRoles([$role->name]);
        }
    }

    private function users(): array
    {
        return [
            [
                'name' => 'Demo Manager',
                'email' => 'manager@agnesbar.com',
                'role' => 'MANAGER',
            ],
            [
                'name' => 'Demo Cashier',
                'email' => 'cashier@agnesbar.com',
                'role' => 'CASHIER',
            ],
            [
                'name' => 'Demo Waiter',
                'email' => 'waiter@agnesbar.com',
                'role' => 'WAITER',
            ],
        ];
    }
}
