<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoAccountService
{
    public const PASSWORD = 'MyStrongPassword123';

    public function ensure(): void
    {
        if (
            !Schema::hasTable('roles')
            || !Schema::hasTable('users')
            || !Schema::hasTable('branches')
        ) {
            return;
        }

        $roles = $this->ensureRoles();
        $branch = $this->ensureBranch();

        foreach ($this->demoUsers() as $demoUser) {
            if (!isset($roles[$demoUser['role']])) {
                continue;
            }

            $role = $roles[$demoUser['role']];

            $user = User::updateOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                    'role' => $role->code ?? $demoUser['role'],
                    'role_id' => $role->id,
                    'branch_id' => $branch->id,
                    'department_id' => null,
                    'status' => 'ACTIVE',
                    'active' => true,
                ]
            );

            $user->syncRoles([$role->name]);
        }
    }

    private function ensureRoles(): array
    {
        $roles = [];

        foreach ($this->roles() as $roleData) {
            $role = Role::updateOrCreate(
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'code' => $roleData['code'],
                    'slug' => Str::slug($roleData['name']),
                    'description' => $roleData['description'],
                    'is_system' => true,
                    'active' => true,
                ]
            );

            $roles[$roleData['code']] = $role;
        }

        return $roles;
    }

    private function ensureBranch(): Branch
    {
        return Branch::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'location' => 'Head Office',
                'status' => 'ACTIVE',
                'currency' => 'RWF',
                'country' => 'Rwanda',
            ]
        );
    }

    private function roles(): array
    {
        return [
            ['code' => 'ADMIN', 'name' => 'Administrator', 'description' => 'Full system access'],
            ['code' => 'MANAGER', 'name' => 'Manager', 'description' => 'Operations and reporting access'],
            ['code' => 'CASHIER', 'name' => 'Cashier', 'description' => 'POS cashier'],
            ['code' => 'WAITER', 'name' => 'Waiter', 'description' => 'Unified POS ordering and receipt access'],
            ['code' => 'SERVER', 'name' => 'Server', 'description' => 'Restaurant and bar table service POS access'],
            ['code' => 'KITCHEN_MANAGER', 'name' => 'Kitchen Manager', 'description' => 'Kitchen sales, orders, stock, and reports'],
            ['code' => 'BAR_MANAGER', 'name' => 'Bar Manager', 'description' => 'Bar sales, stock, and reports'],
            ['code' => 'KITCHEN_CHIEF', 'name' => 'Kitchen Chief', 'description' => 'Kitchen department chief operations'],
            ['code' => 'BAR_CHIEF', 'name' => 'Bar Chief', 'description' => 'Bar department chief operations'],
            ['code' => 'BARTENDER', 'name' => 'Bartender', 'description' => 'Bar operations'],
        ];
    }

    private function demoUsers(): array
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
