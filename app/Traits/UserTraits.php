<?php

namespace App\Traits;

use App\Exports\CompaniesExport;
use App\Imports\CompaniesImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;

trait UserTraits
{
    /**
     *  This method returns a list of users
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

                //  Set the users to this eloquent builder
                $users = $builder;

            }else{

                //  Get the users
                $users = (new \App\Models\User);

            }

            //  Filter the users
            $users = $this->filterResources($data, $users);

            //  Sort the users
            $users = $this->sortResources($data, $users);

            //  Return users
            return $this->collectionResponse($data, $users, $paginate);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method filters the users by search or status
     */
    public function filterResources($data = [], $users)
    {
        //  If we need to search for specific users
        if ( isset($data['search']) && !empty($data['search']) ) {

            $users = $this->filterResourcesBySearch($data, $users);

        }elseif ( isset($data['status']) && !empty($data['status']) ) {

            $users = $this->filterResourcesByStatus($data, $users);

        }

        //  Return the users
        return $users;
    }

    /**
     *  This method filters the users by search
     */
    public function filterResourcesBySearch($data = [], $users)
    {
        //  Set the search term e.g "Katlego Warona"
        $search_term = $data['search'] ?? null;

        //  Set the search term e.g "Katlego Warona"
        $search_type = $data['search_type'] ?? 'all';

        //  Filter admin users
        if( $search_type == 'admin' ){

            $users = $users->adminUsers();

        //  Filter basic users
        }elseif( $search_type == 'basic' ){

            $users = $users->basicUsers();

        //  Filter special users
        }elseif( $search_type == 'special' ){

            $users = $users->specialUsers();

        }

        return $users->search($search_term);

    }

    /**
     *  This method filters the users by status
     */
    public function filterResourcesByStatus($data = [], $users)
    {
        //  Set the statuses to an empty array
        $statuses = [];

        //  Set the status filters e.g ["admin users", "basic users", "special users", ...] or "admin users,basic users,special users, ..."
        $status_filters = $data['status'];

        //  If the filters are provided as String format e.g "admin users,basic users,special users"
        if( is_string($status_filters) ){

            //  Set the statuses to the exploded Array ["admin users", "basic users", "special users", ...]
            $statuses = explode(',', $status_filters);

        }elseif( is_array($status_filters) ){

            //  Set the statuses to the given Array ["admin users", "basic users", "special users", ...]
            $statuses = $status_filters;

        }

        //  Clean-up each status filter
        foreach ($statuses as $key => $status) {

            //  Convert " individuals " to "Individuals"
            $statuses[$key] = strtolower(trim($status));

        }

        if ( $users && count($statuses) ) {

            /***************************
             *  FILTER BY USER ROLES   *
             **************************/

            $filterByUserRoles = collect($statuses)->filter(function($status){
                return in_array($status, ['admin', 'basic', 'special']);
            })->toArray();

            if( count($filterByUserRoles) ){
                $users = $users->userRoles($filterByUserRoles);
            }

        }

        //  Return the users
        return $users;
    }

    /**
     *  This method sorts the users
     */
    public function sortResources($data = [], $users)
    {
        //  Set the sort by e.g "updated_at"
        $sort_by = $data['sort_by'] ?? null;

        //  Set the sort by type e.g "desc"
        $sort_by_type = $data['sort_by_type'] ?? null;

        if($sort_by && $sort_by_type){

            if( $sort_by_type == 'asc' ){

                return $users->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' ASC');

            }elseif( $sort_by_type == 'desc' ){

                return $users->orderByRaw('ISNULL('.$sort_by.'), '.$sort_by.' DESC');

            }

        }

        //  By default sort by the "updated at" date
        return $users->latest('updated_at');

    }

    /**
     *  This method exports a list of users
     */
    public function exportResources($data = [])
    {
        try {

            //  Get the users
            $users = $this->getResources($data, null, null);

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
            return Excel::download(new CompaniesExport($users), $file_name);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method imports a list of users
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
     *  This method returns a single user
     */
    public function getResource($id)
    {
        try {

            //  Get the resource
            $user = \App\Models\User::where('id', $id)->first() ?? null;

            //  If exists
            if ($user) {

                //  Return user
                return $user;

            } else {

                //  Return "Not Found" Error
                return help_resource_not_found();

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

}
