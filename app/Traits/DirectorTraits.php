<?php

namespace App\Traits;

use App\Exports\DirectorsExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;

trait DirectorTraits
{
    /**
     *  This method returns a list of directors
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

                //  Set the directors to this eloquent builder
                $directors = $builder;

            }else{

                //  Get the directors
                $directors = \App\Models\Director::with(['individual.addresses', 'company']);

            }

            //  Filter the directors
            $directors = $this->filterResources($data, $directors);

            //  Sort the directors
            $directors = $this->sortResources($data, $directors);

            //  Return directors
            return $this->collectionResponse($data, $directors, $paginate);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method filters the directors by search or status
     */
    public function filterResources($data = [], $directors)
    {
        //  If we need to search for specific directors
        if ( isset($data['search']) && !empty($data['search']) ) {

            $directors = $this->filterResourcesBySearch($data, $directors);

        }elseif ( isset($data['status']) && !empty($data['status']) ) {

            $directors = $this->filterResourcesByStatus($data, $directors);

        }

        //  Return the directors
        return $directors;
    }

    /**
     *  This method filters the directors by search
     */
    public function filterResourcesBySearch($data = [], $directors)
    {
        //  Set the search term e.g "John"
        $search_term = $data['search'] ?? null;

        //  Search directors
        return $directors->whereHas('individual', function (Builder $query) use ($search_term){
            $query->search($search_term);
        });

    }

