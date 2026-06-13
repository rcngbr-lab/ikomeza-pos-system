<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\AuditLogService;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ROLE LIST
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $roles = Role::latest()->get();

        return view(
            'roles.index',
            compact('roles')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
{
    $permissions = Permission::orderBy(
        'name'
    )->get();

    return view(

        'roles.create',

        compact('permissions')

    );
}

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'code' => 'required|unique:roles,code',

            'name' => 'required|unique:roles,name',

            'description' => 'nullable'

        ]);

        $role = Role::create([

            'code' => strtoupper($request->code),

            'name' => $request->name,

            'guard_name' => 'web',

            'slug' => Str::slug($request->name),

            'description' => $request->description

        ]);

        AuditLogService::record([
            'action' => 'ROLE_CREATED',
            'module' => 'Roles',
            'model' => $role,
            'reference' => $role->name,
            'description' => 'Created role ' . $role->name . '.',
            'new_values' => $role->only(['code', 'name', 'description', 'guard_name']),
            'severity' => 'SECURITY',
        ]);

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
{
    $role = Role::findOrFail($id);

    $permissions = Permission::orderBy(
        'name'
    )->get();

    return view(

        'roles.edit',

        compact(

            'role',
            'permissions'

        )

    );
}

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([

            'code' =>
                'required|unique:roles,code,' . $role->id,

            'name' =>
                'required|unique:roles,name,' . $role->id,

        ]);

        $oldValues = $role->only(['code', 'name', 'slug', 'description']);

        $role->update([

            'code' => strtoupper($request->code),

            'name' => $request->name,

            'slug' => Str::slug($request->name),

            'description' => $request->description

        ]);

        AuditLogService::record([
            'action' => 'ROLE_UPDATED',
            'module' => 'Roles',
            'model' => $role,
            'reference' => $role->name,
            'description' => 'Updated role ' . $role->name . '.',
            'old_values' => $oldValues,
            'new_values' => $role->only(array_keys($oldValues)),
            'severity' => 'SECURITY',
        ]);

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (
            in_array(strtoupper((string) ($role->code ?? $role->name)), ['ADMIN', 'ADMINISTRATOR'], true)
            || (bool) ($role->is_system ?? false)
            || $role->users()->exists()
        ) {

            return back()->withErrors([

                'error' =>
                    'System roles and assigned roles cannot be deleted. Deactivate or adjust permissions instead.'

            ]);
        }

        AuditLogService::record([
            'action' => 'ROLE_DELETED',
            'module' => 'Roles',
            'model' => $role,
            'reference' => $role->name,
            'description' => 'Deleted unused role ' . $role->name . '.',
            'old_values' => $role->only(['code', 'name', 'description', 'guard_name']),
            'severity' => 'SECURITY',
        ]);

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Role deleted successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE PERMISSIONS PAGE
    |--------------------------------------------------------------------------
    */

    public function permissions($id)
    {
        $role = Role::findOrFail($id);

        $permissions = Permission::orderBy(
            'name'
        )->get();

        return view(
            'roles.permissions',
            compact(
                'role',
                'permissions'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ROLE PERMISSIONS
    |--------------------------------------------------------------------------
    */

    public function updatePermissions(
        Request $request,
        $id
    )
    {
        $role = Role::findOrFail($id);

        $permissionIds =
            $request->permissions ?? [];

        $permissions = Permission::whereIn(
            'id',
            $permissionIds
        )->pluck('name');

        $oldPermissions = $role->permissions()->pluck('name')->values()->all();

        $role->syncPermissions(
            $permissions
        );

        AuditLogService::record([
            'action' => 'ROLE_PERMISSIONS_CHANGED',
            'module' => 'Permissions',
            'model' => $role,
            'reference' => $role->name,
            'description' => 'Updated permissions for role ' . $role->name . '.',
            'old_values' => ['permissions' => $oldPermissions],
            'new_values' => ['permissions' => $permissions->values()->all()],
            'severity' => 'SECURITY',
        ]);

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Permissions updated successfully.'
            );
    }
}
