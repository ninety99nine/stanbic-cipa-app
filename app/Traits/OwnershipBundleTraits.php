<?php

namespace App\Traits;

use App\Exports\CompaniesExport;
use App\Imports\CompaniesImport;
use Maatwebsite\Excel\Facades\Excel;

trait OwnershipBundleTraits
{
    /**
     *  This method returns a list of ownership bundles
     */
    public function getResources($data = [], $builder = null, $paginate = true)
    {
        try {

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            //  Validate the data (CommanTraits)
            $this->getResourcesValidation($data);

            //  If we already have an eloquent builder defined
            if( is_object($builder) ){

                //  Set the ownership bundles to this eloquent builder
                $ownershipBundles = $builder;

            }else{

                //  Get the ownership bundles
                $ownershipBundles = \App\Models\OwnershipBundle::with(['shareholder.owner.addresses', 'director', 'company']);

            }

            //  Filter the ownership bundles
            $ownershipBundles = $this->filterResources($data, $ownershipBundles);

            //  Sort the ownership bundles
            $ownershipBundles = $this->sortResources($data, $ownershipBundles);

            //  Return ownership bundles
            return $this->collectionResponse($data, $ownershipBundles, $paginate);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method filters the ownership bundles by search or status
     */
    public function filterResources($data = [], $ownershipBundles)
    {
        //  If we need to search for specific ownership bundles
        if ( isset($data['search']) && !empty($data['search']) ) {

            $ownershipBundles = $this->filterResourcesBySearch($data, $ownershipBundles);

        }elseif ( isset($data['status']) && !empty($data['status']) ) {

            $ownershipBundles = $this->filterResourcesByStatus($data, $ownershipBundles);

        }

        //  Return the ownership bundles
        return $ownershipBundles;
    }

    /**
     *  This method filters the ownership bundles by search
     */
    public function filterResourcesBySearch($data = [], $ownershipBundles)
    {
        //  Set the search term e.g "BW00001234567"
        $search_term = $data['search'] ?? null;

        //  Set the search term e.g "BW00001234567"
        $search_type = $data['search_type'] ?? 'all';

        /**
         *  Search ownership bundles where the search term entity is the owner e.g
         *
         *  $search_term = 'Company A';
         *
         *  Search: What does "Company A" own
         */
        if( $search_type == 'owned_by' ){

            return $ownershipBundles->searchOwnedBy($search_term);

        /**
         *  Search ownership bundles where other entities own this search term entity e.g
         *
         *  $search_term = 'Company A';
         *
         *  Search: Who owns "Company A"
         */
        }elseif( $search_type == 'who_owns' ){

            return $ownershipBundles->searchWhoOwns($search_term);

        /**
         *  Search ANY of the above cases
         */
        }else{

            return $ownershipBundles->search($search_term);

        }

    }

    /**
     *  This method filters the ownership bundles by status
     */
    public function filterResourcesByStatus($data = [], $ownershipBundles)
    {
        //  Set the statuses to an empty array
        $statuses = [];

        //  Set the status filters e.g ["individuals", "companies", "director", ...] or "registered,companies,director, ..."
        $status_filters = $data['status'];

        //  If the filters are provided as String format e.g "individuals,companies,director"
        if( is_string($status_filters) ){

            //  Set the statuses to the exploded Array ["individuals", "companies", "director", ...]
            $statuses = explode(',', $status_filters);

        }elseif( is_array($status_filters) ){

            //  Set the statuses to the given Array ["individuals", "companies", "director", ...]
            $statuses = $status_filters;

        }

        //  Clean-up each status filter
        foreach ($statuses as $key => $status) {

            //  Convert " individuals " to "Individuals"
            $statuses[$key] = strtolower(trim($status));

        }

        if ( $ownershipBundles && count($statuses) ) {

            /**************************************************
             *  FILTER BY OWNERSHIP SHAREHOLDER OWNER TYPES   *
             *************************************************/

            $filterByShareholderOwnerTypes = collect($statuses)->filter(function($status){
                return in_array($status, ['individual', 'company', 'organisation']);
            })->toArray();

            if( count($filterByShareholderOwnerTypes) ){

                $ownershipBundles = $ownershipBundles->shareholderOwnerTypes($filterByShareholderOwnerTypes);

            }

            /*******************************
             *  FILTER BY DIRECTOR         *
             *******************************/

            $filterByDirectorTypes = collect($statuses)->filter(function($status){
                return in_array($status, [
                    'current director', 'former director', 'not director'
                ]);
            })->toArray();

            if( count($filterByDirectorTypes) ){

                $ownershipBundles = $ownershipBundles->directorType($filterByDirectorTypes);

            }

            /********************************************
             *  FILTER BY SHAREHOLDER ALLOCATION TYPE   *
             ********************************************/

            $filterByShareholderAllocationTypes = collect($statuses)->filter(function($status){
                return in_array($status, [
                    'majority shareholder', 'minority shareholder', 'equal shareholder',
                    'only shareholder', 'partial shareholder', 'custom shareholder'
                ]);
            })->toArray();

            if( count($filterByShareholderAllocationTypes) ){

                $start_percentage = isset($data['start_percentage']) ? $data['start_percentage'] : null;

                $end_percentage = isset($data['end_percentage']) ? $data['end_percentage'] : null;

                $ownershipBundles = $ownershipBundles->shareholderAllocationType($filterByShareholderAllocationTypes, $start_percentage, $end_percentage);

            }

            /*******************************
             *  FILTER BY OWNERSHIP         *
             *******************************/

            $filterByShareholderSourcesOfShares = collect($statuses)->filter(function($status){
                return in_array($status, [
                    'shareholder to one', 'shareholder to many', 'shareholder to specific'
                ]);
            })->toArray();

            if( count($filterByShareholderSourcesOfShares) ){

                $min_source_of_shares = null;
                $max_source_of_shares = null;
                $exact_source_of_shares = null;

                $source_of_shares_type = $data['source_of_shares_type'];

                if( in_array(strtolower($source_of_shares_type), ['minimum', 'range']) ){

                    $min_source_of_shares = $data['min_source_of_shares'] ?? null;

                }

                if( in_array(strtolower($source_of_shares_type), ['maximum', 'range']) ){

                    $max_source_of_shares = $data['max_source_of_shares'] ?? null;

                }

                if( in_array(strtolower($source_of_shares_type), ['exact']) ){

                    $exact_source_of_shares = $data['exact_source_of_shares'] ?? null;

                }

                $ownershipBundles = $ownershipBundles->hasSourcesOfShares($filterByShareholderSourcesOfShares, $min_source_of_shares, $max_source_of_shares, $exact_source_of_shares);

            }

            /**********************************************
             *  FILTER BY SHAREHOLDER APPOINTMENT DATE    *
             *********************************************/

            if( in_array('shareholder appointed date', $statuses) && isset($data['shareholder_appointed_start_date']) && !empty($data['shareholder_appointed_start_date'])){

                $start_date = $data['shareholder_appointed_start_date'];

                $ownershipBundles = $ownershipBundles->shareholderAppointmentDate($start_date, null);

            }

            if( in_array('shareholder appointed date', $statuses) && isset($data['shareholder_appointed_end_date']) && !empty($data['shareholder_appointed_end_date'])){

                $end_date = $data['shareholder_appointed_end_date'];

                $ownershipBundles = $ownershipBundles->shareholderAppointmentDate(null, $end_date);

            }

            /*****************************************
             *  FILTER BY SHAREHOLDER CEASED DATE    *
             *****************************************/

            if( in_array('shareholder ceased date', $statuses) && isset($data['shareholder_ceased_start_date']) && !empty($data['shareholder_ceased_start_date'])){

                $start_date = $data['shareholder_ceased_start_date'];

                $ownershipBundles = $ownershipBundles->shareholderCeasedDate($start_date, null);

            }

            if( in_array('shareholder ceased date', $statuses) && isset($data['shareholder_ceased_end_date']) && !empty($data['shareholder_ceased_end_date'])){

                $end_date = $data['shareholder_ceased_end_date'];

                $ownershipBundles = $ownershipBundles->shareholderCeasedDate(null, $end_date);

            }

            /*******************************************
             *  FILTER BY DIRECTOR APPOINTMENT DATE    *
             *******************************************/

            if( in_array('director appointed date', $statuses) && isset($data['director_appointed_start_date']) && !empty($data['director_appointed_start_date'])){

                $start_date = $data['director_appointed_start_date'];

                $ownershipBundles = $ownershipBundles->directorAppointmentDate($start_date, null);

            }

            if( in_array('director appointed date', $statuses) && isset($data['director_appointed_end_date']) && !empty($data['director_appointed_end_date'])){

                $end_date = $data['director_appointed_end_date'];

                $ownershipBundles = $ownershipBundles->directorAppointmentDate(null, $end_date);

            }

            /**************************************
             *  FILTER BY DIRECTOR CEASED DATE    *
             **************************************/

            if( in_array('director ceased date', $statuses) && isset($data['director_ceased_start_date']) && !empty($data['director_ceased_start_date'])){

                $start_date = $data['director_ceased_start_date'];

                $ownershipBundles = $ownershipBundles->directorCeasedDate($start_date, null);

            }

            if( in_array('director appointed date', $statuses) && isset($data['director_ceased_end_date']) && !empty($data['director_ceased_end_date'])){

                $end_date = $data['director_ceased_end_date'];

                $ownershipBundles = $ownershipBundles->directorCeasedDate(null, $end_date);

            }

        }

        //  Return the ownership bundles
        return $ownershipBundles;
    }

    /**
     *  This method sorts the ownership bundles
     */
    public function sortResources($data = [], $ownershipBundles)
    {
        //  Set the sort by e.g "updated_at"
        $sort_by = $data['sort_by'] ?? null;

        //  Set the sort by type e.g "desc"
        $sort_by_type = $data['sort_by_type'] ?? null;

        if($sort_by && $sort_by_type){

            if( $sort_by_type == 'asc' ){

                return $ownershipBundles->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' ASC');

            }elseif( $sort_by_type == 'desc' ){

                return $ownershipBundles->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' DESC');

            }

        }

        //  By default sort by the incorporation date
        return $ownershipBundles->latest('updated_at');

    }

    /**
     *  This method exports a list of ownership bundles
     */
    public function exportResources($data = [])
    {
        try {

            //  Get the ownership bundles
            $ownershipBundles = $this->getResources($data, null, null);

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            if( isset($data['export_type']) && !empty($data['export_type']) ){

                //  Set the "export_type"
                $export_type = $data['export_type'];

            }else{

                //  Set the "export_type"
                $export_type = 'csv';

            }

            //  Set the file name e.g "ownership.csv"
            $file_name = 'ownership.'.$export_type;

            //  Download the excel data
            return Excel::download(new CompaniesExport($ownershipBundles), $file_name);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method imports a list of ownership bundles
     */
    public function importResources($data = [])
    {
        try {

            Excel::import(new CompaniesImport, request()->file('excelFile'));

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method returns a single ownership bundle
     */
    public function getResource($id)
    {
        try {

            //  Get the resource
            $ownershipBundle = \App\Models\OwnershipBundle::where('id', $id)->first() ?? null;

            //  If exists
            if ($ownershipBundle) {

                //  Return ownership bundle
                return $ownershipBundle;

            } else {

                //  Return "Not Found" Error
                return help_resource_not_found();

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

}
