<?php

namespace App\Http\Resources;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Companies extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = 'App\Http\Resources\Company';

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [];

        //  If we are paginating
        if( $this->resource instanceof LengthAwarePaginator ) {

            //  Provide pagination links
            $data['_links'] = [

                //  Link to current resource
                'self' => [
                    'href' => url()->full(),
                    'title' => 'These companies',
                ],

                'first' => [
                    'href' => $this->url(1),
                    'title' => 'First page of this collection',
                ],

                'prev' => [
                    'href' => $this->previousPageUrl(),
                    'title' => 'Previous page of this collection',
                ],

                'next' => [
                    'href' => $this->nextPageUrl(),
                    'title' => 'Next page of this collection',
                ],

                'last' => [
                    'href' => $this->url($this->lastPage()),
                    'title' => 'Last page of this collection',
                ],

                //  Link to search
                'search' => [
                    'href' => url()->current().'?search={searchTerms}',
                    'templated' => true,
                ],

            ];

            $data['total'] = $this->total();
            $data['count'] = $this->count();
            $data['per_page'] = $this->perPage();
            $data['current_page'] = $this->currentPage();
            $data['total_pages'] = $this->lastPage();

        }

        //  Provide the embedded content
        $data['_embedded'] = [
            'companies' => $this->collection,
        ];

        return $data;
    }

    /*
     *  This will remove all extra pagination fields from the JSON response (links, meta, etc) and allow you to
     *  customize the response as you'd like in toArray($request). The toResponse method call is NOT static,
     *  but instead calling the grandparent JsonResource::toResponse method, just as parent::toResponse would
     *  call the ResourceCollection toResponse(..) instance method.
     *  Link: https://stackoverflow.com/questions/48094741/customising-laravel-5-5-api-resource-collection-pagination
     */
    public function toResponse($request)
    {
        return JsonResource::toResponse($request);
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response $response
     */
    public function withResponse($request, $response)
    {
        $response->header('Content-Type', 'application/hal+json');
    }
}
