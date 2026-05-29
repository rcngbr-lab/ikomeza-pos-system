<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [

        'name',

        'email',

        'password',

        'role',

        'role_id',

        'phone',

        'branch_id',

        'department_id',

        'status',

        'avatar',

        'active',

    ];

    protected $hidden = [

        'password',

        'remember_token',

    ];

    protected $casts = [

        'email_verified_at' => 'datetime',

        'password' => 'hashed',

        'active' => 'boolean',

        'last_login_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function branch()
    {
        return $this->belongsTo(
            Branch::class
        );
    }

    public function roleRecord()
    {
        return $this->belongsTo(
            Role::class,
            'role_id'
        );
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function sales()
    {
        return $this->hasMany(
            Sale::class
        );
    }

    public function shifts()
    {
        return $this->hasMany(
            Shift::class
        );
    }

    public function auditLogs()
    {
        return $this->hasMany(
            AuditLog::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function roleCode(): string
    {
        if (!empty($this->attributes['role'])) {
            return $this->normalizeOperationalRole(
                (string) $this->attributes['role']
            );
        }

        $role = $this->roleRecord ?: $this->roles->first();

        if (!$role) {
            return 'STAFF';
        }

        return $this->normalizeOperationalRole(
            (string) ($role->code ?? $role->name ?? 'STAFF')
        );
    }

    public function roleLabel(): string
    {
        $role = $this->roleRecord ?: $this->roles->first();

        if ($role && !empty($role->name)) {
            return (string) $role->name;
        }

        return str($this->roleCode())
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function hasOperationalRole(string ...$roles): bool
    {
        $allowed = collect($roles)
            ->flatten()
            ->map(fn ($role) => $this->normalizeOperationalRole((string) $role))
            ->all();

        return in_array(
            $this->roleCode(),
            $allowed,
            true
        );
    }

    public function isDepartmentBound(): bool
    {
        return $this->hasOperationalRole(
            'KITCHEN_MANAGER',
            'KITCHEN_CHIEF',
            'BAR_MANAGER',
            'BAR_CHIEF',
            'BARTENDER'
        );
    }

    public function canAccessDepartment(?int $departmentId): bool
    {
        if (!$departmentId) {
            return true;
        }

        if ($this->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER')) {
            return true;
        }

        if ($this->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            return true;
        }

        if (!$this->isDepartmentBound()) {
            return false;
        }

        if ($this->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF')) {
            return optional(Department::where('code', 'KITCHEN')->first())->id === (int) $departmentId;
        }

        if ($this->hasOperationalRole('BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            return optional(Department::where('code', 'BAR')->first())->id === (int) $departmentId;
        }

        return (int) $this->department_id === (int) $departmentId;
    }

    private function normalizeOperationalRole(string $role): string
    {
        $role = strtoupper(trim($role));

        return match (true) {
            str_contains($role, 'ADMIN'),
            str_contains($role, 'CEO') => 'ADMIN',
            str_contains($role, 'KITCHEN') && str_contains($role, 'MANAGER') => 'KITCHEN_MANAGER',
            str_contains($role, 'KITCHEN') && str_contains($role, 'CHIEF') => 'KITCHEN_MANAGER',
            str_contains($role, 'BAR') && str_contains($role, 'MANAGER') => 'BAR_MANAGER',
            str_contains($role, 'BAR') && str_contains($role, 'CHIEF') => 'BAR_MANAGER',
            str_contains($role, 'GENERAL') && str_contains($role, 'MANAGER') => 'MANAGER',
            $role === 'MANAGER' || str_contains($role, 'MANAGER') => 'MANAGER',
            str_contains($role, 'WAITER'),
            str_contains($role, 'SERVER') => 'WAITER',
            str_contains($role, 'CASHIER') => 'CASHIER',
            str_contains($role, 'BARTENDER') => 'BAR_MANAGER',
            default => $role ?: 'STAFF',
        };
    }





}
