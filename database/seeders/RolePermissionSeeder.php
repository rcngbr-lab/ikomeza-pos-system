<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::pluck('id');

        $this->rolesFor('ADMIN')->each(
            fn (Role $role) => $role->permissions()->sync($allPermissions)
        );

        $cashierPermissions = Permission::whereIn('code', [
            'ACCESS_POS',
            'VIEW_SALES',
        ])->pluck('id');

        $this->rolesFor('CASHIER')->each(
            fn (Role $role) => $role->permissions()->sync($cashierPermissions)
        );

        $managerPermissions = Permission::whereIn('code', [
            'VIEW_PRODUCTS',
            'CREATE_PRODUCT',
            'EDIT_PRODUCT',
            'VIEW_CATEGORIES',
            'CREATE_CATEGORY',
            'ACCESS_POS',
            'VIEW_SALES',
            'VIEW_REPORTS',
            'MANAGE_USERS',
        ])->pluck('id');

        $this->rolesFor('MANAGER')->each(
            fn (Role $role) => $role->permissions()->sync($managerPermissions)
        );
    }

    private function rolesFor(string $roleCode)
    {
        return Role::query()
            ->where(function ($query) use ($roleCode) {
                $query->whereRaw('UPPER(code) = ?', [$roleCode])
                    ->orWhereRaw('UPPER(name) = ?', [$roleCode]);

                if ($roleCode === 'ADMIN') {
                    $query->orWhereRaw('UPPER(name) LIKE ?', ['%ADMIN%'])
                        ->orWhereRaw('UPPER(name) LIKE ?', ['%CEO%']);
                }

                if ($roleCode === 'MANAGER') {
                    $query->orWhereRaw('UPPER(name) LIKE ?', ['%MANAGER%']);
                }

                if ($roleCode === 'CASHIER') {
                    $query->orWhereRaw('UPPER(name) LIKE ?', ['%CASHIER%']);
                }
            })
            ->get();
    }
}
