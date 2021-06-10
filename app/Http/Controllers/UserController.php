<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the authenticated user
        $this->user = Auth::user();
    }

    public function createUser(Request $request)
    {
        try {

            //  Return a new user
            return (new User)->createResource($request, $this->user)->convertToApiFormat();

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }

    public function updateUser(Request $request, $user_id)
    {
        try {

            //  Update the user
            return (new User)->getResource($user_id)->requestCipaUpdate();

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function getUsers(Request $request)
    {
        try {

            //  Return a list of users
            $users = (new User)->getResources($request);

            return Inertia::render('Users/List', [
                'users' => $users
            ]);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function exportUsers(Request $request)
    {
        try {

            //  Export a list of users
            return (new User)->exportResources($request);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function importUsers(Request $request)
    {
        try {

            //  Import a list of users
            (new User)->importResources($request);

            return redirect()->route('users');

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function getUser($user_id)
    {
        try {

            //  Return a single user
            return (new User)->getResource($user_id)->convertToApiFormat();

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }

    public function deleteUser($user_id)
    {
        try {

            //  Delete the user
            return (new User)->getResource($user_id)->deleteResource($this->user);

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }
}
