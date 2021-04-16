<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
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

//  Companies Resource Routes
Route::prefix('companies')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'CompanyController@getCompanies')->name('companies');
    Route::post('/', 'CompanyController@createCompany')->name('company-create');

    //  Single company resources    /companies/{company_id}   name => company-*
    Route::prefix('/{company_id}')->name('company-')->group(function () {

        Route::get('/', 'CompanyController@getCompany')->name('show')->where('company_id', '[0-9]+');
        Route::put('/', 'CompanyController@updateCompany')->name('update')->where('company_id', '[0-9]+');
        Route::delete('/', 'CompanyController@deleteCompany')->name('delete')->where('company_id', '[0-9]+');

    });

});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function (Request $request) {

    $total = \App\Models\Company::count();
    $total_imported = \App\Models\Company::importedFromCipa()->count();
    $total_not_imported = \App\Models\Company::notImportedFromCipa()->count();
    $total_outdated = \App\Models\Company::outdatedWithCipa()->count();
    $total_recently_updated = \App\Models\Company::recentlyUpdatedWithCipa()->count();

    $progress_totals = [
        'total' => $total,
        'total_imported' => $total_imported,
        'total_not_imported' => $total_not_imported,
        'total_outdated' => $total_outdated,
        'total_recently_updated' => $total_recently_updated,
        'total_imported_percentage' => (int)($total_imported / $total * 100),
        'total_recently_updated_percentage' => (int) ($total_recently_updated / $total * 100),
    ];

    $companies = \App\Models\Company::all();

    /*
    $companies = \App\Models\Company::outdatedWithCipa()->limit(10);

    foreach( $companies as $company ){

        $company->requestCipaUpdate();

    }
    */

    //  Return response to Dashboard
    return Inertia::render('Dashboard', [
        'companies' => $companies,
        'progress_totals' => $progress_totals
    ]);

})->name('dashboard');
