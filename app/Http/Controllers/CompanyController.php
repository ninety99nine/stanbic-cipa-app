<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the authenticated user
        $this->user = Auth::user();
    }

    public function createCompany(Request $request)
    {
        try {

            //  Return a new company
            return (new Company)->createResource($request, $this->user)->convertToApiFormat();

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }

    public function updateCompany(Request $request, $company_id)
    {
        try {

            //  Update the company
            return (new Company)->getResource($company_id)->requestCipaUpdate();

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function getCompanies(Request $request)
    {
        try {

            //  Return a list of companies
            $companies = (new Company)->getResources($request);

            return Inertia::render('Companies/List', [
                'companies' => $companies
            ]);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function getCompany($company_id)
    {
        try {

            //  Return a single company
            return (new Company)->getResource($company_id)->convertToApiFormat();

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }

    public function deleteCompany($company_id)
    {
        try {

            //  Delete the company
            return (new Company)->getResource($company_id)->deleteResource($this->user);

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }

    public function arrangeCompanies(Request $request)
    {
        try {

            //  Arrange companies
            return (new Company())->reorderCompanies($request);

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }
}
