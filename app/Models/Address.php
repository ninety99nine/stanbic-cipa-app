<?php

namespace App\Models;

use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory, CommonTraits;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['country', 'region'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'end_date',
        'start_date'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cipa_identifier', 'type', 'care_of', 'line_1', 'line_2', 'post_code',
        'region_id', 'country_id', 'start_date', 'end_date',
        'owner_id', 'owner_type'
    ];

    /*************************************
     *  SCOPES / FILTERS                 *
     ************************************/

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('post_code', $searchTerm)
                     ->orWhere('line_1', 'like', '%'.$searchTerm.'%')
                     ->orWhere('line_2', 'like', '%'.$searchTerm.'%');
    }

    public function scopeValid($query)
    {
        return $query->whereNotNull('end_date');
    }

    public function scopeInValid($query)
    {
        return $query->whereNull('end_date');
    }

    public function scopeOnlyCompanies($query)
    {
        return $query->where('owner_type', 'company');
    }

    public function scopeOnlyIndividuals($query)
    {
        return $query->where('owner_type', 'individual');
    }

    public function scopeOnlySpecifiedRegion($query, $searchTerm)
    {
        return $query->whereHas('region', function (Builder $query) use ($searchTerm){
            $query->where('code', $searchTerm);
        });
    }

    public function scopeOnlySpecifiedCountry($query, $searchTerm)
    {
        return $query->whereHas('region', function (Builder $query) use ($searchTerm){
            $query->where('code', $searchTerm)
                  ->orWhere('name', $searchTerm);
        });
    }

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type', 'address_line'
    ];

    /**
     *  Address line
     */
    public function getAddressLineAttribute()
    {
        $region_code = ($this->region) ? $this->region->code : null;
        $country_name = ($this->country) ? $this->country->name : null;

        $values = [$this->care_of, $this->line_1, $this->line_2, $this->post_code, $region_code, $country_name];

        $address_line = '';

        foreach ($values as $value) {
            if( !empty($value)){

                //  Remove existing commas and trim white spaces
                $value = trim( str_replace(',', '', $value) );

                $address_line .= ( !empty($address_line) ? ', ' : '' ).$value;

            }
        }

        return $address_line;
    }

}
