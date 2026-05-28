<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            /*
            |--------------------------------------------------------------------------
            | PRODUCTS
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'PRODUCTS',
                'name' => 'View Products',
                'code' => 'VIEW_PRODUCTS',
            ],

            [
                'module' => 'PRODUCTS',
                'name' => 'Create Product',
                'code' => 'CREATE_PRODUCT',
            ],

            [
                'module' => 'PRODUCTS',
                'name' => 'Edit Product',
                'code' => 'EDIT_PRODUCT',
            ],

            [
                'module' => 'PRODUCTS',
                'name' => 'Delete Product',
                'code' => 'DELETE_PRODUCT',
            ],

            /*
            |--------------------------------------------------------------------------
            | CATEGORIES
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'CATEGORIES',
                'name' => 'View Categories',
                'code' => 'VIEW_CATEGORIES',
            ],

            [
                'module' => 'CATEGORIES',
                'name' => 'Create Category',
                'code' => 'CREATE_CATEGORY',
            ],

            /*
            |--------------------------------------------------------------------------
            | POS
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'POS',
                'name' => 'Access POS',
                'code' => 'ACCESS_POS',
            ],

            /*
            |--------------------------------------------------------------------------
            | SALES
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'SALES',
                'name' => 'View Sales',
                'code' => 'VIEW_SALES',
            ],

            /*
            |--------------------------------------------------------------------------
            | REPORTS
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'REPORTS',
                'name' => 'View Reports',
                'code' => 'VIEW_REPORTS',
            ],

            /*
            |--------------------------------------------------------------------------
            | USERS
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'USERS',
                'name' => 'Manage Users',
                'code' => 'MANAGE_USERS',
            ],

            /*
            |--------------------------------------------------------------------------
            | ROLES
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'ROLES',
                'name' => 'Manage Roles',
                'code' => 'MANAGE_ROLES',
            ],

            /*
            |--------------------------------------------------------------------------
            | AUDIT
            |--------------------------------------------------------------------------
            */

            [
                'module' => 'AUDIT',
                'name' => 'View Audit Logs',
                'code' => 'VIEW_AUDIT_LOGS',
            ],
        ];

        foreach ($permissions as $permission) {

            Permission::updateOrCreate(
                ['code' => $permission['code']],
                $permission
            );
        }
    }
}
