<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\OwnershipBundle;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OwnershipBundleController extends Controller
{
    public function getOwnershipBundles(Request $request)
    {
        try {

            /**
             *  Set the dynamic filter options
             */
            $company_statuses = collect(
                DB::table('companies')->whereNotNull('company_status')->groupBy('company_status')->pluck('company_status')
            )->filter()->values()->toArray();

            $company_types = collect(
                DB::table('companies')->whereNotNull('company_type')->groupBy('company_type')->pluck('company_type')
            )->filter()->values()->toArray();

            $company_sub_types = collect(
                DB::table('companies')->whereNotNull('company_sub_type')->groupBy('company_sub_type')->pluck('company_sub_type')
            )->filter()->values()->toArray();

            $business_sectors = collect(
                DB::table('companies')->whereNotNull('business_sector')->groupBy('business_sector')->pluck('business_sector')
            )->filter()->values()->toArray();

            $dynamic_filter_options = [
                'company_statuses' => $company_statuses,
                'company_sub_types' => $company_sub_types,
                'company_types' => $company_types,
                'business_sectors' => $business_sectors
            ];

            //  Return a list of ownership bundles
            $ownership_bundles = (new OwnershipBundle)->getResources($request);

            return Inertia::render('Ownership/List', [
                'ownership_bundles' => $ownership_bundles,
                'dynamic_filter_options' => $dynamic_filter_options
            ]);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

    public function exportOwnershipBundles(Request $request)
    {
        try {

            //  Export a list of ownership bundles
            return (new OwnershipBundle)->exportResources($request);

        } catch (\Exception $e) {

            throw ($e);

        }
    }

}