    /**
     *  This method filters the directors by status
     */
    public function filterResourcesByStatus($data = [], $directors)
    {
        //  Set the statuses to an empty array
        $statuses = [];

        //  Set the status filters e.g ["current directors", "former directors", ...] or "current directors,former directors, ..."
        $status_filters = $data['status'];

        //  If the filters are provided as String format e.g "current directors,former directors"
        if( is_string($status_filters) ){

            //  Set the statuses to the exploded Array ["current directors", "former directors", ...]
            $statuses = explode(',', $status_filters);

        }elseif( is_array($status_filters) ){

            //  Set the statuses to the given Array ["current directors", "former directors", ...]
            $statuses = $status_filters;

        }

        //  Clean-up each status filter
        foreach ($statuses as $key => $status) {

            //  Convert " current directors " to "Current directors"
            $statuses[$key] = strtolower(trim($status));

        }

        if ( $directors && count($statuses) ) {

            /*******************************
             *  FILTER BY COMPANY STATUS   *
             *******************************/

            $company_statuses = collect(DB::table('companies')->groupBy('company_status')->pluck('company_status'))->filter()->values()->map(function ($value) {
                return strtolower($value);
            })->toArray();

            $filterByCompanyStatuses = collect($statuses)->filter(function($status) use ($company_statuses) {


                return in_array($status, $company_statuses);
            })->toArray();

            if( count($filterByCompanyStatuses) ){

                $directors = $directors->whereHas('company', function (Builder $query) use ($filterByCompanyStatuses) {
                    $query->companyStatus($filterByCompanyStatuses);
                });

            }

            /*******************************
             *  FILTER BY COMPANY TYPE     *
             *******************************/

            $company_types = collect(DB::table('companies')->groupBy('company_type')->pluck('company_type'))->filter()->values()->map(function ($value) {
                return strtolower($value);
            })->toArray();

            $filterByCompanyTypes = collect($statuses)->filter(function($status) use ($company_types) {
                return in_array($status, $company_types);
            })->toArray();

            if( count($filterByCompanyTypes) ){

                $directors = $directors->whereHas('company', function (Builder $query) use ($filterByCompanyTypes) {
                    $query->companyType($filterByCompanyTypes);
                });

            }

            /*******************************
             *  FILTER BY COMPANY SUB TYPE *
             *******************************/

            $company_sub_types = collect(DB::table('companies')->groupBy('company_sub_type')->pluck('company_sub_type'))->filter()->values()->map(function ($value) {
                return strtolower($value);
            })->toArray();

            $filterByCompanySubTypes = collect($statuses)->filter(function($status) use ($company_sub_types) {
                return in_array($status, $company_sub_types);
            })->toArray();

            if( count($filterByCompanySubTypes) ){

                $directors = $directors->whereHas('company', function (Builder $query) use ($filterByCompanySubTypes) {
                    $query->companySubType($filterByCompanySubTypes);
                });

            }

            /**************************************
             *  FILTER BY COMPANY BUSINESS SECTOR *
             *************************************/

            $business_sectors = collect(DB::table('companies')->groupBy('business_sector')->pluck('business_sector'))->filter()->values()->map(function ($value) {
                return strtolower($value);
            })->toArray();

            $filterByBusinessSectors = collect($statuses)->filter(function($status) use ($business_sectors) {
                return in_array($status, $business_sectors);
            })->toArray();

            if( count($filterByBusinessSectors) ){

                $directors = $directors->whereHas('company', function (Builder $query) use ($filterByBusinessSectors) {
                    $query->businessSector($filterByBusinessSectors);
                });

            }

            /*****************************************
             *  FILTER BY COMPLIANT / NOT COMPLIANT  *
             *****************************************/

            //  If we want only compliant companies and not non-compliant companies
            if( in_array('compliant', $statuses) && !in_array('not compliant', $statuses) ){

                $directors = $directors->whereHas('company', function (Builder $query) {
                    $query->compliant();
                });

            //  If we want only non-compliant companies and not compliant companies
            }elseif( in_array('not compliant', $statuses) && !in_array('compliant', $statuses) ){

                $directors = $directors->whereHas('company', function (Builder $query) {
                    $query->notCompliant();
                });

            }

            /*******************************************
             *  FILTER BY CURRENT/FORMER DIRECTOR      *
             ******************************************/

            if( in_array('current director', $statuses) && !in_array('former director', $statuses)){

                $directors = $directors->onlyCurrentDirectors();

            }elseif( in_array('former director', $statuses) && !in_array('current director', $statuses) ){

                $directors = $directors->onlyFormerDirectors();

            }

            /********************************************
             *  FILTER BY DIRECTOR ALLOCATION TYPE      *
             ********************************************/

            $filterByDirectorAllocationTypes = collect($statuses)->filter(function($status){
                return in_array($status, [
                    'majority director', 'minority director', 'equal director',
                    'only director', 'partial director', 'custom director'
                ]);
            })->toArray();

            if( count($filterByDirectorAllocationTypes) ){

                $start_percentage = isset($data['start_percentage']) ? $data['start_percentage'] : null;

                $end_percentage = isset($data['end_percentage']) ? $data['end_percentage'] : null;

                $directors = $directors->whereHas('ownershipBundles', function (Builder $query) use ($filterByDirectorAllocationTypes, $start_percentage, $end_percentage)  {
                    $query->directorAllocationType($filterByDirectorAllocationTypes, $start_percentage, $end_percentage);
                });

            }




            /*******************************
             *  FILTER BY OWNERSHIP         *
             *******************************/

            $filterByDirectorToOneOrManyCompanies = collect($statuses)->filter(function($status){
                return in_array($status, [
                    'director to one', 'director to many', 'director to specific'
                ]);
            })->toArray();

            if( count($filterByDirectorToOneOrManyCompanies) ){

                $min_companies = null;
                $max_companies = null;
                $exact_companies = null;

                $director_to_specific_type = $data['director_to_specific_type'] ?? null;

                if( $director_to_specific_type ){

                    if( in_array(strtolower($director_to_specific_type), ['minimum', 'range']) ){

                        $min_companies = $data['min_companies'] ?? null;

                    }

                    if( in_array(strtolower($director_to_specific_type), ['maximum', 'range']) ){

                        $max_companies = $data['max_companies'] ?? null;
                    }

                    if( in_array(strtolower($director_to_specific_type), ['exact']) ){

                        $exact_companies = $data['exact_companies'] ?? null;

                    }

                }

                $directors = $directors->directorToNumberOfCompanies($filterByDirectorToOneOrManyCompanies, $min_companies, $max_companies, $exact_companies);

            }

            /*******************************
             *  FILTER BY OWNERSHIP         *
             *******************************/

            $filterByCompanyWithOneOrManyDirectors = collect($statuses)->filter(function($status){
                return in_array($status, [
                    'has one director', 'has many directors', 'has specific directors'
                ]);
            })->toArray();

            if( count($filterByCompanyWithOneOrManyDirectors) ){

                $min_directors = null;
                $max_directors = null;
                $equal_directors = null;

                $specific_directors_type = $data['specific_directors_type'] ?? null;

                if( $specific_directors_type ){

                    if( in_array(strtolower($specific_directors_type), ['minimum', 'range']) ){

                        $min_directors = $data['min_directors'] ?? null;

                    }

                    if( in_array(strtolower($specific_directors_type), ['maximum', 'range']) ){

                        $max_directors = $data['max_directors'] ?? null;

                    }

                    if( in_array(strtolower($specific_directors_type), ['exact']) ){

                        $equal_directors = $data['equal_directors'] ?? null;

                    }

                }

                $directors = $directors->whereHas('ownershipBundles', function (Builder $query) use ($filterByCompanyWithOneOrManyDirectors, $min_directors, $max_directors, $equal_directors) {
                    $query->directorAllocationType($filterByCompanyWithOneOrManyDirectors, $min_directors, $max_directors, $equal_directors);
                });

            }

            /*******************************************
             *  FILTER BY DIRECTOR APPOINTMENT DATE    *
             *******************************************/

            if( in_array('director appointed date', $statuses) && isset($data['director_appointed_start_date']) && !empty($data['director_appointed_start_date'])){

                $start_date = $data['director_appointed_start_date'];

                $directors = $directors->appointmentDate($start_date, null);

            }

            if( in_array('director appointed date', $statuses) && isset($data['director_appointed_end_date']) && !empty($data['director_appointed_end_date'])){

                $end_date = $data['director_appointed_end_date'];

                $directors = $directors->appointmentDate(null, $end_date);

            }

            /**************************************
             *  FILTER BY DIRECTOR CEASED DATE    *
             **************************************/

            if( in_array('director ceased date', $statuses) && isset($data['director_ceased_start_date']) && !empty($data['director_ceased_start_date'])){

                $start_date = $data['director_ceased_start_date'];

                $directors = $directors->ceasedDate($start_date, null);

            }

            if( in_array('director appointed date', $statuses) && isset($data['director_ceased_end_date']) && !empty($data['director_ceased_end_date'])){

                $end_date = $data['director_ceased_end_date'];

                $directors = $directors->ceasedDate(null, $end_date);

            }

        }

        //  Return the directors
        return $directors;
    }

    /**
     *  This method sorts the directors
     */
    public function sortResources($data = [], $directors)
    {
        //  Set the sort by e.g "updated_at"
        $sort_by = $data['sort_by'] ?? null;

        //  Set the sort by type e.g "desc"
        $sort_by_type = $data['sort_by_type'] ?? null;

        if($sort_by && $sort_by_type){

            if( $sort_by_type == 'asc' ){

                return $directors->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' ASC');

            }elseif( $sort_by_type == 'desc' ){

                return $directors->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' DESC');

            }

        }

        //  By default sort by the incorporation date
        return $directors->latest('updated_at');

    }

    /**
     *  This method exports a list of directors
     */
    public function exportResources($data = [])
    {
        try {

            //  Get the directors
            $directors = $this->getResources($data, null, null);

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            if( isset($data['export_type']) && !empty($data['export_type']) ){

                //  Set the "export_type"
                $export_type = $data['export_type'];

            }else{

                //  Set the "export_type"
                $export_type = 'csv';

            }

            //  Set the file name e.g "director.csv"
            $file_name = 'director.'.$export_type;

            //  Download the excel data
            return Excel::download(new DirectorsExport($directors), $file_name);

        } catch (\Exception $e) {

            throw($e);

        }
    }

}
