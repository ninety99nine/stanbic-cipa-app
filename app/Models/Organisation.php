<?php

namespace App\Models;

use App\Traits\CommonTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organisation extends Model
{
    use HasFactory, CommonTraits;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'registration_number', 'country_id'
    ];

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'owner');
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
        'resource_type'
    ];
}
