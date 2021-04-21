<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Exports\CompaniesExport;
use App\Imports\CompaniesImport;
use Maatwebsite\Excel\Facades\Excel;
use RicorocksDigitalAgency\Soap\Facades\Soap;
use App\Http\Resources\Company as CompanyResource;
use App\Http\Resources\Companies as CompaniesResource;

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

                $fields = collect((new \App\Models\Company )->getFillable())->reject(function ($value, $key) {
                                return $value == 'details';
                            })->toArray();

                //  Get the companies
                $companies = \App\Models\Company::select($fields);

            }

            //  Filter the companies
            $companies = $this->filterResources($data, $companies);

            //  Sort the companies
            $companies = $this->sortResources($data, $companies);

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

            $companies = $this->filterResourcesBySearch($data, $companies);

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
        //  Set the search term e.g "Bravo Cinema"
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

            $filterByCompanyStatuses = collect($statuses)->filter(function($status){
                return in_array($status, ['registered', 'cancelled', 'removed', 'not found']);
            })->toArray();

            if( count($filterByCompanyStatuses) ){

                $companies = $companies->companyStatus($filterByCompanyStatuses);

            }

            /*******************************
             *  FILTER BY COMPANY TYPE     *
             *******************************/

            $filterByCompanyType = collect($statuses)->filter(function($status){
                return in_array($status, ['private company', 'llc company']);
            })->toArray();

            if( count($filterByCompanyType) ){

                $companies = $companies->companyType($filterByCompanyType);

            }

            /*******************************
             *  FILTER BY COMPANY SUB TYPE *
             *******************************/

            $filterByCompanySubType = collect($statuses)->filter(function($status){
                return in_array($status, ['type a', 'type b']);
            })->toArray();

            if( count($filterByCompanySubType) ){

                $companies = $companies->companySubType($filterByCompanySubType);

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

            //  Set Auth Credentials
            $username = 'apiBursR2bc6JhrY1iyFVQNWdoZ845H';
            $password = '15EKveY1US572yrycjaw5zoBBim1NQpH';

            //  Set Endpoint
            $url = 'https://suppre.cipa.support.fostermoore.com/ng-cipa-companies/soap/viewCompanyWS.wsdl';

            // Run API Call With Basic Authentication
            $cipaCompany = Soap::to($url)
                                ->withBasicAuth($username, $password)
                                    ->viewCompanyWS(['TxnBusinessIdentifier' => $this->uin]);

            //  If we have the company details
            if( isset( $cipaCompany->response ) ){

                $cipaCompany = $cipaCompany->response->BursCompanyView;

                $template = [
                    'details' => $cipaCompany,
                    'cipa_updated_at' => \Carbon\Carbon::now()
                ];

                //  List of company fields we want to capture
                $cipaCompanyFields = [
                    'Info', 'CompanyName', 'CompanyStatus', 'Exempt', 'ForeignCompany', 'CompanyType', 'CompanySubType',
                    'IncorporationDate', 'ReRegistrationDate', 'OldCompanyNumber', 'DissolutionDate', 'OwnConstitutionYn',
                    'BusinessSector', 'AnnualReturnFilingMonth', 'ARLastFiledDate'
                ];

                $changeCompanyFields = [
                    'CompanyName' => 'name',
                    'ARLastFiledDate' => 'annual_return_last_filed_date'
                ];

                //  List of company fields that should be treated as dates
                $cipaCompanyDates = ['IncorporationDate', 'ReRegistrationDate', 'DissolutionDate', 'ARLastFiledDate'];

                foreach($cipaCompanyFields as $cipaCompanyField){

                    //  If the field exists on the comapny record
                    if( isset($cipaCompany->{$cipaCompanyField}) ){

                        //  If the field is empty (Is equal to an empty Object {})
                        if( $cipaCompany->{$cipaCompanyField} == new \stdClass() ){

                            //  Cconvert to Null
                            $cipaCompany->{$cipaCompanyField} = null;

                        }

                        //  If exists in Array of names to change
                        if( array_key_exists($cipaCompanyField, $changeCompanyFields) ){

                            //  Set the database field e.g $db_field = $changeCompanyFields['CompanyName']
                            $db_field = $changeCompanyFields[ $cipaCompanyField ];

                        //  If not Array e.g $cipaCompanyField = IncorporationDate
                        }else{

                            //  Convert to database field e.g "IncorporationDate" to "incorporation_date"
                            $db_field = Str::snake($cipaCompanyField);

                        }

                        //  If this field is empty
                        if( $cipaCompany->{$cipaCompanyField} == null ){

                            //  Capture field and value
                            $template[$db_field] = null;

                        //  If the given field is a date
                        }elseif( in_array($cipaCompany->{$cipaCompanyField}, $cipaCompanyDates) ){

                            //  Convert to valid date and capture field and value
                            $template[$db_field] = \Carbon\Carbon::parse($cipaCompany->{$cipaCompanyField}->Value)->format('Y-m-d H:i:s');

                        }else{

                            //  Capture field and value
                            $template[$db_field] = $cipaCompany->{$cipaCompanyField}->Value;

                        }

                    }

                }

                $this->update($template);

                //  If we should return an instance
                if( $return ){

                    //  Return a fresh instance
                    return $this->fresh();

                }

            }else{

                //  Mark the company status as Not Found
                $this->update([
                    'company_status' => 'Not Found',
                    'cipa_updated_at' => \Carbon\Carbon::now()
                ]);

        }

        } catch (\Exception $e) {

            throw($e);

        }
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
