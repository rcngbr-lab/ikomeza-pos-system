<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Str;

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

            'username' => $data['username'],

            'email' => $this->contactEmail($data),

            'phone' => $data['phone'] ?? null,

            'password' => bcrypt(
                $data['password']
            ),

            'branch_id' => $data['branch_id'],

            'department_id' => $data['department_id'] ?? null,

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

            'username' => $data['username'],

            'email' => $this->contactEmail($data, $user),

            'phone' => $data['phone'] ?? null,

            'branch_id' => $data['branch_id'],

            'department_id' => $data['department_id'] ?? null,

            'status' => $data['status'],

            'role' => $role->code ?? strtoupper($data['role']),

            'role_id' => $role?->id,

        ]);

        $user->syncRoles([

            $role?->name ?? $data['role']

        ]);

        return $user;
    }

    private function contactEmail(array $data, ?User $user = null): string
    {
        $email = trim((string) ($data['email'] ?? ''));

        if ($email !== '') {
            return Str::lower($email);
        }

        if (
            $user
            && $user->email
            && !str_ends_with(Str::lower($user->email), '@ikomeza.local')
        ) {
            return $user->email;
        }

        return $data['username'] . '@ikomeza.local';
    }
}
