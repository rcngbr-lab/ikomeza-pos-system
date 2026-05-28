<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        Role::create([

            'code' => strtoupper($request->code),

            'name' => $request->name,

            'guard_name' => 'web',

            'slug' => Str::slug($request->name),

            'description' => $request->description

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

        $role->update([

            'code' => strtoupper($request->code),

            'name' => $request->name,

            'slug' => Str::slug($request->name),

            'description' => $request->description

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

        if ($role->name == 'ADMIN') {

            return back()->withErrors([

                'error' =>
                    'Admin role cannot be deleted.'

            ]);
        }

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

        $role->syncPermissions(
            $permissions
        );

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Permissions updated successfully.'
            );
    }
}