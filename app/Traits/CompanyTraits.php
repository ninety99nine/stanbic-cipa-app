<?php

namespace App\Traits;

use Carbon\Carbon;
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

                //  Get the companies
                $companies = \App\Models\Company::latest();

            }

            //  Filter the companies
            $companies = $this->filterResources($data, $companies);

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
            $statuses[$key] = ucwords(strtolower(trim($status)));

        }

        if ( $companies && count($statuses) ) {

            /*******************************
             *  FILTER BY COMPANY STATUS   *
             *******************************/

            $filterByCompanyStatuses = collect($statuses)->filter(function($status){
                return in_array($status, ['Registered', 'Cancelled', 'Removed']);
            })->toArray();

            if( count($filterByCompanyStatuses) ){

                $companies = $companies->companyStatus($filterByCompanyStatuses);

            }

            /*******************************
             *  FILTER BY COMPANY TYPE     *
             *******************************/

            $filterByCompanyType = collect($statuses)->filter(function($status){
                return in_array($status, ['Private Company', 'LLC Company']);
            })->toArray();

            if( count($filterByCompanyType) ){

                $companies = $companies->companyType($filterByCompanyType);

            }

            /*******************************
             *  FILTER BY COMPANY SUB TYPE *
             *******************************/

            $filterByCompanySubType = collect($statuses)->filter(function($status){
                return in_array($status, ['Type A', 'Type B']);
            })->toArray();

            if( count($filterByCompanySubType) ){

                $companies = $companies->companySubType($filterByCompanySubType);

            }

            /*******************************
             *  FILTER BY EXEMPT           *
             *******************************/

            if( in_array('Exempt', $statuses) ){

                $companies = $companies->exempt();

            }elseif( in_array('Not Exempt', $statuses) ){

                $companies = $companies->notExempt();

            }

            /*******************************
             *  FILTER BY FOREIGN / LOCAL  *
             *******************************/

            //  If we want only foreign companies and not local companies
            if( in_array('Foreign Company', $statuses) && !in_array('Local Company', $statuses) ){

                $companies = $companies->foreignCompany();

            //  If we want only local companies and not foreign companies
            }elseif( in_array('Local Company', $statuses) && !in_array('Foreign Company', $statuses) ){

                $companies = $companies->notForeignCompany();

            }

            /**********************************
             *  FILTER BY IMPORTED WITH CIPA  *
             *********************************/

            if( in_array('Imported', $statuses) ){

                $companies = $companies->ImportedFromCipa();

            }

            /**************************************
             *  FILTER BY NOT IMPORTED WITH CIPA  *
             *************************************/

            if( in_array('Not Imported', $statuses) ){

                $companies = $companies->notImportedFromCipa();

            }

            /******************************************
             *  FILTER BY RECENTLY UPDATED WITH CIPA  *
             *****************************************/

            if( in_array('Recently Updated', $statuses) ){

                $companies = $companies->recentlyUpdatedWithCipa();

            }

            /**********************************
             *  FILTER BY OUTDATED WITH CIPA  *
             *********************************/

            if( in_array('Outdated', $statuses) ){

                $companies = $companies->outdatedWithCipa();

            }


            /*****************************************
             *  FILTER BY COMPLIANT / NOT COMPLIANT  *
             *****************************************/

            //  If we want only compliant companies and not non-compliant companies
            if( in_array('Compliant', $statuses) && !in_array('Not Compliant', $statuses) ){

                $companies = $companies->compliant();

            //  If we want only non-compliant companies and not compliant companies
            }elseif( in_array('Not Compliant', $statuses) && !in_array('Compliant', $statuses) ){

                $companies = $companies->notCompliant();

            }
        }

        //  Return the companies
        return $companies;
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

            if( isset( $cipaCompany->response ) ){

                $cipaCompany = $cipaCompany->response->BursCompanyView;

                $this->update([
                    'name' => $cipaCompany->CompanyName->Value,
                    'company_status' => $cipaCompany->CompanyStatus != new \stdClass() ? $cipaCompany->CompanyStatus->Value : null,
                    'exempt' => $cipaCompany->Exempt != new \stdClass() ? $cipaCompany->Exempt->Value : null,
                    'foreign_company' => $cipaCompany->ForeignCompany != new \stdClass() ? $cipaCompany->ForeignCompany->Value : null,
                    'company_type' => $cipaCompany->CompanyType != new \stdClass() ? $cipaCompany->CompanyType->Value : null,
                    'company_sub_type' => $cipaCompany->CompanySubType != new \stdClass() ? $cipaCompany->CompanySubType->Value : null,
                    'return_month' => $cipaCompany->AnnualReturnFilingMonth != new \stdClass() ? $cipaCompany->AnnualReturnFilingMonth->Value : null,
                    'details' => $cipaCompany,
                    'cipa_updated_at' => \Carbon\Carbon::now()
                ]);

                //  If we should return an instance
                if( $return ){

                    //  Return a fresh instance
                    return $this->fresh();

                }

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
