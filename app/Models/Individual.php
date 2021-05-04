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

    public function residentialAddresses()
    {
        return $this->addresses()->where('addresses.type', 'residential');
    }

    public function postalAddresses()
    {
        return $this->addresses()->where('addresses.type', 'postal');
    }

    public function directors()
    {
        return $this->hasMany(Director::class);
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type', 'full_name'
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




}
