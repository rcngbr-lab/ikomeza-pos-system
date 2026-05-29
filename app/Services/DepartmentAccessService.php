<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;

class DepartmentAccessService
{
    public function allowedDepartmentIds(User $user)
    {
        if ($user->hasOperationalRole('ADMIN', 'ADMINISTRATOR', 'MANAGER', 'CASHIER', 'WAITER', 'SERVER')) {
            return null;
        }

        if ($user->hasOperationalRole('KITCHEN_MANAGER', 'KITCHEN_CHIEF')) {
            return array_filter([
                Department::where('code', DepartmentCatalogService::KITCHEN)->value('id') ?: $user->department_id,
            ]);
        }

        if ($user->hasOperationalRole('BAR_MANAGER', 'BAR_CHIEF', 'BARTENDER')) {
            return array_filter([
                Department::where('code', DepartmentCatalogService::BAR)->value('id') ?: $user->department_id,
            ]);
        }

        return [];
    }

    public function selectedDepartmentId(User $user, ?int $requestedDepartmentId = null): ?int
    {
        $allowed = $this->allowedDepartmentIds($user);

        if ($allowed === null) {
            return $requestedDepartmentId;
        }

        if (!$allowed) {
            abort(403);
        }

        if ($requestedDepartmentId && !in_array($requestedDepartmentId, $allowed, true)) {
            abort(403);
        }

        return $requestedDepartmentId ?: $allowed[0];
    }

    public function authorize(User $user, ?int $departmentId): void
    {
        $allowed = $this->allowedDepartmentIds($user);

        if ($allowed === null) {
            return;
        }

        if (!$departmentId || !in_array((int) $departmentId, array_map('intval', $allowed), true)) {
            abort(403);
        }
    }

    public function visibleDepartments(User $user)
    {
        $allowed = $this->allowedDepartmentIds($user);

        return Department::where('active', true)
            ->when($allowed !== null, fn ($query) => $query->whereIn('id', array_filter($allowed)))
            ->orderBy('sort_order')
            ->get();
    }
}
