<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use \Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use \Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Actions\Fortify\PasswordValidationRules;


class RoleController extends Controller
{
    use PasswordValidationRules;

    public function getRoles()
    {
        $permissions = Permission::all();
        $roles = Role::with('permissions')->latest()->paginate(10);

        return Inertia::render('Roles/List', ['roles' => $roles, 'permissions' => $permissions]);
    }

    public function createRole(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'permissions.*' => ['required', 'exists:permissions,name']
        ])->validate();

        //  Create role
        $role = Role::create(['name' => strtolower($request->name)]);

        //  Sync multiple permissions
        $role->syncPermissions($request->permissions);

        return redirect()->back()->with('message', 'Created Successfully');

    }

    public function updateRole(Request $request, $role_id)
    {
        Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'permissions.*' => ['required', 'exists:permissions,name']
        ])->validate();

        $role = Role::find($role_id);

        if( $role ){

            //  Update role
            $role->update(['name' => strtolower($request->name)]);

            //  Sync multiple permissions
            $role->syncPermissions($request->permissions);

        }

        return redirect()->back()->with('message', 'Updated Successfully');
    }

    public function deleteRole(Request $request, $role_id)
    {
        Validator::make($request->all(), [
            'role' => ['required', 'exists:roles,name']
        ])->validate();

        //  Get the matching role
        $role = Role::find($role_id);

        //  If we have a role
        if( $role ){

            //  Return only users matching the given role
            $matchingUsers = User::role($role->name)->get();

            //  Foreach matching user
            foreach ($matchingUsers as $user) {

                //  Set the replacement role
                $user->syncRoles([$request->role]);

            }

            //  Delete the role
            $role->delete();

        }

        return redirect()->back()->with('message', 'Deleted Successfully');

    }
}
