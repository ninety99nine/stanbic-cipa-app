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


class UserController extends Controller
{
    use PasswordValidationRules;

    public function getUsers(Request $request)
    {
        try {

            //  Return a list of roles
            $roles = Role::all();

            //  Return a list of permissions
            $permissions = Permission::all();

            //  Return a list of users
            $users = (new User)->getResources($request);

            return Inertia::render('Users/List', ['users' => $users, 'roles' => $roles, 'permissions' => $permissions]);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function createUser(Request $request)
    {
        Validator::make($request->all(), [
            'role' => ['required', 'exists:roles,name'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users']
        ])->validate();

        //  Generate the temporary password
        //  $temporary_password = Hash::make(Str::random(8));
        $temporary_password = Hash::make('stanb!c50672');

        //  Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $temporary_password
        ]);

        //  Assign role to user
        $user->assignRole($request->role);

        return redirect()->back()->with('message', 'Created Successfully');

    }

    public function updateUser(Request $request, $user_id)
    {
        Validator::make($request->all(), [
            'role' => ['required', 'exists:roles,name'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255']
        ])->validate();

        $user = User::find($user_id);

        if( $user ){

            $data = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            //  If we would like to reset the password
            if( $request->input('reset_password') == true ){

                //  Generate the temporary password
                //  $data['password'] = Hash::make(Str::random(8));
                $data['password'] = Hash::make('stanb!c50672');

            }

            //  Update user
            $user->update($data);

            // Remove current roles and replace by the array given
            $user->syncRoles([$request->role]);

        }

        return redirect()->back()->with('message', 'Updated Successfully');
    }

    public function deleteUser($user_id)
    {
        $user = User::find($user_id);

        if( $user ){

            $user->delete();

        }

        return redirect()->back()->with('message', 'Deleted Successfully');

    }
}
