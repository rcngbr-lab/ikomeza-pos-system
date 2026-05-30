<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAccountService
{
    public function ensure(): void
    {
        $adminRole = $this->adminRole();

        $this->ensureUser(
            $this->envValue('ADMIN_USERNAME', 'admin'),
            $this->envValue('ADMIN_EMAIL', 'admin@agnesbar.com'),
            $this->envValue('ADMIN_NAME', 'Administrator'),
            $this->adminPassword(),
            $adminRole
        );

    }

    private function adminRole(): Role
    {
        $adminRole = Role::where('code', 'ADMIN')
            ->orWhere('name', 'ADMIN')
            ->orWhere('name', 'Administrator')
            ->first();

        if (!$adminRole) {
            $adminRole = Role::firstOrCreate(
                [
                    'name' => 'Administrator',
                    'guard_name' => 'web',
                ],
                [
                    'code' => 'ADMIN',
                    'slug' => 'administrator',
                    'description' => 'Full system access',
                    'is_system' => true,
                    'active' => true,
                ]
            );
        }

        $adminRole->forceFill([
            'guard_name' => $adminRole->guard_name ?: 'web',
            'code' => $adminRole->code ?: 'ADMIN',
            'slug' => $adminRole->slug ?: Str::slug($adminRole->name),
            'active' => true,
        ])->save();

        return $adminRole;
    }

    private function ensureUser(string $username, string $email, string $name, string $password, Role $adminRole): void
    {
        $username = Str::lower(trim($username));

        $user = User::whereRaw('LOWER(username) = ?', [$username])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if (!$user) {
            $user = new User();
        }

        $emailOwner = User::whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->when($user->exists, fn ($query) => $query->whereKeyNot($user->id))
            ->exists();

        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'email' => $emailOwner ? ($user->email ?: $username . '@ikomeza.local') : $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'role' => 'ADMIN',
            'role_id' => $adminRole->id,
            'status' => 'ACTIVE',
            'active' => true,
        ])->save();

        if (!$user->hasRole($adminRole->name)) {
            $user->syncRoles([$adminRole->name]);
        }
    }

    private function envValue(string $key, string $default): string
    {
        $value = env($key);

        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value !== '' ? $value : $default;
    }

    private function adminPassword(): string
    {
        $password = $this->envValue('ADMIN_PASSWORD', 'MyStrongPassword123');
        $placeholderPasswords = [
            'password',
            'change-this-password',
            'changeme',
        ];

        return in_array(strtolower($password), $placeholderPasswords, true)
            ? 'MyStrongPassword123'
            : $password;
    }
}
