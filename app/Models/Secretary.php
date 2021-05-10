<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Secretary extends Model
{
    use HasFactory, CommonTraits;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'appointment_date',
        'ceased_date'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cipa_identifier', 'appointment_date', 'ceased_date', 'secretary_of_company_id',
        'owner_id', 'owner_type'
    ];

    /*************************************
     *  SCOPES / FILTERS                 *
     ************************************/

    /*
     *  Scope:
     *  Returns current secretaries
     */
    public function scopeOnlyCurrentSecretaries($query)
    {
        return $query->whereNull('ceased_date');
    }

    /*
     *  Scope:
     *  Returns former secretaries
     */
    public function scopeOnlyFormerSecretaries($query)
    {
        return $query->whereNotNull('ceased_date');
    }

    /*
     *  Scope:
     *  Returns only company secretaries
     */
    public function scopeOnlyCompanies($query)
    {
        return $query->where('owner_type', 'company');
    }

    /*
     *  Scope:
     *  Returns only individual secretaries
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

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type'
    ];

    public function getAppointmentDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d M Y') : null;
    }

    public function getCeasedDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d M Y') : null;
    }

}
