<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\CommonTraits;
use App\Traits\DirectorTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Director extends Model
{
    use HasFactory, CommonTraits, DirectorTraits;

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

    /*
     *  Scope:
     *  Returns directors that match the given director allocation date
     */
    public function scopeAppointmentDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){
            $query = $query->whereDate('appointment_date', '>=', $start_date);
        }

        if( $end_date ){
            $query = $query->whereDate('appointment_date', '<=', $end_date);
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns directors that match the given director ceased date
     */
    public function scopeCeasedDate($query, $start_date = null, $end_date = null)
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
    }    /*
    *  Scope:
    *  Returns directors with one or many number of companies
    */
   public function scopeDirectorToNumberOfCompanies($query, $types, $min_companies = null, $max_companies = null, $exact_companies = null)
   {
       return $query->where(function($query) use ($types, $min_companies, $max_companies, $exact_companies){

           if( in_array('director to one', $types) ){

               $query = $query->orWhere(function (Builder $query) {
                           return $query->directorToOneCompany();
                       });

           }

           if( in_array('director to many', $types) ){

               $query = $query->orWhere(function (Builder $query) {
                           return $query->directorToManyCompanies();
                       });

           }

           if( in_array('director to specific', $types) ){

               if( !is_null($min_companies) ){

                   $query = $query->directorToMinNumberOfCompanies($min_companies);

               }

               if( !is_null($max_companies) ){

                   $query = $query->directorToMaxNumberOfCompanies($max_companies);

               }

               if( !is_null($exact_companies) ){

                   $query = $query->directorToExactNumberOfCompanies($exact_companies);

               }

           }

       });

   }

   /*
    *  Scope:
    *  Returns director that match only one company
    */
   public function scopeDirectorToOneCompany($query)
   {
       return $query->whereHas('individual', function (Builder $query) {
            $query->has('directors', '=', 1);
       });
   }

   /*
    *  Scope:
    *  Returns director that match many companies
    */
   public function scopeDirectorToManyCompanies($query)
   {
       return $query->whereHas('individual', function (Builder $query) {
            $query->has('directors', '>=', 2);
       });
   }

   /*
    *  Scope:
    *  Returns director that match an exact number of companies
    */
   public function scopeDirectorToExactNumberOfCompanies($query, $number)
   {
        return $query->whereHas('individual', function (Builder $query) use ($number){
            $query->has('directors', '=', $number);
        });
   }

   /*
    *  Scope:
    *  Returns director that match an exact number of companies
    */
    public function scopeDirectorToMinNumberOfCompanies($query, $number)
    {
         return $query->whereHas('individual', function (Builder $query) use ($number){
             $query->has('directors', '>=', $number);
         });
    }
    /*

     *  Scope:
     *  Returns director that match an exact number of companies
     */
    public function scopeDirectorToMaxNumberOfCompanies($query, $number)
    {
         return $query->whereHas('individual', function (Builder $query) use ($number){
             $query->has('directors', '<=', $number);
         });
    }
    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function individual()
    {
        return $this->belongsTo(Individual::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'director_of_company_id');
    }

    public function ownershipBundles()
    {
        return $this->hasMany(OwnershipBundle::class);
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
