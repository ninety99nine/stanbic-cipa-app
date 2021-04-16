<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait CommonTraits
{
    /**
     *  This method sets or creates a new Request Object
     */
    public function extractRequestData($resource = [])
    {
        //  If the resource is a valid Request Object
        if( ($resource instanceof \Illuminate\Http\Request) ){

            //  Return the Request Object data
            return $resource->all();

        //  If the resource is a valid non-empty Array
        }elseif( is_array($resource) && !empty($resource) ){

            //  Return the Array
            return $resource;

        }else{

            //  Return an empty Array
            return [];

        }
    }

    public function collectionResponse($data = [], $builder = null, $paginate = true)
    {
        try {

            //  Set the pagination limit e.g 15
            $limit = $data['limit'] ?? null;

            //  If we should paginate the builder
            if( $paginate === true ){

                //  Return PAGINATE
                return $builder->paginate($limit);

            //  If we should not paginate the builder
            }elseif( $paginate === false ){

                //  Return GET
                return $builder->get();

            //  If we should do nothing
            }elseif( $paginate === null ){

                //  Return BUILDER
                return $builder;

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /**
     *  This method validates fetching multiple resources
     */
    public function getResourcesValidation($data = [])
    {
        try {

            //  Set validation rules
            $rules = [
                'limit' => 'sometimes|required|numeric|min:1|max:100',
            ];

            //  Set validation messages
            $messages = [
                'limit.required' => 'Enter a valid limit containing only digits e.g 50',
                'limit.regex' => 'Enter a valid limit containing only digits e.g 50',
                'limit.min' => 'The limit attribute must be a value between 1 and 100',
                'limit.max' => 'The limit attribute must be a value between 1 and 100',
            ];

            $this->resourceValidation($data, $rules, $messages);

        } catch (\Exception $e) {

            throw($e);

        }
    }

    public function resourceValidation($data = [], $rules = [], $messages = [])
    {
        try {

            //  Validate request
            $validator = Validator::make($data, $rules, $messages);

            //  If the validation failed
            if ($validator->fails()) {

                //  Throw Validation Exception with validation errors
                throw ValidationException::withMessages(collect($validator->errors())->toArray());

            }

        } catch (\Exception $e) {

            throw($e);

        }
    }

    /*
     *  Returns the resource type
     */
    public function getResourceTypeAttribute()
    {
        return strtolower(Str::snake(class_basename($this)));
    }
}
