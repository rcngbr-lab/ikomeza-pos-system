<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;

class UserService
{
    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    public function create(array $data): User
    {
        $role = Role::where('name', $data['role'])
            ->orWhere('code', strtoupper($data['role']))
            ->first();

        $user = User::create([

            'name' => $data['name'],

            'email' => $data['email'],

            'phone' => $data['phone'] ?? null,

            'password' => bcrypt(
                $data['password']
            ),

            'branch_id' => $data['branch_id'],

            'status' => $data['status'],

            'role' => $role->code ?? strtoupper($data['role']),

            'role_id' => $role?->id,

        ]);

        $user->assignRole($role?->name ?? $data['role']);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    public function update(
        User $user,
        array $data
    ): User {

        $role = Role::where('name', $data['role'])
            ->orWhere('code', strtoupper($data['role']))
            ->first();

        $user->update([

            'name' => $data['name'],

            'email' => $data['email'],

            'phone' => $data['phone'] ?? null,

            'branch_id' => $data['branch_id'],

            'status' => $data['status'],

            'role' => $role->code ?? strtoupper($data['role']),

            'role_id' => $role?->id,

        ]);

        $user->syncRoles([

            $role?->name ?? $data['role']

        ]);

        return $user;
    }
}
