<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\DepartmentCatalogService;
use App\Services\UserService;

class UserController extends Controller
{
    public function index()
    {
        $usersQuery = User::with([
            'branch',
            'department',
            'roles',
        ])->latest();

        if ($this->managerIsLimited()) {
            $this->limitUsersForManager($usersQuery);
        }

        $users = $usersQuery->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        app(DepartmentCatalogService::class)->ensureDefaults();

        return view('users.create', [
            'branches' => $this->availableBranches(),
            'departments' => $this->availableDepartments(),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function store(
        StoreUserRequest $request,
        UserService $userService
    ) {
        $this->authorizeRequestedRole($request->validated('role'));

        $user = $userService->create($request->validated());

        AuditLogService::record([
            'action' => 'USER_CREATED',
            'module' => 'Users',
            'model' => $user,
            'branch_id' => $user->branch_id,
            'department_id' => $user->department_id,
            'reference' => $user->username,
            'description' => 'Created user account ' . $user->username . ' with role ' . $user->roleLabel() . '.',
            'new_values' => $user->only(['name', 'username', 'email', 'phone', 'role', 'role_id', 'branch_id', 'department_id', 'status']),
            'severity' => 'SECURITY',
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorizeManageableUser($user);

        return view('users.edit', [
            'user' => $user,
            'branches' => $this->availableBranches(),
            'departments' => $this->availableDepartments(),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UserService $userService
    ) {
        $this->authorizeManageableUser($user);
        $this->authorizeRequestedRole($request->validated('role'));

        $oldValues = $user->only(['name', 'username', 'email', 'phone', 'role', 'role_id', 'branch_id', 'department_id', 'status']);

        $user = $userService->update($user, $request->validated());

        AuditLogService::record([
            'action' => (
                ($oldValues['role'] ?? null) !== $user->role
                || (int) ($oldValues['role_id'] ?? 0) !== (int) $user->role_id
            ) ? 'USER_ROLE_CHANGED' : 'USER_UPDATED',
            'module' => 'Users',
            'model' => $user,
            'branch_id' => $user->branch_id,
            'department_id' => $user->department_id,
            'reference' => $user->username,
            'description' => 'Updated user account ' . $user->username . '.',
            'old_values' => $oldValues,
            'new_values' => $user->only(array_keys($oldValues)),
            'severity' => (
                ($oldValues['role'] ?? null) !== $user->role
                || (int) ($oldValues['role_id'] ?? 0) !== (int) $user->role_id
                || ($oldValues['status'] ?? null) !== $user->status
            ) ? 'SECURITY' : 'INFO',
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    private function managerIsLimited(): bool
    {
        return auth()->user()->hasOperationalRole('MANAGER')
            && !auth()->user()->hasOperationalRole('ADMIN', 'ADMINISTRATOR');
    }

    private function limitUsersForManager($query): void
    {
        $query->where(function ($users) {
            $users->whereRaw('UPPER(role) = ?', ['CASHIER'])
                ->orWhereRaw('UPPER(role) = ?', ['WAITER'])
                ->orWhereRaw('UPPER(role) = ?', ['SERVER'])
                ->orWhereHas('roles', function ($roles) {
                    $roles->whereRaw('UPPER(name) = ?', ['CASHIER'])
                        ->orWhereRaw('UPPER(name) = ?', ['WAITER'])
                        ->orWhereRaw('UPPER(name) = ?', ['SERVER'])
                        ->orWhereRaw('UPPER(code) = ?', ['CASHIER'])
                        ->orWhereRaw('UPPER(code) = ?', ['WAITER'])
                        ->orWhereRaw('UPPER(code) = ?', ['SERVER']);
                });
        });

        if (auth()->user()->branch_id) {
            $query->where('branch_id', auth()->user()->branch_id);
        }
    }

    private function availableBranches()
    {
        $query = Branch::query()->orderBy('name');

        if ($this->managerIsLimited() && auth()->user()->branch_id) {
            $query->whereKey(auth()->user()->branch_id);
        }

        return $query->get();
    }

    private function availableRoles()
    {
        $query = Role::query()->orderBy('name');

        if ($this->managerIsLimited()) {
            $query->where(function ($roles) {
                $roles->whereRaw('UPPER(name) = ?', ['CASHIER'])
                    ->orWhereRaw('UPPER(name) = ?', ['WAITER'])
                    ->orWhereRaw('UPPER(name) = ?', ['SERVER'])
                    ->orWhereRaw('UPPER(code) = ?', ['CASHIER'])
                    ->orWhereRaw('UPPER(code) = ?', ['WAITER'])
                    ->orWhereRaw('UPPER(code) = ?', ['SERVER']);
            });
        }

        return $query->get();
    }

    private function availableDepartments()
    {
        return Department::where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    private function authorizeRequestedRole(string $roleName): void
    {
        if (!$this->managerIsLimited()) {
            return;
        }

        $role = Role::where('name', $roleName)
            ->orWhere('code', strtoupper($roleName))
            ->first();

        $roleCode = strtoupper((string) ($role->code ?? $role->name ?? $roleName));

        if (!in_array($roleCode, ['CASHIER', 'WAITER', 'SERVER'], true)) {
            abort(403);
        }
    }

    private function authorizeManageableUser(User $user): void
    {
        if (!$this->managerIsLimited()) {
            return;
        }

        if (!$user->hasOperationalRole('CASHIER', 'WAITER', 'SERVER')) {
            abort(403);
        }

        if (
            auth()->user()->branch_id
            && $user->branch_id !== auth()->user()->branch_id
        ) {
            abort(403);
        }
    }
}
