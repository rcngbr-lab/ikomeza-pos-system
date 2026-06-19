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
            $this->envValue('ADMIN_EMAIL', 'admin@frontiershop.rw'),
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
            'email' => $emailOwner ? ($user->email ?: $username . '@frontier.local') : $email,
            'email_verified_at' => now(),
            'role' => 'ADMIN',
            'role_id' => $adminRole->id,
            'status' => 'ACTIVE',
            'active' => true,
        ];

        $hasConfiguredPassword = !$this->isPlaceholderPassword($password);
        $shouldSetPassword = $isNewUser
            || ($hasConfiguredPassword && app()->environment('production'))
            || filter_var(env('ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOL)
            || $this->shouldReplaceDefaultPassword($user, $password);

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

        if (is_string($value)) {
            $value = trim($value);

            if ($value !== '') {
                return $value;
            }
        }

        foreach ($this->environmentValues() as $candidate) {
            $parsedValue = $this->parseEmbeddedEnvValue($candidate, $key);

            if ($parsedValue !== null) {
                return $parsedValue;
            }
        }

        return $default;
    }

    /**
     * Railway's variable editor can accidentally receive multiple KEY=value
     * lines inside one variable. This keeps admin bootstrap recoverable without
     * exposing a password reset route.
     */
    private function parseEmbeddedEnvValue(mixed $candidate, string $key): ?string
    {
        if (!is_string($candidate) || !str_contains($candidate, $key . '=')) {
            return null;
        }

        foreach (preg_split('/\R/', $candidate) ?: [] as $line) {
            $line = trim($line);

            if (!str_starts_with($line, $key . '=')) {
                continue;
            }

            $value = trim(substr($line, strlen($key) + 1), " \t\n\r\0\x0B\"'");

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function environmentValues(): array
    {
        $values = array_merge($_ENV, $_SERVER);
        $getenvValues = getenv();

        if (is_array($getenvValues)) {
            $values = array_merge($values, $getenvValues);
        }

        return $values;
    }

    private function shouldReplaceDefaultPassword(User $user, string $password): bool
    {
        if (!$user->exists || $this->isPlaceholderPassword($password)) {
            return false;
        }

        foreach (['MyStrongPassword123', 'password', 'admin123'] as $defaultPassword) {
            if (Hash::check($defaultPassword, $user->password)) {
                return true;
            }
        }

        return false;
    }

    private function adminPassword(string $password): string
    {
        if ($this->isPlaceholderPassword($password)) {
            if (app()->environment('production')) {
                throw new \RuntimeException('ADMIN_PASSWORD must be set to a strong, private value before seeding production.');
            }

            return 'MyStrongPassword123';
        }

        return $password;
    }

    private function isPlaceholderPassword(string $password): bool
    {
        return in_array(strtolower(trim($password)), [
            '',
            'password',
            'change-this-password',
            'changeme',
        ], true);
    }
}
