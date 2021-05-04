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
Route::prefix('ownership')->namespace('App\Http\Controllers')->middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/', 'OwnershipBundleController@getOwnershipBundles')->name('ownership-bundles');

});

// Route for view/blade file.
Route::get('importExportView', [ExcelController::class, 'importExportView'])->name('importExportView');
// Route for export/download tabledata to .csv, .xls or .xlsx
Route::get('exportExcel/{type}', [ExcelController::class, 'exportExcel'])->name('exportExcel');
// Route for import excel data to database.
Route::post('importExcel', [ExcelController::class, 'importExcel'])->name('importExcel');
