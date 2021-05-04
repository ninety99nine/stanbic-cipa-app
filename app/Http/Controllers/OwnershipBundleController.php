<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\OwnershipBundle;
use App\Http\Controllers\Controller;

class OwnershipBundleController extends Controller
{
    public function getOwnershipBundles(Request $request)
    {
        try {

            //  Return a list of ownership bundles
            $ownership_bundles = (new OwnershipBundle)->getResources($request);

            return Inertia::render('Ownership/List', [
                'ownership_bundles' => $ownership_bundles
            ]);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function getCompany($company_id)
    {
        try {

            //  Return a single company
            return (new OwnershipBundle)->getResource($company_id)->convertToApiFormat();

        } catch (\Exception $e) {

            return help_handle_exception($e);

        }
    }

}
