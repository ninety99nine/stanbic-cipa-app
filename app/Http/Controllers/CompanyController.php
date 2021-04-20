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

            $total = Company::count();
            $total_imported = Company::importedFromCipa()->count();
            $total_not_imported = Company::notImportedFromCipa()->count();
            $total_outdated = Company::outdatedWithCipa()->count();
            $total_recently_updated = Company::recentlyUpdatedWithCipa()->count();

            $progress_totals = [
                'total' => $total,
                'total_imported' => $total_imported,
                'total_not_imported' => $total_not_imported,
                'total_outdated' => $total_outdated,
                'total_recently_updated' => $total_recently_updated,
                'total_imported_percentage' => (int)($total_imported / $total * 100),
                'total_recently_updated_percentage' => (int) ($total_recently_updated / $total * 100),
            ];

            //  Return a list of companies
            $companies = (new Company)->getResources($request);

            return Inertia::render('Companies/List', [
                'companies' => $companies,
                'progress_totals' => $progress_totals
            ]);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function exportCompanies(Request $request)
    {
        try {

            //  Export a list of companies
            return (new Company)->exportResources($request);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function importCompanies(Request $request)
    {
        try {

            //  Import a list of companies
            (new Company)->importResources($request);

            return redirect()->route('companies');

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
}
