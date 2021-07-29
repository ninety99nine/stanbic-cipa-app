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

//  Ownership Bundles Resource Routes
Route::prefix('directors')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'DirectorController@getDirectors')->name('directors')->middleware('can:view directors');

    // Route for export/download tabledata to .csv, .xls or .xlsx
    Route::get('/export', 'DirectorController@exportDirectors')->name('directors-export')->middleware('can:export directors');

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

//  Reports Resource Routes
Route::prefix('reports')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', function(){

        //  Return a list of companies
        $companies = \App\Models\Company::markedAsClient()->get();
        $total_companies = collect($companies)->count();

        //  Return a list of ownership bundles
        $ownership_bundles = \App\Models\OwnershipBundle::all();
        $total_ownership_bundles = collect($ownership_bundles)->count();

        //  Company Status As Percentage
        $company_status_by_percentage = collect($companies)->groupBy('company_status')->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name ? $name : 'Not specified',
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values();

        //  Exempt Status As Percentage
        $exempt_status_by_percentage = collect($companies)->groupBy('exempt.name')->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name ? $name : 'Not specified',
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values();

        //  Foreign Status As Percentage
        $foreign_status_by_percentage = collect($companies)->groupBy('foreign_company.name')->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name ? $name : 'Not specified',
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values();

        //  Company Type As Percentage
        $company_type_by_percentage = collect($companies)->groupBy('company_type')->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name ? $name : 'Not specified',
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values();

        //  Company Sub Type As Percentage
        $company_sub_type_by_percentage = collect($companies)->groupBy('company_sub_type')->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name ? $name : 'Not specified',
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values();

        //  Business Sector As Percentage
        $business_sector_by_percentage = collect($companies)->groupBy('business_sector')->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name ? $name : 'Not specified',
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values();

        //  Old Company Number As Percentage
        $old_company_number_by_percentage = collect($companies)->groupBy(function ($item, $key) {
            return !empty($item['old_company_number']) ? 'Specified': 'Not Specified';
        })->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name,
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values()->toArray();

        //  Dissolution Date As Percentage
        $dissolution_date_by_percentage = collect($companies)->groupBy(function ($item, $key) {
            return !empty($item['dissolution_date']) ? 'Specified': 'Not Specified';
        })->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name,
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values()->toArray();

        //  Re-Registration Date As Percentage
        $re_registration_date_by_percentage = collect($companies)->groupBy(function ($item, $key) {
            return !empty($item['re_registration_date']) ? 'Specified': 'Not Specified';
        })->map(function($collection, $name) use ($total_companies){

            $curr_total = collect($collection)->count();

            return collect([
                'name' => $name,
                'total' => $curr_total,
                'percentage' => ($curr_total / $total_companies) * 100
            ]);

        })->values()->toArray();

        //  Incorporation Date
        $incorporation_dates = collect(collect($companies)->groupBy(function ($item, $key) {

            //  Group by years
            return substr($item['incorporation_date'], -4);

        })->map(function($collection_by_year, $year) use ($total_companies){

            $curr_total_by_year = collect($collection_by_year)->count();

            $drill_down = collect(collect($collection_by_year)->groupBy(function ($item, $key) {

                //  Group by months
                return substr($item['incorporation_date'], 3, 3);

            })->map(function($collection_grouped_by_month, $month) use ($curr_total_by_year, $year){

                $curr_total_group_by_month = collect($collection_grouped_by_month)->count();

                return collect([
                    $month, ($curr_total_group_by_month / $curr_total_by_year * 100)
                ]);

            })->toArray())->values();

            return collect([
                'id' => $year ? $year : 'Not specified',
                'name' => $year ? $year : 'Not specified',
                'data' => $drill_down,
                'count' => $curr_total_by_year,
                'percentage' => ($curr_total_by_year / $total_companies) * 100
            ]);

        })->sortBy('name')->toArray())->values();

        return Inertia::render('Reports/List', [
            'company_status_by_percentage' => $company_status_by_percentage,
            'exempt_status_by_percentage' => $exempt_status_by_percentage,
            'foreign_status_by_percentage' => $foreign_status_by_percentage,
            'company_type_by_percentage' => $company_type_by_percentage,
            'company_sub_type_by_percentage' => $company_sub_type_by_percentage,
            'business_sector_by_percentage' => $business_sector_by_percentage,
            'old_company_number_by_percentage' => $old_company_number_by_percentage,
            'dissolution_date_by_percentage' => $dissolution_date_by_percentage,
            're_registration_date_by_percentage' => $re_registration_date_by_percentage,
            'incorporation_dates' => $incorporation_dates
        ]);

    })->name('reports'); //->middleware('can:view reports');

    //  Route::get('/', 'ReportController@getReports')->name('reports')->middleware('can:view reports');

});
