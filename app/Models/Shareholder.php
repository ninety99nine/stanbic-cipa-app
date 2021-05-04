<?php

namespace App\Models;

use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shareholder extends Model
{
    use HasFactory, CommonTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cipa_identifier', 'nominee', 'appointment_date', 'ceased_date', 'shareholder_of_company_id',
        'owner_id', 'owner_type'
    ];

    /*************************************
     *  SCOPES / FILTERS                 *
     ************************************/
    /*
     *  Scope:
     *  Returns shareholders that are nominee
     */
    public function scopeNominee($query)
    {
        return $query->where('nominee', 'y');
    }

    /*
     *  Scope:
     *  Returns shareholders that are not nominee
     */
    public function scopeNotNominee($query)
    {
        return $query->where('nominee','!=','y');
    }

    /*
     *  Scope:
     *  Returns shareholders that are not specified nominee
     */
    public function scopeNotSpecifiedNominee($query)
    {
        return $query->whereNull('nominee');
    }

    /*
     *  Scope:
     *  Returns current shareholders
     */
    public function scopeOnlyCurrentShareholders($query)
    {
        return $query->whereNull('ceased_date');
    }

    /*
     *  Scope:
     *  Returns former shareholders
     */
    public function scopeOnlyFormerShareholders($query)
    {
        return $query->whereNotNull('ceased_date');
    }

    /*
     *  Scope:
     *  Returns only company shareholders
     */
    public function scopeOnlyCompanies($query)
    {
        return $query->where('owner_type', 'company');
    }

    /*
     *  Scope:
     *  Returns only individual shareholders
     */
    public function scopeOnlyIndividuals($query)
    {
        return $query->where('owner_type', 'individual');
    }

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function owner()
    {
        return $this->morphTo();
    }

    public function ownershipBundle()
    {
        return $this->hasOne(OwnershipBundle::class);
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type'
    ];

    /**
     *  Company nominee status
     */
    public function getNomineeAttribute($value)
    {
        return [
            'status' => $value,
            'name' => $value ? 'Yes' : 'No',
        ];
    }

}
