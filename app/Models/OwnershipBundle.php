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
        'total_shareholder_occurances', 'is_shareholder_to_self', 'cipa_ownership_type',
        'shareholder_name', 'shareholder_id', 'shareholder_of_company_id', 'director_id'
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
     *  Returns ownership bundles with duplicate shareholder names
     */
    public function scopeDuplicateShareholderNames($query)
    {
        return $query->where('total_shareholder_occurances', '>=', 2);
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company is shareholder to itself
     */
    public function scopeIsShareholderToSelf($query)
    {
        return $query->where('is_shareholder_to_self', '1');
    }

    /*
     *  Scope:
     *  Returns ownership bundles by shareholder owner type
     *
     *  $owner_types = ['individual', 'company', 'organisation']
     */
    public function scopeShareholderOwnerTypes($query, $owner_types = [])
    {
        return $query->whereHas('shareholder', function (Builder $query) use ($owner_types){
            $query->whereIn('owner_type', $owner_types);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles by director type
     */
    public function scopeDirectorType($query, $directorTypes)
    {
        return $query->where(function($query) use ($directorTypes){

            if( in_array('current director', $directorTypes) ){

                $query = $query->orWhereHas('director', function (Builder $query) {
                    $query->whereNull('ceased_date');
                });

            }

            if( in_array('former director', $directorTypes) ){

                $query = $query->orWhereHas('director', function (Builder $query) {
                    $query->whereNotNull('ceased_date');
                });

            }

            if( in_array('not director', $directorTypes) ){

                $query = $query->orWhereNull('director_id');

            }

        });
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

    /*
     *  Scope:
     *  Returns ownership bundles where company has shares in other companies
     */
    public function scopeCompanyHasShares($query)
    {
        return $query->has('company.shares');
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company does not have shares in other companies
     */
    public function scopeCompanyDoesNotHaveShares($query)
    {
        return $query->doesntHave('company.shares');
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company has given number of shareholders
     */
    public function scopeHasShareholders($query, $types, $min = null, $max = null, $equal = null)
    {
        return $query->where(function($query) use ($types, $min, $max, $equal){

            if( in_array('has one shareholder', $types) ){

                $query = $query->orWhere(function (Builder $query) {
                            return $query->hasOneShareholder();
                        });

            }

            if( in_array('has many shareholders', $types) ){

                $query = $query->orWhere(function (Builder $query) {
                            return $query->hasManyShareholders();
                        });

            }

            if( in_array('has specific shareholders', $types) ){

                if( !is_null($min) ){

                    $query = $query->hasMinShareholders($min);

                }

                if( !is_null($max) ){

                    $query = $query->hasMaxShareholders($max);

                }

                if( !is_null($equal) ){

                    $query = $query->hasExactShareholders($equal);

                }

            }

        });

    }

    /*
     *  Scope:
     *  Returns ownership bundles where company has one shareholder
     */
    public function scopeHasOneShareholder($query)
    {
        return $query->whereHas('company', function (Builder $query) {
            $query->has('shareholders', '=', 1);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company has many shareholders
     */
    public function scopeHasManyShareholders($query)
    {
        return $query->whereHas('company', function (Builder $query) {
            $query->has('shareholders', '>=', 2);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company has exact number of shareholders
     */
    public function scopeHasExactShareholders($query, $number)
    {
        return $query->whereHas('company', function (Builder $query) use ($number){
            $query->has('shareholders', '=', $number);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company has minimum number of shareholders
     */
    public function scopeHasMinShareholders($query, $number)
    {
        return $query->whereHas('company', function (Builder $query) use ($number){
            $query->has('shareholders', '>=', $number);
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where company has minimum number of shareholders
     */
    public function scopeHasMaxShareholders($query, $number)
    {
        return $query->whereHas('company', function (Builder $query) use ($number){
            $query->has('shareholders', '<=', $number);
        });
    }







    /*
     *  Scope:
     *  Returns ownership bundles where shareholder has source of shares
     */
    public function scopeHasSourcesOfShares($query, $types, $min_source_of_shares = null, $max_source_of_shares = null, $exact_source_of_shares = null)
    {
        return $query->where(function($query) use ($types, $min_source_of_shares, $max_source_of_shares, $exact_source_of_shares){

            if( in_array('shareholder to one', $types) ){

                $query = $query->orWhere(function (Builder $query) {
                            return $query->hasOneSourceOfShares();
                        });

            }

            if( in_array('shareholder to many', $types) ){

                $query = $query->orWhere(function (Builder $query) {
                            return $query->hasManySourcesOfShares();
                        });

            }

            if( in_array('shareholder to specific', $types) ){

                if( !is_null($min_source_of_shares) ){

                    $query = $query->hasMinSpecifiedSourceOfShares($min_source_of_shares);

                }

                if( !is_null($max_source_of_shares) ){

                    $query = $query->hasMaxSpecifiedSourceOfShares($max_source_of_shares);

                }

                if( !is_null($exact_source_of_shares) ){

                    $query = $query->hasExactlySpecifiedSourceOfShares($exact_source_of_shares);

                }

            }

        });

    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder has one source of shares
     */
    public function scopeHasOneSourceOfShares($query)
    {
        return $query->whereHas('shareholder', function (Builder $query) {
            $query->whereHas('owner', function (Builder $query) {
                $query->has('shares', '=', 1);
            });
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder has multiple source of shares
     */
    public function scopeHasManySourcesOfShares($query)
    {
        return $query->whereHas('shareholder', function (Builder $query) {
            $query->whereHas('owner', function (Builder $query) {
                $query->has('shares', '>=', 2);
            });
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder has a specified number source of shares
     */
    public function scopeHasExactlySpecifiedSourceOfShares($query, $equal_source_of_shares)
    {
        return $query->whereNotNull('shareholder_id')->whereHas('shareholder', function (Builder $query) use ($equal_source_of_shares){
            $query->whereNotNull('owner_id')->whereHas('owner', function (Builder $query) use ($equal_source_of_shares) {
                $query->has('shares', '=', $equal_source_of_shares);
            });
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder has a specified number source of shares
     */
    public function scopeHasMinSpecifiedSourceOfShares($query, $min_source_of_shares)
    {
        return $query->whereHas('shareholder', function (Builder $query) use ($min_source_of_shares){
            $query->whereHas('owner', function (Builder $query) use ($min_source_of_shares) {
                $query->has('shares', '>=', $min_source_of_shares);
            });
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles where shareholder has a specified number source of shares
     */
    public function scopeHasMaxSpecifiedSourceOfShares($query, $max_source_of_shares)
    {
        return $query->whereHas('shareholder', function (Builder $query) use ($max_source_of_shares){
            $query->whereHas('owner', function (Builder $query) use ($max_source_of_shares) {
                $query->has('shares', '<=', $max_source_of_shares);
            });
        });
    }

    /*
     *  Scope:
     *  Returns ownership bundles that match the given shareholder allocation date
     */
    public function scopeShareholderAppointmentDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){

            $query = $query->whereHas('shareholder', function (Builder $query) use ($start_date) {
                $query->whereDate('appointment_date', '>=', $start_date);
            });
        }

        if( $end_date ){
            $query = $query->whereHas('shareholder', function (Builder $query) use ($end_date) {
                $query->whereDate('appointment_date', '<=', $end_date);
            });
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns ownership bundles that match the given shareholder ceased date
     */
    public function scopeShareholderCeasedDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){
            $query = $query->whereHas('shareholder', function (Builder $query) use ($start_date) {
                $query->whereDate('ceased_date', '>=', $start_date);
            });
        }

        if( $end_date ){
            $query = $query->whereHas('shareholder', function (Builder $query) use ($end_date) {
                $query->whereDate('ceased_date', '<=', $end_date);
            });
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns ownership bundles that match the given director allocation date
     */
    public function scopeDirectorAppointmentDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){
            $query = $query->whereHas('director', function (Builder $query) use ($start_date) {
                $query->whereDate('appointment_date', '>=', $start_date);
            });
        }

        if( $end_date ){
            $query = $query->whereHas('director', function (Builder $query) use ($end_date) {
                $query->whereDate('appointment_date', '<=', $end_date);
            });
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns ownership bundles that match the given director ceased date
     */
    public function scopeDirectorCeasedDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){
            $query = $query->whereHas('director', function (Builder $query) use ($start_date) {
                $query->whereDate('ceased_date', '>=', $start_date);
            });
        }

        if( $end_date ){
            $query = $query->whereHas('director', function (Builder $query) use ($end_date) {
                $query->whereDate('ceased_date', '<=', $end_date);
            });
        }

        return $query;
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

    public function director()
    {
        return $this->belongsTo(Director::class);
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
