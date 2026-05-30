<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;

class DefaultRolePermissionService
{
    public function ensurePermissions(): Collection
    {
        return collect($this->permissions())
            ->mapWithKeys(function (array $permission) {
                $record = Permission::updateOrCreate(
                    ['code' => $permission['code']],
                    array_merge($permission, [
                        'guard_name' => 'web',
                        'active' => true,
                    ])
                );

                return [$permission['code'] => $record];
            });
    }

    public function ensureRolePermissions(): void
    {
        $permissions = $this->ensurePermissions();

        foreach ($this->rolePermissions() as $roleCode => $permissionCodes) {
            $permissionIds = $permissions
                ->only($permissionCodes)
                ->pluck('id')
                ->all();

            if (empty($permissionIds)) {
                continue;
            }

            $this->rolesFor($roleCode)->each(function (Role $role) use ($permissionIds) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            });
        }
    }

    public function permissions(): array
    {
        return [
            ['module' => 'DASHBOARD', 'name' => 'Access Dashboard', 'code' => 'ACCESS_DASHBOARD'],
            ['module' => 'POS', 'name' => 'Access POS', 'code' => 'ACCESS_POS'],
            ['module' => 'SALES', 'name' => 'View Sales', 'code' => 'VIEW_SALES'],
            ['module' => 'SALES', 'name' => 'Refund Sales', 'code' => 'REFUND_SALES'],
            ['module' => 'SHIFTS', 'name' => 'View Shifts', 'code' => 'VIEW_SHIFTS'],
            ['module' => 'SHIFTS', 'name' => 'Manage Own Shift', 'code' => 'MANAGE_OWN_SHIFT'],
            ['module' => 'REPORTS', 'name' => 'View Reports', 'code' => 'VIEW_REPORTS'],
            ['module' => 'PRODUCTS', 'name' => 'View Products', 'code' => 'VIEW_PRODUCTS'],
            ['module' => 'PRODUCTS', 'name' => 'Create Product', 'code' => 'CREATE_PRODUCT'],
            ['module' => 'PRODUCTS', 'name' => 'Edit Product', 'code' => 'EDIT_PRODUCT'],
            ['module' => 'PRODUCTS', 'name' => 'Delete Product', 'code' => 'DELETE_PRODUCT'],
            ['module' => 'CATEGORIES', 'name' => 'View Categories', 'code' => 'VIEW_CATEGORIES'],
            ['module' => 'CATEGORIES', 'name' => 'Create Category', 'code' => 'CREATE_CATEGORY'],
            ['module' => 'INVENTORY', 'name' => 'View Inventory', 'code' => 'VIEW_INVENTORY'],
            ['module' => 'INVENTORY', 'name' => 'Manage Inventory', 'code' => 'MANAGE_INVENTORY'],
            ['module' => 'STORE', 'name' => 'View Store Control', 'code' => 'VIEW_STORE'],
            ['module' => 'STORE', 'name' => 'Manage Store Control', 'code' => 'MANAGE_STORE'],
            ['module' => 'REQUISITIONS', 'name' => 'View Requisitions', 'code' => 'VIEW_REQUISITIONS'],
            ['module' => 'REQUISITIONS', 'name' => 'Create Requisition', 'code' => 'CREATE_REQUISITION'],
            ['module' => 'REQUISITIONS', 'name' => 'Approve Requisition', 'code' => 'APPROVE_REQUISITION'],
            ['module' => 'USERS', 'name' => 'View Users', 'code' => 'VIEW_USERS'],
            ['module' => 'USERS', 'name' => 'Manage Users', 'code' => 'MANAGE_USERS'],
            ['module' => 'ROLES', 'name' => 'Manage Roles', 'code' => 'MANAGE_ROLES'],
            ['module' => 'PERMISSIONS', 'name' => 'Manage Permissions', 'code' => 'MANAGE_PERMISSIONS'],
            ['module' => 'AUDIT', 'name' => 'View Audit Logs', 'code' => 'VIEW_AUDIT_LOGS'],
        ];
    }

    private function rolePermissions(): array
    {
        $salesStaff = [
            'ACCESS_DASHBOARD',
            'ACCESS_POS',
            'VIEW_SALES',
            'VIEW_SHIFTS',
            'MANAGE_OWN_SHIFT',
        ];

        $departmentOps = [
            'ACCESS_DASHBOARD',
            'VIEW_SALES',
            'VIEW_REPORTS',
            'VIEW_PRODUCTS',
            'VIEW_CATEGORIES',
            'VIEW_INVENTORY',
            'VIEW_STORE',
            'VIEW_REQUISITIONS',
            'CREATE_REQUISITION',
        ];

        $managerOps = array_merge($departmentOps, [
            'ACCESS_POS',
            'REFUND_SALES',
            'VIEW_SHIFTS',
            'MANAGE_INVENTORY',
            'MANAGE_STORE',
            'APPROVE_REQUISITION',
            'VIEW_USERS',
            'MANAGE_USERS',
            'VIEW_AUDIT_LOGS',
        ]);

        return [
            'ADMIN' => collect($this->permissions())->pluck('code')->all(),
            'MANAGER' => $managerOps,
            'CASHIER' => $salesStaff,
            'WAITER' => $salesStaff,
            'SERVER' => $salesStaff,
            'BARTENDER' => array_merge($salesStaff, [
                'VIEW_INVENTORY',
                'VIEW_STORE',
                'VIEW_REQUISITIONS',
                'CREATE_REQUISITION',
            ]),
            'KITCHEN_MANAGER' => $departmentOps,
            'KITCHEN_CHIEF' => $departmentOps,
            'BAR_MANAGER' => $departmentOps,
            'BAR_CHIEF' => $departmentOps,
            'STORE_KEEPER' => [
                'ACCESS_DASHBOARD',
                'VIEW_INVENTORY',
                'MANAGE_INVENTORY',
                'VIEW_STORE',
                'MANAGE_STORE',
                'VIEW_REQUISITIONS',
                'CREATE_REQUISITION',
                'VIEW_REPORTS',
                'VIEW_AUDIT_LOGS',
            ],
        ];
    }

    private function rolesFor(string $roleCode): Collection
    {
        return Role::query()
            ->where(function ($query) use ($roleCode) {
                $query->whereRaw('UPPER(code) = ?', [$roleCode])
                    ->orWhereRaw('UPPER(name) = ?', [$roleCode]);

                if ($roleCode === 'ADMIN') {
                    $query->orWhereRaw('UPPER(name) LIKE ?', ['%ADMIN%'])
                        ->orWhereRaw('UPPER(name) LIKE ?', ['%CEO%']);
                }
            })
            ->get();
    }
}
