<?php

namespace App\Models;

use App\Traits\CommonTraits;
use App\Traits\OwnershipBundleTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OwnershipBundle extends Model
{
    use HasFactory, OwnershipBundleTraits, CommonTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cipa_identifier', 'percentage_of_shares', 'number_of_shares', 'total_shares',
        'ownership_type', 'shareholder_name', 'shareholder_id', 'shareholder_of_company_id',
        'is_director'
    ];

    /*
     *  Scope: Search any matching ownership bundles
     *
     *  Either searchOwnedBy() or searchWhoOwns()
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('shareholder_name', 'like', '%'.$searchTerm.'%')
                     ->orWhereHas('company', function (Builder $query) use ($searchTerm){
                        $query->search($searchTerm);
                    });
    }

    /*
     *  Scope: Search ownership bundles where the search term entity is the owner e.g
     *
     *  $search_term = 'Company A';
     *
     *  Search: What does "Company A" own
     */
    public function scopeSearchOwnedBy($query, $searchTerm)
    {
        return $query->where('shareholder_name', 'like', '%'.$searchTerm.'%');
    }

    /*
     *  Scope: Search ownership bundles where other entities own this search term entity e.g
     *
     *  $search_term = 'Company A';
     *
     *  Search: Who owns "Company A"
     */
    public function scopeSearchWhoOwns($query, $searchTerm)
    {
        return $query->whereHas('company', function (Builder $query) use ($searchTerm){
            $query->search($searchTerm);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles by shareholder owner type
     *
     *  $owner_types = ['individual', 'business', 'company']
     */
    public function scopeShareholderOwnerTypes($query, $owner_types = [])
    {
        return $query->whereHas('shareholder', function (Builder $query) use ($owner_types){
            $query->whereIn('owner_type', $owner_types);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder are current directors
     */
    public function scopeCurrentDirectors($query)
    {
        return $query->where('is_director', 'y');
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder are former directors
     */
    public function scopeFormerDirectors($query)
    {
        return $query->where('is_director', 'f');
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder are not directors
     */
    public function scopeNonDirectors($query)
    {
        return $query->where('is_director', 'n');
    }

    /*
     *  Scope:
     *  Returns ownership bundles by shareholder allocation type
     */
    public function scopeShareholderAllocationType($query, $allocationTypes, $start_percentage, $end_percentage)
    {
        return $query->where(function($query) use ($allocationTypes, $start_percentage, $end_percentage){

            if( in_array('majority shareholder', $allocationTypes) ){

                $query = $query->orWhere('percentage_of_shares', '>', '50');

            }

            if( in_array('minority shareholder', $allocationTypes) ){

                $query = $query->orWhere('percentage_of_shares', '<', '50');

            }

            if( in_array('equal shareholder', $allocationTypes) ){

                $query = $query->orWhere('percentage_of_shares', '=', '50');

            }

            if( in_array('only shareholder', $allocationTypes) ){

                $query = $query->orWhere('percentage_of_shares', '=', '100');

            }

            if( in_array('partial shareholder', $allocationTypes) ){

                $query = $query->orWhere('percentage_of_shares', '!=', '100');

            }

            if( in_array('custom shareholder', $allocationTypes) ){

                $start_percentage = is_null($start_percentage) ? 0 : $start_percentage;

                $end_percentage = is_null($end_percentage) ? 100 : $end_percentage;

                $query = $query->orWhere(function($query) use ($start_percentage, $end_percentage){
                    $query->where('percentage_of_shares', '>=', $start_percentage)
                          ->where('percentage_of_shares', '<=', $end_percentage);
                });

            }

        });
    }

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function company()
    {
        return $this->belongsTo(Company::class, 'shareholder_of_company_id');
    }

    public function shareholder()
    {
        return $this->belongsTo(Shareholder::class);
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type'
    ];

    public function getPercentageOfSharesAttribute($value)
    {
        return round($value, 2);
    }
}
