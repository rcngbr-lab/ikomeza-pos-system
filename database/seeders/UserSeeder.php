<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('code', 'ADMIN')
            ->orWhere('name', 'ADMIN')
            ->orWhere('name', 'Administrator')
            ->first();

        if (!$adminRole) {
            $adminRole = Role::create([
                'name' => 'Administrator',
                'guard_name' => 'web',
                'code' => 'ADMIN',
                'slug' => 'administrator',
                'description' => 'Full system access',
                'is_system' => true,
                'active' => true,
            ]);
        }

        $adminRole->forceFill([
            'guard_name' => $adminRole->guard_name ?: 'web',
            'code' => $adminRole->code ?: 'ADMIN',
            'slug' => $adminRole->slug ?: Str::slug($adminRole->name),
            'active' => true,
        ])->save();

        $email = $this->envValue('ADMIN_EMAIL', 'admin@agnesbar.com');
        $password = $this->adminPassword();
        $name = $this->envValue('ADMIN_NAME', 'Administrator');

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if (!$user) {
            $user = new User();
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
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
