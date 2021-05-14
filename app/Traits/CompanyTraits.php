<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Exports\CompaniesExport;
use App\Imports\CompaniesImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RicorocksDigitalAgency\Soap\Facades\Soap;
use App\Http\Resources\Company as CompanyResource;
use App\Http\Resources\Companies as CompaniesResource;
use BrightNucleus\CountryCodes\Country as CountryCodes;
use App\Models\Address;
use App\Models\Business;
use App\Models\Company;
use App\Models\Country;
use App\Models\Director;
use App\Models\Individual;
use App\Models\Organisation;
use App\Models\OwnershipBundle;
use App\Models\Region;
use App\Models\Secretary;
use App\Models\Shareholder;

trait CompanyTraits
{
    public $company = null;

    /**
     *  This method transforms a collection or single model instance
     */
    public function convertToApiFormat($collection = null)
    {
        try {

            // If this instance is a collection or a paginated collection
            if( $collection instanceof \Illuminate\Support\Collection ||
                $collection instanceof \Illuminate\Pagination\LengthAwarePaginator ){

                //  Transform the multiple instances
                return new CompaniesResource($collection);

            // If this instance is not a collection
            }elseif($this instanceof \App\Models\Company){

                //  Transform the single instance
                return new CompanyResource($this);

            }else{

                return $collection ?? $this;

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method creates a new company
     */
    public function createResource($data = [], $user = null)
    {
        try {

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            //  Verify permissions
            $this->createResourcePermission($user);

            //  Validate the data
            $this->createResourceValidation($data);

            //  Set the "duration"
            $duration = $data['duration'] ?? 1;

            //  Set the "start_at" datetime
            $start_at = $data['start_at'] ?? (Carbon::now())->format('Y-m-d H:i:s');

            //  Calculate the "end_at" datetime based on the "start_at" datetime and "duration"
            $data['end_at'] = Carbon::parse($start_at)->addDays( $duration );

            //  Set the template with the resource fields allowed
            $template = collect($data)->only($this->getFillable())->toArray();

            /**
             *  Create a new resource
             */
            $this->company = $this->create($template);

            //  If created successfully
            if ( $this->company ) {

                //  If we have the company resource
                if( (isset($data['resource_id']) && !empty($data['resource_id'])) &&
                     isset($data['resource_type']) && !empty($data['resource_type']) ){

                    //  Update the company with the resource id and type
                    $this->company->update([
                        'owner_id' => $data['resource_id'],
                        'owner_type' => $data['resource_type'],
                    ]);

                }

                //  Set this company arrangement
                $data = [
                    'arrangements' => [
                        [
                            'id' => $this->id,
                            'arrangement' => $this->arrangement ?? 1
                        ]
                    ]
                ];

                //  $this->reorderCompanies($data);

                //  Return the company
                return $this->company;

            }

        } catch (\Exception $e) {

            throw($e);

        }

    }

    /**
     *  This method updates an existing company
     */
    public function updateResource($data = [], $user = null)
    {
        try {

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            //  Merge the existing data with the new data
            $data = array_merge(collect($this)->only($this->getFillable())->toArray(), $data);

            //  Verify permissions
            $this->updateResourcePermission($user);

            //  Validate the data
            $this->updateResourceValidation($data);

            if( $data['reset_dates'] == true ){

                //  Set the "duration"
                $duration = $data['duration'] ?? 1;

                //  Set the "start_at" datetime
                $start_at = $data['start_at'] ?? (Carbon::now())->format('Y-m-d H:i:s');

                //  Calculate the "end_at" datetime based on the "start_at" datetime and "duration"
                $data['end_at'] = Carbon::parse($start_at)->addDays( $duration );

            }else{

                //  Do not update the "start_at" attribute
                unset($data['start_at']);

                //  Do not update the "end_at" attribute
                unset($data['end_at']);

            }

            //  Set the template with the resource fields allowed
            $template = collect($data)->only($this->getFillable())->toArray();

            /**
             *  Update the resource details
             */
            $updated = $this->update($template);

            //  If updated successfully
            if ($updated) {

                //  If we have the company resource
                if( (isset($data['resource_id']) && !empty($data['resource_id'])) &&
                     isset($data['resource_type']) && !empty($data['resource_type']) ){

                    //  Update the company with the resource id and type
                    $this->update([
                        'owner_id' => $data['resource_id'],
                        'owner_type' => $data['resource_type'],
                    ]);

                }

                //  Set this company arrangement
                $data = [
                    'arrangements' => [
                        [
                            'id' => $this->id,
                            'arrangement' => $this->arrangement ?? 1
                        ]
                    ]
                ];

                //  $this->reorderCompanies($data);

                //  Return a fresh instance
                return $this->fresh();

            }else{

                //  Return original instance
                return $this;

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method returns a list of companies
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

                //  Set the companies to this eloquent builder
                $companies = $builder;

            }else{

                //  Fields we want to select from the database (All fields except the details field)
                $fields = collect(array_merge(['id', 'created_at', 'updated_at'], (new \App\Models\Company )->getFillable()))
                            ->reject(function ($value) {
                                return in_array($value, ['details']);
                            })->toArray();

                //  Get the companies
                $companies = \App\Models\Company::select($fields)->with(['directors.individual', 'shareholders.owner', 'ownershipBundles']);

            }

            //  Filter the companies
            $companies = $this->filterResources($data, $companies);

            /**
             *  If we have an Eloquent Builder, then we can continue sorting.
             *  Sometimes we may have a Collection instead of an Eloquent
             *  Builder e.g After searching directly with CIPA. In this
             *  case we can't sort the result.
             */
            if( $companies instanceof \Illuminate\Database\Eloquent\Builder ){

                //  Sort the companies
                $companies = $this->sortResources($data, $companies);

            }

            //  Return companies
            return $this->collectionResponse($data, $companies, $paginate);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method filters the companies by search or status
     */
    public function filterResources($data = [], $companies)
    {
        //  If we need to search for specific companies
        if ( isset($data['search']) && !empty($data['search']) ) {

            if( isset($data['search_type']) && !empty($data['search_type']) ){

                //  Searching within application database
                if( $data['search_type'] == 'internal' ){

                    $companies = $this->filterResourcesBySearch($data, $companies);

                //  Searching outside application database (Searching within CIPA)
                }elseif( $data['search_type'] == 'external' ){

                    $companies = $this->requestCipaSearch($data);

                }

            }

        }elseif ( isset($data['status']) && !empty($data['status']) ) {

            $companies = $this->filterResourcesByStatus($data, $companies);

        }

        //  Return the companies
        return $companies;
    }

    /**
     *  This method filters the companies by search
     */
    public function filterResourcesBySearch($data = [], $companies)
    {
        //  Set the search term e.g "BW00001234567"
        $search_term = $data['search'] ?? null;

        //  Return searched companies otherwise original companies
        return empty($search_term) ? $companies : $companies->search($search_term);

    }

    /**
     *  This method filters the companies by status
     */
    public function filterResourcesByStatus($data = [], $companies)
    {
        //  Set the statuses to an empty array
        $statuses = [];

        //  Set the status filters e.g ["registered", "cancelled", "removed", ...] or "registered,cancelled,removed, ..."
        $status_filters = $data['status'];

        //  If the filters are provided as String format e.g "registered,cancelled,removed"
        if( is_string($status_filters) ){

            //  Set the statuses to the exploded Array ["registered", "cancelled", "removed", ...]
            $statuses = explode(',', $status_filters);

        }elseif( is_array($status_filters) ){

            //  Set the statuses to the given Array ["registered", "cancelled", "removed", ...]
            $statuses = $status_filters;

        }

        //  Clean-up each status filter
        foreach ($statuses as $key => $status) {

            //  Convert " registered " to "Registered"
            $statuses[$key] = strtolower(trim($status));

        }

        if ( $companies && count($statuses) ) {

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

                $companies = $companies->companyStatus($filterByCompanyStatuses);

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

                $companies = $companies->companyType($filterByCompanyTypes);

            }

            /*******************************
             *  FILTER BY COMPANY SUB TYPE *
             *******************************/

            $company_sub_types = collect(DB::table('companies')->groupBy('company_sub_type')->pluck('company_sub_type'))->filter()->values()->map(function ($value) {
                return strtolower($value);
            })->toArray();

            $filterByCompanySubType = collect($statuses)->filter(function($status) use ($company_sub_types) {
                return in_array($status, $company_sub_types);
            })->toArray();

            if( count($filterByCompanySubType) ){

                $companies = $companies->companySubType($filterByCompanySubType);

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

                $companies = $companies->businessSector($filterByBusinessSectors);

            }

            /*******************************
             *  FILTER BY EXEMPT           *
             *******************************/

            if( in_array('exempt', $statuses) && !in_array('not exempt', $statuses)){

                $companies = $companies->exempt();

            }elseif( in_array('not exempt', $statuses) && !in_array('exempt', $statuses) ){

                $companies = $companies->notExempt();

            }

            /*******************************
             *  FILTER BY FOREIGN / LOCAL  *
             *******************************/

            //  If we want only foreign companies and not local companies
            if( in_array('foreign company', $statuses) && !in_array('local company', $statuses) ){

                $companies = $companies->foreignCompany();

            //  If we want only local companies and not foreign companies
            }elseif( in_array('local company', $statuses) && !in_array('foreign company', $statuses) ){

                $companies = $companies->notForeignCompany();

            }

            /*************************************************
             *  FILTER BY IMPORTED / NOT IMPORTED WITH CIPA  *
             ************************************************/
            if( in_array('imported', $statuses) && !in_array('not imported', $statuses) ){

                $companies = $companies->ImportedFromCipa();

            /**************************************
             *  FILTER BY NOT IMPORTED WITH CIPA  *
             *************************************/
            }elseif( in_array('not imported', $statuses) && !in_array('imported', $statuses) ){

                $companies = $companies->notImportedFromCipa();

            }

            /******************************************
             *  FILTER BY RECENTLY UPDATED WITH CIPA  *
             *****************************************/

            if( in_array('recently updated', $statuses) ){

                $companies = $companies->recentlyUpdatedWithCipa();

            }

            /**********************************
             *  FILTER BY OUTDATED WITH CIPA  *
             *********************************/

            if( in_array('outdated', $statuses) ){

                $companies = $companies->outdatedWithCipa();

            }

            /*****************************************
             *  FILTER BY COMPLIANT / NOT COMPLIANT  *
             *****************************************/

            //  If we want only compliant companies and not non-compliant companies
            if( in_array('compliant', $statuses) && !in_array('not compliant', $statuses) ){

                $companies = $companies->compliant();

            //  If we want only non-compliant companies and not compliant companies
            }elseif( in_array('not compliant', $statuses) && !in_array('compliant', $statuses) ){

                $companies = $companies->notCompliant();

            }

            /**********************************
             *  FILTER BY DISSOLUTION DATE    *
             *********************************/

            if( in_array('dissolution date', $statuses) && isset($data['dissolution_start_date']) && !empty($data['dissolution_start_date'])){

                $start_date = $data['dissolution_start_date'];

                $companies = $companies->dissolutionDate($start_date, null);

            }

            if( in_array('dissolution date', $statuses) && isset($data['dissolution_end_date']) && !empty($data['dissolution_end_date'])){

                $end_date = $data['dissolution_end_date'];

                $companies = $companies->dissolutionDate(null, $end_date);

            }

            /************************************
             *  FILTER BY INCORPORATION DATE    *
             ***********************************/

            if( in_array('incorporation date', $statuses) && isset($data['incorporation_start_date']) && !empty($data['incorporation_start_date'])){

                $start_date = $data['incorporation_start_date'];

                $companies = $companies->incorporationDate($start_date, null);

            }

            if( in_array('incorporation date', $statuses) && isset($data['incorporation_end_date']) && !empty($data['incorporation_end_date'])){

                $end_date = $data['incorporation_end_date'];

                $companies = $companies->incorporationDate(null, $end_date);

            }

            /************************************
             *  FILTER BY RE-REGISTRATION DATE  *
             ***********************************/

            if( in_array('re-registration date', $statuses) && isset($data['re_registration_start_date']) && !empty($data['re_registration_start_date'])){

                $start_date = $data['re_registration_start_date'];

                $companies = $companies->reRegistrationDate($start_date, null);

            }

            if( in_array('re-registration date', $statuses) && isset($data['re_registration_end_date']) && !empty($data['re_registration_end_date'])){

                $end_date = $data['re_registration_end_date'];

                $companies = $companies->reRegistrationDate(null, $end_date);

            }

            /**********************************************
             *  FILTER BY ANNUAL RETURN LAST FILLED DATE  *
             *********************************************/

            if( in_array('a-r last filed date', $statuses) && isset($data['annual_return_last_filed_start_date']) && !empty($data['annual_return_last_filed_start_date'])){

                $start_date = $data['annual_return_last_filed_start_date'];

                $companies = $companies->annualReturnLastFiledDate($start_date, null);

            }

            if( in_array('a-r last filed date', $statuses) && isset($data['annual_return_last_filed_end_date']) && !empty($data['annual_return_last_filed_end_date'])){

                $end_date = $data['annual_return_last_filed_end_date'];

                $companies = $companies->annualReturnLastFiledDate(null, $end_date);

            }

            /*******************************************
             *  FILTER BY ANNUAL RETURN FILLING MONTH  *
             *******************************************/

            if( in_array('a-r filling month', $statuses) && isset($data['annual_return_filing_month']) && !empty($data['annual_return_filing_month'])){

                $month_number = $data['annual_return_filing_month'];

                $companies = $companies->annualReturnFilingMonth($month_number);

            }

        }

        //  Return the companies
        return $companies;
    }

    /**
     *  This method sorts the companies
     */
    public function sortResources($data = [], $companies)
    {
        //  Set the sort by e.g "incorporation_date"
        $sort_by = $data['sort_by'] ?? null;

        //  Set the sort by type e.g "desc"
        $sort_by_type = $data['sort_by_type'] ?? null;

        if($sort_by && $sort_by_type){

            if( $sort_by_type == 'asc' ){

                return $companies->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' ASC');

            }elseif( $sort_by_type == 'desc' ){

                return $companies->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' DESC');

            }

        }

        //  By default sort by the incorporation date
        return $companies->latest('incorporation_date');

    }

    /**
     *  This method exports a list of companies
     */
    public function exportResources($data = [])
    {
        try {

            //  Get the companies
            $companies = $this->getResources($data, null, null);

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            if( isset($data['export_type']) && !empty($data['export_type']) ){

                //  Set the "export_type"
                $export_type = $data['export_type'];

            }else{

                //  Set the "export_type"
                $export_type = 'csv';

            }

            //  Set the file name e.g "companies.csv"
            $file_name = 'companies.'.$export_type;

            //  Download the excel data
            return Excel::download(new CompaniesExport($companies), $file_name);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method imports a list of companies
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
     *  This method returns a single company
     */
    public function getResource($id)
    {
        try {

            //  Get the resource
            $company = \App\Models\Company::where('id', $id)->first() ?? null;

            //  If exists
            if ($company) {

                //  Return company
                return $company;

            } else {

                //  Return "Not Found" Error
                return help_resource_not_found();

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method deletes a single company
     */
    public function deleteResource($user = null)
    {
        try {

            //  Verify permissions
            $this->forceDeleteResourcePermission($user);

            /**
             *  Delete the resource
             */
            return $this->delete();


        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method updates a single company with the latest CIPA updates
     */
    public function requestCipaUpdate($return = true)
    {
        try {

            //  Request the CIPA company template structure
            $template = $this->requestCipaTemplate($this->uin);

            //  If we have the company template (Means company was found on CIPA side)
            if( $template ){

                //  Update the current company instance
                $this->update($template);

                //  Create or update the company registered office address
                $this->createOrUpdateResourceAddress(
                    $template['registered_office_address'],
                    'registered_office_address', $this->id, $this->resource_type
                );

                //  Create or update the company postal address
                $this->createOrUpdateResourceAddress(
                    $template['postal_address'],
                    'postal_address', $this->id, $this->resource_type
                );

                //  Create or update the principal place of business address
                $this->createOrUpdateResourceAddress(
                    $template['principal_place_of_business'],
                    'principal_place_of_business', $this->id, $this->resource_type
                );

                //  If this company is marked as a client
                if( $this->marked_as_client ){

                    \Illuminate\Support\Facades\Log::debug('DO directors');

                    //  Create or update the directors
                    $this->createOrUpdateResourceDirectors($template['directors']);

                    \Illuminate\Support\Facades\Log::debug('DO shareholders');

                    //  Create or update the shareholders
                    $this->createOrUpdateResourceShareholders($template['shareholders']);

                    \Illuminate\Support\Facades\Log::debug('DO ownership bundles');

                    //  Create or update the ownership bundles
                    $this->createOrUpdateResourceOwnershipBundles($template['ownership_bundles']);

                    \Illuminate\Support\Facades\Log::debug('DO secretaries');

                    //  Create or update the secretaries
                    $this->createOrUpdateResourceSecretaries($template['secretaries']);

                }

                //  If we should return an instance
                if( $return ){

                    //  Return a fresh instance
                    return $this->fresh();

                }

            //  If we don't have the company template (Means company was not found on CIPA side)
            }else{

                //  Set all fields to null
                $template = collect($this->getFillable())->mapWithKeys(function ($field) {
                    return [$field => null];
                })->all();

                //  Mark the company status as Not Found
                $template = array_merge($template, [
                    'uin' => $this->uin,
                    'company_status' => 'Not Found',
                    'cipa_updated_at' => \Carbon\Carbon::now(),
                    'marked_as_client' => $this->marked_as_client
                ]);

                //  Mark the company status as Not Found
                $this->update($template);

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method creates or updates a company address
     */
    public function createOrUpdateResourceAddress($data, $type, $owner_id, $owner_type)
    {
        if( !empty($data) ){

            /****************************************
             *  CREATE / UPDATE REGION              *
             ****************************************/

             // If the region code is specified
            if( !empty($data['region_code']) ){

                $region = $this->createOrUpdateResourceAddressRegion($data['region_code']);

            }

            /****************************************
             *  CREATE / UPDATE COUNTRY             *
             ****************************************/

             // If the country code is specified
            if( !empty($data['country_code']) ){

                $country = $this->createOrUpdateResourceAddressCountry($data['country_code']);

            }

            $identifiers = [
                'type' => $type,
                'owner_id' => $owner_id,
                'owner_type' => $owner_type,
            ];

            //  Merge the address type and ownership details
            $template = array_merge(
                $data,
                $identifiers,
                [
                    'country_id' => $country->id ?? null,
                    'region_id' => $region->id ?? null
                ]
            );

            Address::updateOrCreate(
                /**
                 *  Ideally we would like to update by the referencing the cipa_identifier as follows:
                 *
                 *  Where cipa_identifier = $template['cipa_identifier']
                 *
                 *  But the identifier keeps changing value so we must use the following instead:
                 *
                 *  Where type = $template['type'] and
                 *  Where owner_id = $template['owner_id'] and
                 *  Where owner_type = $template['owner_type']
                 */
                $identifiers,

                //  Update or Create a record with this Array of key/values
                $template

            );

        }

    }

    /**
     *  This method creates or updates an address region
     */
    public function createOrUpdateResourceAddressRegion($region_code)
    {
        return Region::updateOrCreate(

            //  Where code = $region_code
            ['code' => $region_code],

            //  Update or Create a record with this Array of key/values
            ['code' => $region_code]

        );
    }

    /**
     *  This method creates or updates an address country
     */
    public function createOrUpdateResourceAddressCountry($country_code)
    {
        return Country::updateOrCreate(

            //  Where code = $country_code
            ['code' => $country_code],

            //  Update or Create a record with this Array of key/values
            [
                'code' => $country_code,
                'name' => (new \App\Models\Country)->getCountryNameFromCode($country_code)
            ]

        );
    }

    /**
     *  This method creates or updates directors
     */
    public function createOrUpdateResourceDirectors($directors = [])
    {
        //  If we have a list of directors
        if( !empty($directors) ){

            //  Foreach director
            foreach ($directors as $director) {

                /****************************************
                 *  CREATE / UPDATE INDIVIDUAL          *
                 ****************************************/

                $individual = $this->createOrUpdateResourceIndividual($director);

                /****************************************
                 *  CREATE / UPDATE DIRECTOR            *
                 ****************************************/

                $identifiers = [
                    'individual_id' => $individual->id,
                    'director_of_company_id' => $this->id
                ];

                //  Set a Director template
                $director_template = array_merge(
                    $director,
                    $identifiers
                );

                Director::updateOrCreate(
                    /**
                     *  Ideally we would like to update by the referencing the cipa_identifier as follows:
                     *
                     *  Where cipa_identifier = $director_template['cipa_identifier']
                     *
                     *  But the identifier keeps changing value so we must use the following instead:
                     *
                     *  Where owner_id = $director_template['individual_id'] and
                     *  Where owner_type = $director_template['director_of_company_id']
                     */
                    $identifiers,

                    //  Update or Create a record with this Array of key/values
                    $director_template
                );

            }

        }
    }

    /**
     *  This method creates or updates shareholders
     */
    public function createOrUpdateResourceShareholders($shareholders)
    {
        //  If we have a list of shareholders
        if( !empty($shareholders) ){

            //  Foreach shareholder
            foreach ($shareholders as $shareholder) {

                //  If this is an individual shareholder
                if( !is_null($shareholder['individual_shareholder']) ){

                    /********************************************
                     *  CREATE / UPDATE INDIVIDUAL SHAREHOLDER  *
                     *******************************************/

                    $this->createOrUpdateResourceIndividualShareholder($shareholder['individual_shareholder']);

                //  If this is an entity shareholder
                }elseif( !is_null($shareholder['entity_shareholder']) ){

                    /********************************************
                     *  CREATE / UPDATE ENTITY SHAREHOLDER      *
                     *******************************************/

                    $this->createOrUpdateResourceEntityShareholder($shareholder['entity_shareholder']);

                }elseif( !is_null($shareholder['other_shareholder']) ){

                    /********************************************
                     *  CREATE / UPDATE ENTITY SHAREHOLDER      *
                     *******************************************/

                    $this->createOrUpdateResourceOtherShareholder($shareholder['other_shareholder']);

                }
            }
        }
    }

    /**
     *  This method creates or updates individual shareholder
     */
    public function createOrUpdateResourceIndividualShareholder($individual_shareholder)
    {
        /****************************************
         *  CREATE / UPDATE INDIVIDUAL          *
         ****************************************/

        $individual = $this->createOrUpdateResourceIndividual($individual_shareholder);

        //  Create / update the shareholder
        $shareholder = $this->createOrUpdateShareholder($individual_shareholder, $individual);

    }

    /**
     *  This method creates or updates entity (Company / Business) shareholder
     */
    public function createOrUpdateResourceEntityShareholder($entity_shareholder)
    {
        /****************************************
         *  CREATE / UPDATE ENTITY              *
         ****************************************/

        $entity = $this->createOrUpdateResourceEntity($entity_shareholder, true);

        //  Create / update the shareholder
        $shareholder = $this->createOrUpdateShareholder($entity_shareholder, $entity);

    }

    /**
     *  This method creates or updates other shareholder (An Organisation e.g Trust, Union, e.t.c)
     */
    public function createOrUpdateResourceOtherShareholder($organisation_shareholder)
    {
        /****************************************
         *  CREATE / UPDATE ENTITY              *
         ****************************************/

        $organisation = $this->createOrUpdateResourceOrganisation($organisation_shareholder);

        //  Create / update the shareholder
        $shareholder = $this->createOrUpdateShareholder($organisation_shareholder, $organisation);

    }

    /**
     *  This method creates or updates other shareholder (An Organisation e.g Trust, Union, e.t.c)
     */
    public function createOrUpdateShareholder($shareholder, $owner)
    {
        if( $owner ){

            /****************************************
             *  CREATE / UPDATE SHAREHOLDER         *
             ****************************************/

            $identifiers = [
                'owner_id' => $owner->id,
                'owner_type' => $owner->resource_type,
                'shareholder_of_company_id' => $this->id
            ];

            $shareholder_template = array_merge(
                $shareholder,
                $identifiers
            );

            Shareholder::updateOrCreate(
                /**
                 *  Ideally we would like to update by the referencing the cipa_identifier as follows:
                 *
                 *  Where cipa_identifier = $shareholder_template['cipa_identifier']
                 *
                 *  But the identifier keeps changing value so we must use the following instead:
                 *
                 *  Where owner_id = $shareholder_template['owner_id'] and
                 *  Where owner_type = $shareholder_template['owner_type'] and
                 *  Where shareholder_of_company_id = $shareholder_template['shareholder_of_company_id']
                 */
                $identifiers,

                //  Update or Create a record with this Array of key/values
                $shareholder_template

            );

        }

    }

    /**
     *  This method creates or updates secretaries
     */
    public function createOrUpdateResourceSecretaries($secretaries)
    {
        //  If we have a list of secretaries
        if( !empty($secretaries) ){

            //  Foreach secretary
            foreach ($secretaries as $secretary) {

                //  If this is an individual secretary
                if( !is_null($secretary['individual_secretary']) ){

                    /********************************************
                     *  CREATE / UPDATE INDIVIDUAL SECRETARY    *
                     *******************************************/

                    $this->createOrUpdateResourceIndividualSecretary($secretary['individual_secretary']);

                //  If this is an entity secretary
                }elseif( !is_null($secretary['entity_secretary']) ){

                    /********************************************
                     *  CREATE / UPDATE ENTITY SECRETARY        *
                     *******************************************/

                    $this->createOrUpdateResourceEntitySecretary($secretary['entity_secretary']);

                }
            }
        }
    }

    /**
     *  This method creates or updates individual secretary
     */
    public function createOrUpdateResourceIndividualSecretary($individual_secretary)
    {
        /****************************************
         *  CREATE / UPDATE INDIVIDUAL          *
         ****************************************/

        $individual = $this->createOrUpdateResourceIndividual($individual_secretary);

        if( $individual ){

            /****************************************
             *  CREATE / UPDATE SECRETARY         *
             ****************************************/

            $identifiers = [
                'owner_id' => $individual->id,
                'owner_type' => $individual->resource_type,
                'secretary_of_company_id' => $this->id
            ];

            $individual_secretary_template = array_merge(
                $individual_secretary,
                $identifiers
            );

            Secretary::updateOrCreate(
                /**
                 *  Ideally we would like to update by the referencing the cipa_identifier as follows:
                 *
                 *  Where cipa_identifier = $individual_secretary_template['cipa_identifier']
                 *
                 *  But the identifier keeps changing value so we must use the following instead:
                 *
                 *  Where owner_id = $individual_secretary_template['owner_id'] and
                 *  Where owner_type = $individual_secretary_template['owner_type'] and
                 *  Where secretary_of_company_id = $individual_secretary_template['secretary_of_company_id']
                 */
                $identifiers,

                //  Update or Create a record with this Array of key/values
                $individual_secretary_template

            );

        }

    }

    /**
     *  This method creates or updates entity (Company / Business) secretary
     */
    public function createOrUpdateResourceEntitySecretary($entity_secretary)
    {
        /****************************************
         *  CREATE / UPDATE ENTITY              *
         ****************************************/

        $entity = $this->createOrUpdateResourceEntity($entity_secretary);

        if( $entity ){

            /****************************************
             *  CREATE / UPDATE SECRETARY         *
             ****************************************/

            $identifiers = [
                'owner_id' => $entity->id,
                'owner_type' => $entity->resource_type,
                'secretary_of_company_id' => $this->id
            ];

            $entity_secretary_template = array_merge(
                $entity_secretary,
                $identifiers
            );

            Secretary::updateOrCreate(
                /**
                 *  Ideally we would like to update by the referencing the cipa_identifier as follows:
                 *
                 *  Where cipa_identifier = $entity_secretary_template['cipa_identifier']
                 *
                 *  But the identifier keeps changing value so we must use the following instead:
                 *
                 *  Where owner_id = $entity_secretary_template['owner_id'] and
                 *  Where owner_type = $entity_secretary_template['owner_type'] and
                 *  Where secretary_of_company_id = $entity_secretary_template['secretary_of_company_id']
                 */
                $identifiers,

                //  Update or Create a record with this Array of key/values
                $entity_secretary_template

            );

        }

    }

    /**
     *  This method creates or updates ownership bundles
     */
    public function createOrUpdateResourceOwnershipBundles($ownership_bundles)
    {
        //  If we have a list of ownership bundles
        if( !empty($ownership_bundles) ){

            //  Calculate the total number of shares
            $total_shares = collect($ownership_bundles)->map(function($ownership_bundle){
                return $ownership_bundle['number_of_shares'];
            })->sum();

            /**
             *  Load the Company Model shareholder and director relationships
             *  excluding the nested addresses that are normally loaded by
             *  default.
             */
            $this->load(['directors.individual','shareholders.owner']);

            /**
             *  Track the number of times the same shareholder name appears
             */
            $occurances = [];

            /**
             *  Foreach ownership bundle
             *
             *  $ownership_bundle structure:
             *
             *  {
             *      "cipa_identifier":"c5bb5bb15474114d",
             *      "number_of_shares":"100",
             *      "ownership_type":"individual",
             *      "owners":{
             *          "owner":{
             *              "cipa_identifier":"9cea7d7178ab7193",
             *              "shareholder_name":"Katharine Hewitt "
             *          }
             *      }
             *  }
             */

            foreach ($ownership_bundles as $ownership_bundle) {

                /**
                 *  Make sure we have the owners specified. Sometimes this can be null e.g
                 *
                 *  {
                 *      "cipa_identifier":"8d380f59efa1ec48",
                 *      "number_of_shares":null,
                 *      "ownership_type":null,
                 *      "owners":null
                 *  }
                 */

                if( is_null($ownership_bundle['owners']) == false ){

                    /****************************************
                     *  CREATE / UPDATE OWNERSHIP BUNDLE    *
                     ****************************************/

                    $shareholder_name = $ownership_bundle['owners']['owner']['shareholder_name'];

                    /**
                     *  Check if this shareholder is the same as the company name.
                     *  This would mean that the copmany is a shareholder to itself,
                     *  which is strange but happens with the data we receive.
                     */
                    $is_shareholder_to_self = ($shareholder_name == $this->name);

                    \Illuminate\Support\Facades\Log::debug('Ownership Bundle For: '. $shareholder_name);

                    //  Find the matching shareholder
                    $matched_shareholders = collect($this->shareholders)->filter(function($shareholder) use ($shareholder_name){

                        //  If the owner is a company or organisation
                        if( in_array($shareholder->owner_type, ['company', 'organisation', 'business']) ){

                            return ($shareholder_name == $shareholder->owner->name);

                        //  If the owner is an individual
                        }elseif( $shareholder->owner_type == 'individual' ){

                            return ($shareholder_name == $shareholder->owner->full_name);

                        }

                        return false;

                    });

                    //  Retrieve the matching shareholder id
                    $shareholder_id = count($matched_shareholders) ? $matched_shareholders->first()->id : null;

                    //  Find the matching director
                    $matched_directors = collect($this->directors)->filter(function($director) use ($shareholder_name){

                        //  If we have the linked individual
                        if( $director->individual ){

                            return $shareholder_name == $director->individual->full_name;

                        }

                        return false;

                    });

                    //  Retrieve the matching director id
                    $director_id = count($matched_directors) ? $matched_directors->first()->id : null;

                    $number_of_shares = $ownership_bundle['number_of_shares'];

                    /**
                     *  Handle duplicate occurances of the same shareholder.
                     *  Some shareholders can be duplicated, so we want to
                     *  count the total number of duplicate occurances of
                     *  the same shareholder and add up their number of
                     *  shares.
                     */
                    if( isset($occurances[$shareholder_id]) ){

                        $total_occurances = ($occurances[$shareholder_id]['total_occurances'] + 1);

                        $total_number_of_shares = ($occurances[$shareholder_id]['total_number_of_shares'] + $number_of_shares);

                        //  Set the number of occurances and number of shares for this duplciate shareholder
                        $occurances[$shareholder_id] = [
                            'total_occurances' => $total_occurances,
                            'total_number_of_shares' => $total_number_of_shares
                        ];

                    }else{

                        //  Set the number of occurances for this shareholder
                        $occurances[$shareholder_id] = [
                            'total_occurances' => 1,
                            'total_number_of_shares' => $number_of_shares
                        ];

                    }

                    \Illuminate\Support\Facades\Log::debug('OCCURANCES');
                    \Illuminate\Support\Facades\Log::debug( json_encode($occurances) );

                    $identifiers = [
                        'shareholder_name' => $shareholder_name,
                        'shareholder_of_company_id' => $this->id
                    ];

                    $ownership_bundle_template = array_merge(
                        $ownership_bundle,
                        $identifiers,
                        [
                            'director_id' => $director_id,
                            'total_shares' => $total_shares,
                            'shareholder_id' => $shareholder_id,
                            'is_shareholder_to_self' => $is_shareholder_to_self,
                            'number_of_shares' => $occurances[$shareholder_id]['total_number_of_shares'],
                            'total_shareholder_occurances' => $occurances[$shareholder_id]['total_occurances'],
                            'percentage_of_shares' => round($occurances[$shareholder_id]['total_number_of_shares'] / $total_shares * 100, 2)
                        ]
                    );

                    //  Create / Update the Ownership Bundle
                    OwnershipBundle::updateOrCreate(
                        /**
                         *  Ideally we would like to update by the referencing the cipa_identifier as follows:
                         *
                         *  Where cipa_identifier = $ownership_bundle_template['cipa_identifier']
                         *
                         *  But the identifier keeps changing value so we must use the following instead:
                         *
                         *  Where shareholder_name = $ownership_bundle_template['shareholder_name'] and
                         *  Where shareholder_of_company_id = $ownership_bundle_template['shareholder_of_company_id']
                         */
                        $identifiers,

                        //  Update or Create a record with this Array of key/values
                        $ownership_bundle_template

                    );

                }

            }
        }
    }

    /**
     *  This method creates or updates individual
     */
    public function createOrUpdateResourceIndividual($individual_template)
    {

        /****************************************
         *  CREATE / UPDATE INDIVIDUAL          *
         ****************************************/

        $identifiers = [
            'first_name' => $individual_template['individual_name']['first_name'],
            'middle_names' => $individual_template['individual_name']['middle_names'],
            'last_name' => $individual_template['individual_name']['last_name']
        ];

        \Illuminate\Support\Facades\Log::debug('IDENTIFIES');
        \Illuminate\Support\Facades\Log::debug( json_encode($identifiers) );

        \Illuminate\Support\Facades\Log::debug('FIND');
        \Illuminate\Support\Facades\Log::debug( Individual::where($identifiers)->first() );

        //  Create / Update the Individual
        $individual = Individual::updateOrCreate(
            /**
             *  Ideally we would like to update by the referencing the cipa_identifier as follows:
             *
             *  Where cipa_identifier = $individual_template['individual_name']['cipa_identifier']]
             *
             *  But the identifier keeps changing value so we must use the following instead:
             *
             *  Where first_name = $individual_template['individual_name']['first_name'] and
             *  Where middle_names = $individual_template['individual_name']['middle_names'] and
             *  Where last_name = $individual_template['individual_name']['last_name']
             */
            $identifiers,

            //  Update or Create a record with this Array of key/values
            $individual_template['individual_name']

        );

        //  If we have the individual residential address
        if( !empty($individual_template['residential_address']) ){

            //  Create or update the individual residential address
            $this->createOrUpdateResourceAddress(
                $individual_template['residential_address'],
                'residential_address', $individual->id, $individual->resource_type
            );

        }

        //  If we have the individual postal address
        if( !empty($individual_template['postal_address']) ){

            //  Create or update the individual postal address
            $this->createOrUpdateResourceAddress(
                $individual_template['postal_address'],
                'postal_address', $individual->id, $individual->resource_type
            );

        }

        return $individual;
    }

    /**
     *  This method creates or updates entity (Company / Business)
     */
    public function createOrUpdateResourceEntity($entity_template, $force_as_company = false)
    {

        $name = $entity_template['name'];

        //  If we have a UIN or the Company name is the same or we force as a company, then this is a Company
        if( !is_null($entity_template['uin']) || ($this->name == $name) || $force_as_company == true){

            /****************************************
             *  CREATE / UPDATE COMPANY             *
             ****************************************/

            //  If the company name matches, then use the current company uin otherwise the entity uin
            $uin = ($this->name == $name) ? $this->uin : $entity_template['uin'];

            //  If we have a company uin
            if( $uin ){

                //  Identify using the company uin
                $identifiers = [
                    'uin' => $uin
                ];

            }else{

                //  Identify using the company name
                $identifiers = [
                    'name' => $name
                ];

            }

            //  Create / Update the Company
            $entity = Company::updateOrCreate(

                //  Where uin = $uin or name = $name
                $identifiers,

                //  Update or Create a record with this Array of key/values
                $entity_template

            );

        //  If we don't have a UIN, then this is a Business
        }else{

            /****************************************
             *  CREATE / UPDATE BUSINESS            *
             ****************************************/

            $identifiers = [
                'name' => $entity_template['name']
            ];

            //  Create / Update the Business
            $entity = Business::updateOrCreate(

                //  Where uin = $uin
                $identifiers,

                //  Update or Create a record with this Array of key/values
                $entity_template
            );

        }

        //  If we have the company/business registered office address
        if( !empty($entity_template['registered_office_address']) ){

            //  Create or update the company/business registered office address
            $this->createOrUpdateResourceAddress(
                $entity_template['registered_office_address'],
                'registered_office_address', $entity->id, $entity->resource_type
            );

        }

        //  If we have the company/business postal address
        if( !empty($entity_template['postal_address']) ){

            //  Create or update the company/business postal address
            $this->createOrUpdateResourceAddress(
                $entity_template['postal_address'],
                'postal_address', $entity->id, $entity->resource_type
            );

        }

        return $entity;
    }

    /**
     *  This method creates or updates organisation (Trust, Union, e.t.c)
     */
    public function createOrUpdateResourceOrganisation($organisation_template)
    {
        /****************************************
         *  CREATE / UPDATE ORGANISATION        *
         ****************************************/

        /**
         *  Example structure of $organisation_template
         *
         *  {
         *      "cipa_identifier":"7ecd5f3776586442",
         *      "registration_number":null,
         *      "name":"Accuro Trust Mauritius Limited - Trustee For The Lucozo Trust",
         *      "country_code":"MU",
         *      "registered_office_address":{
         *          "cipa_identifier":"d5ce89117b83c1dd",
         *          "care_of":null,
         *          "line_1":"Level 8c, Cyber Tower I I",
         *          "line_2":null,
         *          "region_code":"Ebene Cybercity",
         *          "post_code":"72201",
         *          "country_code":"MU",
         *          "start_date":"2010-08-12 00:00:00",
         *          "end_date":null
         *      },
         *      "postal_office_address":{
         *          "cipa_identifier":"3456bf700bfcccd5",
         *          "care_of":null,
         *          "line_1":"Level 8c, Cyber Tower I I",
         *          "line_2":null,
         *          "region_code":"Ebene Cybercity",
         *          "post_code":"72201",
         *          "country_code":"MU",
         *          "start_date":"2010-08-12 00:00:00",
         *          "end_date":null
         *      },
         *      "appointment_date":"2012-10-31 00:00:00",
         *      "ceased_date":null,
         *      "nominee":false
         *  }
         */

        // If the country code is specified
        if( !empty($organisation_template['country_code']) ){

            $country = $this->createOrUpdateResourceAddressCountry($organisation_template['country_code']);

        }

        $identifiers = [
            'name' => $organisation_template['name']
        ];

        //  Merge the address type and ownership details
        $organisation_template = array_merge(
            $organisation_template,
            [
                'country_id' => $country->id ?? null
            ]
        );

        //  Create / Update the Organisation
        $organisation = Organisation::updateOrCreate(

            //  Where uin = $uin
            $identifiers,

            //  Update or Create a record with this Array of key/values
            $organisation_template

        );

        //  If we have the organisation registered office address
        if( !empty($organisation_template['registered_office_address']) ){

            //  Create or update the organisation registered office address
            $this->createOrUpdateResourceAddress(
                $organisation_template['registered_office_address'],
                'registered_office_address', $organisation->id, $organisation->resource_type
            );

        }

        //  If we have the organisation postal address
        if( !empty($organisation_template['postal_office_address']) ){

            //  Create or update the organisation postal address
            $this->createOrUpdateResourceAddress(
                $organisation_template['postal_office_address'],
                'postal_office_address', $organisation->id, $organisation->resource_type
            );

        }

        return $organisation;
    }

    /**
     *  This method searches the CIPA database for a single company matching the given search term
     */
    public function requestCipaSearch($data = [])
    {
        try {

            //  Extract the Request Object data (CommanTraits)
            $data = $this->extractRequestData($data);

            //  Set the search term e.g "BW00001234567"
            $search_term = $data['search'];

            //  Request the CIPA company template structure
            $template = $this->requestCipaTemplate($search_term);

            //  If we have the company template (Means company was found on CIPA side)
            if( $template ){

                //  Return a Collection of the Company Eloquent Model (Created on the fly - Not stored in database)
                return collect(new \App\Models\Company($template));

            //  If we don't have the company template (Means company was not found on CIPA side)
            }else{

                //  Return an Empty Collection
                return collect(new \App\Models\Company);

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method returns the data template of a single company from CIPA
     */
    public function requestCipaTemplate($uin)
    {
        try {

            //  Set Auth Credentials
            $username = 'apiBursR2bc6JhrY1iyFVQNWdoZ845H';
            $password = '15EKveY1US572yrycjaw5zoBBim1NQpH';

            //  Set Endpoint
            $url = 'https://suppre.cipa.support.fostermoore.com/ng-cipa-companies/soap/viewCompanyWS.wsdl';

            // Run API Call With Basic Authentication
            $cipaCompany = Soap::to($url)
                                ->withBasicAuth($username, $password)
                                    ->viewCompanyWS(['TxnBusinessIdentifier' => $uin]);

            //  If we have the company details
            if( isset( $cipaCompany->response ) ){

                $cipaCompany = $cipaCompany->response->BursCompanyView;

                $template = [
                    'details' => $cipaCompany,
                    'cipa_updated_at' => \Carbon\Carbon::now()
                ];

                $cipaCompanyFields = [
                    'Info' => 'info',
                    'CompanyName' => 'name',
                    'CompanyStatus' => 'company_status',
                    'Exempt' => 'exempt',
                    'ForeignCompany' => 'foreign_company',
                    'CompanyType' => 'company_type',
                    'CompanySubType' => 'company_sub_type',
                    'IncorporationDate' => 'incorporation_date',
                    'ReRegistrationDate' => 're_registration_date',
                    'OldCompanyNumber' => 'old_company_number',
                    'DissolutionDate' => 'dissolution_date',
                    'OwnConstitutionYn' => 'own_constitution_yn',
                    'BusinessSector' => 'business_sector',
                    'AnnualReturnFilingMonth' => 'annual_return_filing_month',
                    'ARLastFiledDate' => 'annual_return_last_filed_date',

                    'RegisteredOfficeAddressDetail.RegisteredOfficeAddress' => $this->cipaAddressFieldsTemplate('registered_office_address'),
                    'PostalAddressDetail.PostalAddress' => $this->cipaAddressFieldsTemplate('postal_address'),
                    'PrincipalPlaceOfBusinessDetail.PrincipalPlaceOfBusiness' => $this->cipaAddressFieldsTemplate('principal_place_of_business'),

                    'OwnershipBundles.OwnershipBundle' => $this->cipaOwnershipBundlesFieldsTemplate(),
                    'DirectorDetails.IndividualDirector' => $this->cipaDirectorDetailsFieldsTemplate(),
                    'ShareholderDetails.Shareholder' => $this->cipaShareholderDetailsFieldsTemplate(),
                    'SecretaryDetails.Secretary' => $this->cipaSecretaryDetailsFieldsTemplate(),
                ];

                $template = $this->makeTemplateFromCipaFields($template, $cipaCompanyFields, $cipaCompany, $this->getDates());

                //  Return the company template
                return $template;

            }else{

                //  Return null for company not found
                return null;

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method generates address template structure
     */
    public function cipaAddressFieldsTemplate($name = null)
    {
        return [
            'name' => $name,                                //  Make sure this is the new name
            'dates' => ['start_date', 'end_date'],          //  Convert the following into dates
            'fields' => [
                'identifier' => 'cipa_identifier',
                'CareOf' => 'care_of',
                'Line1' => 'line_1',
                'Line2' => 'line_2',
                'RegionCode' => 'region_code',
                'PostCode' => 'post_code',
                'Country' => 'country_code',
                'StartDate' => 'start_date',
                'EndDate' => 'end_date'
            ]
        ];
    }

    /**
     *  This method generates individual name template structure
     */
    public function cipaIndividualNameFieldsTemplate()
    {
        return [
            'name' => 'individual_name',
            'fields' => [
                'identifier' => 'cipa_identifier',
                'FirstName' => 'first_name',
                'MiddleNames' => 'middle_names',
                'LastName' => 'last_name'
            ]
        ];
    }

    /**
     *  This method generates ownership bundles template structure
     */
    public function cipaOwnershipBundlesFieldsTemplate()
    {
        return [
            'name' => 'ownership_bundles',
            'fields' => [
                'identifier' => 'cipa_identifier',
                'NumberOfShares' => 'number_of_shares',
                'OwnershipType' => 'cipa_ownership_type',
                'Owners' => [
                    'name' => 'owners',
                    'fields' => [
                        'Owner' => [
                            'name' => 'owner',
                            'fields' => [
                                'identifier' => 'cipa_identifier',
                                'ShareholderName' => 'shareholder_name'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     *  This method generates directors template structure
     */
    public function cipaDirectorDetailsFieldsTemplate()
    {
        return [
            'name' => 'directors',
            'fields' => [
                'identifier' => 'cipa_identifier',
                'IndividualName' => $this->cipaIndividualNameFieldsTemplate(),
                'ResidentialAddress' => $this->cipaAddressFieldsTemplate('residential_address'),
                'PostalAddress' => $this->cipaAddressFieldsTemplate('postal_address'),
                'AppointmentDate' => 'appointment_date',
                'CeasedDate' => 'ceased_date'
            ],
            'dates' => ['appointment_date', 'ceased_date']
        ];
    }

    /**
     *  This method generates shareholders template structure
     */
    public function cipaShareholderDetailsFieldsTemplate()
    {
        return [
            'name' => 'shareholders',
            'fields' => [
                'identifier' => 'cipa_identifier',

                //  This can be an Individual Shareholder
                'IndividualShareholder' => [
                    'name' => 'individual_shareholder',
                    'fields' => [
                        'identifier' => 'cipa_identifier',
                        'IndividualName' => $this->cipaIndividualNameFieldsTemplate(),
                        'ResidentialAddress' => $this->cipaAddressFieldsTemplate('residential_address'),
                        'PostalAddress' => $this->cipaAddressFieldsTemplate('postal_address'),
                        'AppointmentDate' => 'appointment_date',
                        'CeasedDate' => 'ceased_date',
                        'Nominee' => 'nominee'
                    ],
                    'dates' => ['appointment_date', 'ceased_date']
                ],

                //  This can be an Entity Shareholder e.g Company
                'EntityShareholder' => [
                    'name' => 'entity_shareholder',
                    'fields' => [
                        'identifier' => 'cipa_identifier',
                        'UIN' => 'uin',
                        'CompanyName' => 'name',
                        'RegisteredOfficeAddress' => $this->cipaAddressFieldsTemplate('registered_office_address'),
                        'PostalAddress' => $this->cipaAddressFieldsTemplate('postal_address'),
                        'AppointmentDate' => 'appointment_date',
                        'CeasedDate' => 'ceased_date',
                        'Nominee' => 'nominee'
                    ],
                    'dates' => ['appointment_date', 'ceased_date']
                ],

                //  This can be an Other Shareholder e.g Trust or Union
                'OtherShareholder' => [
                    'name' => 'other_shareholder',
                    'fields' => [
                        'identifier' => 'cipa_identifier',
                        'RegistrationNumber' => 'registration_number',
                        'Name' => 'name',
                        'CountryOfRegistration' => 'country_code',
                        'RegisteredOfficeAddress' => $this->cipaAddressFieldsTemplate('registered_office_address'),
                        'PostalOfficeAddress' => $this->cipaAddressFieldsTemplate('postal_office_address'),
                        'AppointmentDate' => 'appointment_date',
                        'CeasedDate' => 'ceased_date',
                        'Nominee' => 'nominee'
                    ],
                    'dates' => ['appointment_date', 'ceased_date']
                ],
            ]
        ];
    }

    /**
     *  This method generates shareholders template structure
     */
    public function cipaSecretaryDetailsFieldsTemplate()
    {
        return [
            'name' => 'secretaries',
            'fields' => [
                'identifier' => 'cipa_identifier',

                //  This can be an Individual Secretary
                'IndividualSecretary' => [
                    'name' => 'individual_secretary',
                    'fields' => [
                        'identifier' => 'cipa_identifier',
                        'IndividualName' => $this->cipaIndividualNameFieldsTemplate(),
                        'ResidentialAddress' => $this->cipaAddressFieldsTemplate('residential_address'),
                        'PostalAddress' => $this->cipaAddressFieldsTemplate('postal_address'),
                        'AppointmentDate' => 'appointment_date',
                        'CeasedDate' => 'ceased_date'
                    ],
                    'dates' => ['appointment_date', 'ceased_date']
                ],

                //  This can be an 'Entity Secretary e.g Company
                'EntitySecretary' => [
                    'name' => 'entity_secretary',
                    'fields' => [
                        'identifier' => 'cipa_identifier',
                        'UIN' => 'uin',
                        'CompanyName' => 'name',
                        'RegisteredOfficeAddress' => $this->cipaAddressFieldsTemplate('registered_office_address'),
                        'PostalAddress' => $this->cipaAddressFieldsTemplate('postal_address'),
                        'AppointmentDate' => 'appointment_date',
                        'CeasedDate' => 'ceased_date'
                    ],
                    'dates' => ['appointment_date', 'ceased_date']
                ],
            ]
        ];
    }

    /**
     *  This method generates templates from CIPA fields
     */
    public function makeTemplateFromCipaFields($template = [], $cipaFields, $cipaData, $dates = [])
    {
        foreach($cipaFields as $originalFieldName => $newFieldName){

            /**
             *  If the new field name is a type of Array e.g
             *
             *  'PostalAddressDetail.PostalAddress' => [
             *      'name' => 'postal_address',
             *      'fields' => $this->cipaAddressFieldsTemplate()
             *  ]
             *
             *  Note that;
             *
             *  $originalFieldName = 'PostalAddressDetail.PostalAddress'
             *
             *  $newFieldName = [
             *      'name' => 'postal_address',
             *      'fields' => $this->cipaAddressFieldsTemplate()
             *  ]
             *
             *  We must then extract the name and the sub template
             */

            //  Unset the $subFields and $subDates
            unset($subFields);
            unset($subDates);

            if( is_array($newFieldName) ){

                $newFieldName = $cipaFields[$originalFieldName]['name'];
                $subFields = $cipaFields[$originalFieldName]['fields'];

                if( isset($cipaFields[$originalFieldName]['dates']) ){

                    $subDates = $cipaFields[$originalFieldName]['dates'];

                }else{

                    $subDates = [];

                }

            }

            /**
             *  $originalFieldName = "CompanyName" or "DirectorDetails.IndividualDirector"
             *
             *  $fields = ['CompanyName'] or ['DirectorDetails', 'IndividualDirector']
             */
            $fields = explode('.', $originalFieldName);

            //  Set the field value to the entire company info { ... }
            $fieldValue = $cipaData;

            //  Foreach field
            for ($i=0; $i < count($fields); $i++) {

                //  The current field
                $field = $fields[$i];

                /**
                 *  Check if the field exists:
                 *
                 *  1) isset( $cipaCompany->DirectorDetails ) = true/false
                 *  2) isset( $cipaCompany->DirectorDetails->IndividualDirector ) = true/false
                 *  ... e.t.c
                 */
                if( isset( $fieldValue->{$field} ) ){

                    //  If the field value is empty (Is equal to an empty Object {})
                    if( $fieldValue->{$field} == new \stdClass() ){

                        //  Reset the field value to null since the field has no value
                        $fieldValue = null;

                    }else{

                        /**
                         *  Set the field value:
                         *
                         *  1) $fieldValue = $cipaCompany->DirectorDetails
                         *  2) $fieldValue = $cipaCompany->DirectorDetails->IndividualDirector
                         *  ... e.t.c
                         *
                         *  Most field values require that we target the "Value" property in order
                         *  to retrieve the actual information for that given field, however this
                         *  might not be necessary other fields e.g
                         *
                         *  Example 1: Just take the value as it is
                         *
                         *  {
                         *      "someField" : "The value"
                         *  }
                         *
                         *  Example 2: Target the "Value" field and extract the actual value
                         *
                         *  or with nested value
                         *
                         *  {
                         *      "someField" : {
                         *          Value: "The value"
                         *      }
                         *  }
                         *
                         *  Example 3: This will require that we use $subFields to properly re-structure
                         *  the information then extract the values of each field appropriately. This
                         *  is a more advanced structure.
                         *
                         *  {
                         *      "someField" : {
                         *          "anotherField1" : {
                         *              Value: "The 1st value"
                         *          },
                         *          "anotherField2" : {
                         *              Value: "The 2nd value"
                         *          },
                         *          "anotherField3" : {
                         *              Value: "The 3rd value"
                         *          }
                         *      }
                         *  }
                         *
                         *  or with
                         *
                         *  We must cater for both scenerios
                         */

                        //  Handle Example 2 scenerio - If we have the "Value" field then extract the actual value
                        if( isset( $fieldValue->{$field}->Value ) ){

                            //  Capture the value
                            $value = $fieldValue->{$field}->Value;

                            //  If the value is a type of string
                            if( gettype($value) == 'string'){

                                //  Set Null if empty or set original value
                                $fieldValue = (trim($value) === '') ? null : $value;

                            }else{

                                //  Set original value e.g true/false boolean
                                $fieldValue = $value;

                            }

                        }else{

                            /** Handle Example 3 scenerio - If we have nested fields that we would like to also re-structure
                             *  We need to make sure that we only access this part of the logic only if we are on the last
                             *  loop. We can check this by the following logic ($i == (count($fields) - 1). We need to be
                             *  on the last loop to target the final field on the chain e.g
                             *
                             *  $fields = ['someField', 'someField2', 'someField3'];
                             *
                             *  this translates to the following structure
                             *
                             *  someField->someField2->someField3
                             *
                             *  Making sure we are on the last loop means we don't run this logic on:
                             *
                             *  1) someField
                             *  2) someField->someField2
                             *
                             *  But only run on the following
                             *
                             *  3) someField->someField2->someField3
                             *
                             *  Which is the last field to target i.e the last loop
                             *
                             */
                            if( isset($subFields) && ($i == (count($fields) - 1)) ){

                                //  Capture the data and convert from Object to Array
                                $subFieldsValue = $fieldValue->{$field};

                                /**
                                 *  If this nested value is an Array, it means that we have multiple instances of the
                                 *  same kind of resrouce e.g an Array of Directors or Shareholders. We must make a
                                 *  template out of each instance.
                                 */
                                if( is_array($subFieldsValue) ){

                                    //  Set the fieldValue as an empty Array
                                    $fieldValue = [];

                                    /**
                                     *  Foreach $subFieldsValue e.g Foreach Director / Shareholder
                                     *  Let us make a template using the data
                                     */
                                    foreach ($subFieldsValue as $singleSubFieldsValue) {

                                        /**
                                         *   $singleFieldValue is like a template of a single Director / Shareholder
                                         */
                                        $singleFieldValue = $this->makeTemplateFromCipaFields([], $subFields, $singleSubFieldsValue, $subDates);

                                        /**
                                         *   We must push this single Director / Shareholder with the rest of the others
                                         */
                                        array_push($fieldValue, $singleFieldValue);

                                    }

                                }else{

                                    /** Its possible that we only had one Object { ... } representing a Director / Shareholder.
                                     *  But we don't want to just return the  Object { ... } as it is. Infact we would like to
                                     *  close the Object into an Array even if we only have one Object. Therefore if the field
                                     *  name is "directors", "shareholders" or "secretaries" we must always return the Object
                                     *  enclosed in an Array.
                                     */

                                     if( in_array($newFieldName, ['directors', 'shareholders', 'secretaries', 'ownership_bundles']) ){

                                        $fieldValue = [
                                            $this->makeTemplateFromCipaFields([], $subFields, $subFieldsValue, $subDates)
                                        ];

                                     }else{

                                        $fieldValue = $this->makeTemplateFromCipaFields([], $subFields, $subFieldsValue, $subDates);

                                     }

                                }

                            //  Handle Example 1 scenerio - If we have a simple value just take the value as it is
                            }else{

                                $fieldValue = $fieldValue->{$field};

                            }

                        }

                    }

                }else{

                    //  Reset the field value to null since the field does not exist
                    $fieldValue = null;

                    //  Stop the loop
                    break 1;

                }

            }

            //  Check if the given field name must be cast into a date and we have a value available
            if( in_array( $newFieldName, $dates ) && !empty($fieldValue)){

                //  Convert value to date
                $fieldValue = \Carbon\Carbon::parse($fieldValue)->format('Y-m-d H:i:s');

            }

            //  Capture the template field and value
            $template[$newFieldName] = $fieldValue;

            \Illuminate\Support\Facades\Log::debug($newFieldName .': '.json_encode($fieldValue));

        }

        return $template;
    }



    /**
     *  This method checks permissions for creating a new resource
     */
    public function createResourcePermission($user = null)
    {
        try {

            //  If the user is provided
            if( $user ){

                //  Check if the user is authourized to create the resource
                if ($user->can('create', Company::class) === false) {

                    //  Return "Not Authourized" Error
                    return help_not_authorized();

                }

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method checks permissions for updating an existing resource
     */
    public function updateResourcePermission($user = null)
    {
        try {

            //  If the user is provided
            if( $user ){

                //  Check if the user is authourized to update the resource
                if ($user->can('update', $this)) {

                    //  Return "Not Authourized" Error
                    return help_not_authorized();

                }

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method checks permissions for deleting an existing resource
     */
    public function forceDeleteResourcePermission($user = null)
    {
        try {

            //  If the user is provided
            if( $user ){

                //  Check if the user is authourized to delete the resource
                if ($user->can('forceDelete', $this)) {

                    //  Return "Not Authourized" Error
                    return help_not_authorized();

                }

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method validates creating a new resource
     */
    public function createResourceValidation($data = [])
    {
        try {

            //  Set validation rules
            $rules = [

            ];

            //  Set validation messages
            $messages = [

            ];

            //  Method executed within CommonTraits
            $this->resourceValidation($data, $rules, $messages);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method validates updating an existing resource
     */
    public function updateResourceValidation($data = [])
    {
        try {

            //  Run the resource creation validation
            $this->createResourceValidation($data);

        } catch (\Exception $e) {

            throw($e);

        }

    }

}
