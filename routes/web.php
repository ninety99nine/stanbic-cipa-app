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

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function (Request $request) {

    //  Set Auth Credentials
    $username = 'apiBursR2bc6JhrY1iyFVQNWdoZ845H';
    $password = '15EKveY1US572yrycjaw5zoBBim1NQpH';

    //  Set Endpoint
    $url = 'https://suppre.cipa.support.fostermoore.com/ng-cipa-companies/soap/viewCompanyWS.wsdl';

    // Run API Call With Basic Authentication
    $response = Soap::to($url)->withBasicAuth($username, $password)->viewCompanyWS(['TxnBusinessIdentifier' => 'BW00001125314']);

    //  Return response to Dashboard
    return Inertia::render('Dashboard', [
        'response' => $response
    ]);

})->name('dashboard');
