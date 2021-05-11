<?php

namespace App\Models;

use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Individual extends Model
{
    use HasFactory, CommonTraits;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['addresses'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cipa_identifier', 'first_name', 'middle_names', 'last_name'
    ];

    /*************************************
     *  SCOPES / FILTERS                 *
     ************************************/

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('first_name', $searchTerm)
                     ->orWhere('middle_names', 'like', '%'.$searchTerm.'%')
                     ->orWhere('last_name', 'like', '%'.$searchTerm.'%');
    }

    public function scopeWithoutResidentialAddress($query)
    {
        return $query->doesntHave('residentialAddresses');
    }

    public function scopeWithoutPostalAddress($query)
    {
        return $query->doesntHave('residentialAddresses');
    }

    public function scopeOnlySpecifiedResidentialAddressRegion($query, $searchTerm)
    {
        return $query->whereHas('residentialAddresses', function (Builder $query) use ($searchTerm){
            $query->onlySpecifiedRegion($searchTerm);
        });
    }

    public function scopeOnlySpecifiedResidentialAddressCountry($query, $searchTerm)
    {
        return $query->whereHas('residentialAddresses', function (Builder $query) use ($searchTerm){
            $query->onlySpecifiedCountry($searchTerm);
        });
    }

    public function scopeOnlySpecifiedPostalAddressRegion($query, $searchTerm)
    {
        return $query->whereHas('postalAddresses', function (Builder $query) use ($searchTerm){
            $query->onlySpecifiedRegion($searchTerm);
        });
    }

    public function scopeOnlySpecifiedPostalAddressCountry($query, $searchTerm)
    {
        return $query->whereHas('postalAddresses', function (Builder $query) use ($searchTerm){
            $query->onlySpecifiedCountry($searchTerm);
        });
    }

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function addresses()
    {
        return $this->morphMany(Address::class, 'owner');
    }

    public function directors()
    {
        return $this->hasMany(Director::class);
    }

    public function shares()
    {
        return $this->morphMany(Shareholder::class, 'owner');
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type', 'full_name', 'residential_address_lines', 'postal_address_lines'
    ];

    /**
     *  Full name
     */
    public function getFullNameAttribute()
    {
        $names = [$this->first_name, $this->middle_names, $this->last_name];

        $full_name = '';

        foreach ($names as $name) {
            if( !empty($name)){
                $full_name .= ( !empty($full_name) ? ' ' : '' ).$name;
            }
        }

        return $full_name;
    }

    /**
     *  Residential Address Lines
     */
    public function getResidentialAddressLinesAttribute()
    {
        //  Foreach address return the address line
        return collect($this->addresses)->where('type', 'residential_address')->map(function($address){

            return $address->address_line;

        //  Join multiple addresses with the symbol below
        })->join(' | ');
    }

    /**
     *  Residential Address Lines
     */
    public function getPostalAddressLinesAttribute()
    {
        //  Foreach address return the address line
        return collect($this->addresses)->where('type', 'postal_address')->map(function($address){

            return $address->address_line;

        //  Join multiple addresses with the symbol below
        })->join(' | ');
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = trim($value);
    }

    public function setMiddleNamesAttribute($value)
    {
        $this->attributes['middle_names'] = trim($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = trim($value);
    }

}
