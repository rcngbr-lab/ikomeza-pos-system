<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Permission;
use App\Services\AuditLogService;

class PermissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $permissions = Permission::latest()->get();

        return view(
            'permissions.index',
            compact('permissions')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('permissions.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'code' => [
                'required',
                'unique:permissions,code'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ]

        ]);

        DB::beginTransaction();

        try {

            $permission = Permission::create([

                'code' => strtolower(
                    trim($request->code)
                ),

                'name' => trim(
                    $request->name
                ),

                'description' => trim(
                    $request->description
                )

            ]);

            AuditLogService::record([
                'action' => 'PERMISSION_CREATED',
                'module' => 'Permissions',
                'model' => $permission,
                'reference' => $permission->code,
                'description' => 'Created permission ' . $permission->name . '.',
                'new_values' => $permission->only(['code', 'name', 'description']),
                'severity' => 'SECURITY',
            ]);

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with(
                    'success',
                    'Permission created successfully'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return view(
            'permissions.edit',
            compact('permission')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([

            'code' => [
                'required',
                'unique:permissions,code,' . $permission->id
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ]

        ]);

        DB::beginTransaction();

        try {

            $oldValues = $permission->only(['code', 'name', 'description']);

            $permission->update([

                'code' => strtolower(
                    trim($request->code)
                ),

                'name' => trim(
                    $request->name
                ),

                'description' => trim(
                    $request->description
                )

            ]);

            AuditLogService::record([
                'action' => 'PERMISSION_UPDATED',
                'module' => 'Permissions',
                'model' => $permission,
                'reference' => $permission->code,
                'description' => 'Updated permission ' . $permission->name . '.',
                'old_values' => $oldValues,
                'new_values' => $permission->only(array_keys($oldValues)),
                'severity' => 'SECURITY',
            ]);

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with(
                    'success',
                    'Permission updated successfully'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | PREVENT DELETE IF ASSIGNED
        |--------------------------------------------------------------------------
        */

        if (
            $permission->roles()->count() > 0
        ) {

            return back()->with(
                'error',
                'Cannot delete assigned permission'
            );
        }

        DB::beginTransaction();

        try {

            AuditLogService::record([
                'action' => 'PERMISSION_DELETED',
                'module' => 'Permissions',
                'model' => $permission,
                'reference' => $permission->code,
                'description' => 'Deleted unassigned permission ' . $permission->name . '.',
                'old_values' => $permission->only(['code', 'name', 'description']),
                'severity' => 'SECURITY',
            ]);

            $permission->delete();

            DB::commit();

            return redirect()
                ->route('permissions.index')
                ->with(
                    'success',
                    'Permission deleted successfully'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }
}
