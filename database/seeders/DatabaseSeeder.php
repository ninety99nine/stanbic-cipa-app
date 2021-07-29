<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use \Spatie\Permission\Models\Role;
use \Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        //  Create the roles
        $adminRole = Role::create(['name' => 'admin', 'guard_name'=>'sanctum']);
        $userRole = Role::create(['name' => 'user', 'guard_name'=>'sanctum']);

        $adminPermissions = [
            'view companies', 'import companies', 'export companies', 'view shareholders', 'export shareholders',
            'view directors', 'export directors', 'view users', 'create users', 'update users', 'delete users',
            'view roles', 'create roles', 'update roles', 'delete roles',
            'view reports'
        ];

        $userPermissions = [
            'view companies', 'import companies', 'export companies', 'view shareholders', 'export shareholders'
        ];

        foreach ($adminPermissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name'=>'sanctum']);
        }

        //  Assign permissions to admin
        $adminRole->syncPermissions($adminPermissions);

        //  Assign permissions to user
        $userRole->syncPermissions($userPermissions);

        //  Grant specific users the admin role
        $user = \App\Models\User::where('email', 'brandontabona@gmail.com')->first();
        $user->syncRoles(['admin']);

        //  Grant other users the user role
        $users = \App\Models\User::where('email', '!=', 'brandontabona@gmail.com')->get();

        foreach ($users as $user) {
            $user->syncRoles(['user']);
        }
    }
}
