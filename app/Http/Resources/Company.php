<?php

namespace App\Http\Resources;

use App\Http\Resources\Store as StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [

            'id' => $this->id,
            'name' => $this->name,
            'company_status' => $this->company_status,
            'exempt' => $this->exempt,
            'foreign_company' => $this->foreign_company,
            'company_type' => $this->company_type,
            'company_sub_type' => $this->company_sub_type,
            'return_month' => $this->return_month,
            'details' => $this->details,
            'cipa_updated_at' => $this->cipa_updated_at,

            /*  Timestamp Info  */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            /*  Attributes  */
            '_attributes' => [
                'is_removed' => $this->is_removed,
                'is_cancelled' => $this->is_cancelled,
                'is_compliant' => $this->is_compliant,
                'resource_type' => $this->resource_type,
                'is_registered' => $this->is_registered,
                'cipa_updated_human_time' => $this->cipa_updated_human_time,
                'is_imported_from_cipa' => $this->is_imported_from_cipa,
                'is_recently_updated_with_cipa' => $this->is_recently_updated_with_cipa
            ],

            /*  Resource Links */
            '_links' => [
                'curies' => [
                    ['name' => 'oq', 'href' => 'https://oqcloud.co.bw/docs/rels/{rel}', 'templated' => true],
                ],

                //  Link to current resource
                'self' => [
                    'href' => route('company-show', ['company_id' => $this->id]),
                    'title' => 'This company',
                ]

            ],

            /*  Embedded Resources */
            '_embedded' => [

                'owner' => $this->owner()

            ]

        ];
    }

    public function owner()
    {
        if( $this->owner ){
            switch ($this->owner->resource_type) {
                case 'store':
                    return new StoreResource( $this->owner );
                case 'location':
                    return new LocationResource( $this->owner );
                case 'instant_cart':
                    return new InstantCartResource( $this->owner );
                break;
            }
        }
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
