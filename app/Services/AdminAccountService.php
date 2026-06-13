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
            $this->envValue('ADMIN_PASSWORD', ''),
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

        $isNewUser = !$user;

        if (!$user) {
            $user = new User();
        }

        $emailOwner = User::whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->when($user->exists, fn ($query) => $query->whereKeyNot($user->id))
            ->exists();

        $payload = [
            'name' => $name,
            'username' => $username,
            'email' => $emailOwner ? ($user->email ?: $username . '@ikomeza.local') : $email,
            'email_verified_at' => now(),
            'role' => 'ADMIN',
            'role_id' => $adminRole->id,
            'status' => 'ACTIVE',
            'active' => true,
        ];

        $shouldSetPassword = $isNewUser || filter_var(env('ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOL);

        if ($shouldSetPassword) {
            $payload['password'] = Hash::make($this->adminPassword($password));
        }

        $user->forceFill($payload)->save();

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

    private function adminPassword(string $password): string
    {
        $placeholderPasswords = [
            '',
            'password',
            'change-this-password',
            'changeme',
        ];

        if (in_array(strtolower($password), $placeholderPasswords, true)) {
            if (app()->environment('production')) {
                throw new \RuntimeException('ADMIN_PASSWORD must be set to a strong, private value before seeding production.');
            }

            return 'MyStrongPassword123';
        }

        return $password;
    }
}
