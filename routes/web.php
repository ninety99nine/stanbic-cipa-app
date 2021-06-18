<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ExcelController;
use RicorocksDigitalAgency\Soap\Facades\Soap;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function (Request $request) {

    return redirect()->route('companies');

})->name('dashboard');

//  Companies Resource Routes
Route::prefix('companies')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'CompanyController@getCompanies')->name('companies');
    Route::post('/', 'CompanyController@createCompany')->name('company-create');

    // Route for export/download tabledata to .csv, .xls or .xlsx
    Route::get('/export', 'CompanyController@exportCompanies')->name('companies-export');
    Route::post('/import', 'CompanyController@importCompanies')->name('companies-import');

    //  Single company resources    /companies/{company_id}   name => company-*
    Route::prefix('/{company_id}')->name('company-')->group(function () {

        Route::get('/', 'CompanyController@getCompany')->name('show')->where('company_id', '[0-9]+');
        Route::put('/', 'CompanyController@updateCompany')->name('update')->where('company_id', '[0-9]+');
        Route::delete('/', 'CompanyController@deleteCompany')->name('delete')->where('company_id', '[0-9]+');

    });

});

//  Ownership Bundles Resource Routes
Route::prefix('shareholders')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'OwnershipBundleController@getOwnershipBundles')->name('shareholders')->middleware('can:view shareholders');

    // Route for export/download tabledata to .csv, .xls or .xlsx
    Route::get('/export', 'OwnershipBundleController@exportOwnershipBundles')->name('shareholders-export')->middleware('can:export shareholders');

});

//  Users Resource Routes
Route::prefix('users')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'UserController@getUsers')->name('users')->middleware('can:view users');
    Route::post('/', 'UserController@createUser')->name('user-create')->middleware('can:create users');

    //  Single user resources    /users/{user_id}   name => user-*
    Route::prefix('/{user_id}')->name('user-')->group(function () {

        Route::put('/', 'UserController@updateUser')->name('update')->where('user_id', '[0-9]+')->middleware('can:update users');
        Route::delete('/', 'UserController@deleteUser')->name('delete')->where('user_id', '[0-9]+')->middleware('can:delete users');

    });

});

//  Roles Resource Routes
Route::prefix('roles')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'RoleController@getRoles')->name('roles')->middleware('can:view roles');
    Route::post('/', 'RoleController@createRole')->name('role-create')->middleware('can:create roles');

    //  Single role resources    /roles/{role_id}   name => role-*
    Route::prefix('/{role_id}')->name('role-')->group(function () {

        Route::put('/', 'RoleController@updateRole')->name('update')->where('role_id', '[0-9]+')->middleware('can:update roles');
        Route::delete('/', 'RoleController@deleteRole')->name('delete')->where('role_id', '[0-9]+')->middleware('can:delete roles');

    });

});
