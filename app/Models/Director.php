<?php

namespace App\Models;

use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Director extends Model
{
    use HasFactory, CommonTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cipa_identifier', 'individual_id', 'appointment_date', 'ceased_date', 'director_of_company_id'
    ];

    /*************************************
     *  SCOPES / FILTERS                 *
     ************************************/

    /*
     *  Scope:
     *  Returns current directors
     */
    public function scopeOnlyCurrentDirectors($query)
    {
        return $query->whereNull('ceased_date');
    }

    /*
     *  Scope:
     *  Returns former directors
     */
    public function scopeOnlyFormerDirectors($query)
    {
        return $query->whereNotNull('ceased_date');
    }

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function individual()
    {
        return $this->belongsTo(Individual::class);
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type'
    ];

}
