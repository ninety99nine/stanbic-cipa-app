<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\CommonTraits;
use App\Traits\CompanyTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory, CommonTraits, CompanyTraits;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'details' => 'array'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'cipa_updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uin', 'name', 'company_status', 'exempt', 'foreign_company', 'company_type', 'company_sub_type',
        'return_month', 'details', 'cipa_updated_at'
    ];

    /*
     *  Scope:
     *  Returns companies that are being searched
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('uin', $searchTerm)->orWhere('name', 'like', '%'.$searchTerm.'%');
    }

    /*
     *  Scope:
     *  Returns companies that match the given company status or statuses
     */
    public function scopeCompanyStatus($query, $status)
    {
        if( is_array($status) ){
            return $query->whereIn('company_status', $status);
        }else{
            return $query->where('company_status', $status);
        }
    }

    /*
     *  Scope:
     *  Returns companies that are exempt
     */
    public function scopeExempt($query)
    {
        return $query->where('exempt', '1');
    }

    /*
     *  Scope:
     *  Returns companies that are not exempt
     */
    public function scopeNotExempt($query)
    {
        return $query->where('exempt', '0');
    }

    /*
     *  Scope:
     *  Returns companies that are foreign companies
     */
    public function scopeForeignCompany($query)
    {
        return $query->where('foreign_company', '1');
    }

    /*
     *  Scope:
     *  Returns companies that are not foreign companies
     */
    public function scopeNotForeignCompany($query)
    {
        return $query->where('foreign_company', '0');
    }

    /*
     *  Scope:
     *  Returns companies that match the given return month
     */
    public function scopeReturnMonth($query)
    {
        return $query->where('return_month', $query);
    }

    /*
     *  Scope:
     *  Returns companies that match the given company type
     */
    public function scopeCompanyType($query, $status)
    {
        if( is_array($status) ){
            return $query->whereIn('company_type', $status);
        }else{
            return $query->where('company_type', $status);
        }
    }

    /*
     *  Scope:
     *  Returns companies that match the given company sub type
     */
    public function scopeCompanySubType($query, $status)
    {
        if( is_array($status) ){
            return $query->whereIn('company_sub_type', $status);
        }else{
            return $query->where('company_sub_type', $status);
        }
    }

    /*
     *  Scope:
     *  Returns companies that are imported from cipa
     */
    public function scopeImportedFromCipa($query)
    {
        return $query->whereNotNull('details');
    }

    /*
     *  Scope:
     *  Returns companies that are not imported from cipa
     */
    public function scopeNotImportedFromCipa($query)
    {
        return $query->whereNull('details');
    }

    /*
     *  Scope:
     *  Returns companies that are outdated
     */
    public function scopeOutdatedWithCipa($query, $frequency = 'days', $duration = 1)
    {
        if( $frequency == 'hours' ){
            $date = Carbon::now()->subHours($duration);
        }else{
            $date = Carbon::now()->subDays($duration);
        }

        return $query->importedFromCipa()->where('cipa_updated_at', '<', $date)->orWhereNotNull('details');
    }

    /*
     *  Scope:
     *  Returns companies that are recently updated
     */
    public function scopeRecentlyUpdatedWithCipa($query, $frequency = 'days', $duration = 1)
    {
        if( $frequency == 'hours' ){
            $date = Carbon::now()->subHours($duration);
        }else{
            $date = Carbon::now()->subDays($duration);
        }

        return $query->importedFromCipa()->where('cipa_updated_at', '>', $date);
    }

    /*
     *  Scope:
     *  Returns companies that are compliant
     */
    public function scopeCompliant($query)
    {
        $query->importedFromCipa()->where('company_status', 'Registered');
    }

    /*
     *  Scope:
     *  Returns companies that are not compliant
     */
    public function scopeNotCompliant($query)
    {
        $query->importedFromCipa()->whereNot('company_status', 'Registered');
    }

    /** ATTRIBUTES
     *
     *  Note that the "resource_type" is defined within CommonTraits.
     */
    protected $appends = [
        'resource_type', 'is_registered', 'is_cancelled', 'is_removed', 'is_compliant',
        'is_imported_from_cipa', 'is_recently_updated_with_cipa', 'cipa_updated_human_time'
    ];

    /**
     *  Company compliance return month
     */
    public function getReturnMonthAttribute($value)
    {
        $date = Carbon::createFromFormat('d/n/Y', '01/'.$value.'/2020');

        $long_name = $date->format('F');
        $short_name = $date->format('M');

        return [
            'number' => $value,          //   1 - 12
            'long_name' => $long_name,   //   January - December
            'short_name' => $short_name  //   Jan - Dec
        ];
    }

    /**
     *  Company compliance exempt status
     */
    public function getExemptAttribute($value)
    {
        $status = $value;

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No'
        ];
    }

    /**
     *  Company compliance foreign company status
     */
    public function getForeignCompanyAttribute($value)
    {
        $status = $value;

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No'
        ];
    }


    /**
     *  Company registration status
     */
    public function getIsRegisteredAttribute()
    {
        return [
            'status' => ($this->company_status == 'Registered')
        ];
    }

    /**
     *  Company cancellation status
     */
    public function getIsCancelledAttribute()
    {
        return [
            'status' => ($this->company_status == 'Cancelled')
        ];
    }

    /**
     *  Company removed status
     */
    public function getIsRemovedAttribute()
    {
        return [
            'status' => ($this->company_status == 'Removed')
        ];
    }

    /**
     *  Company compliance status
     */
    public function getIsCompliantAttribute()
    {
        $is_compliant = $this->company_status == 'Registered';

        return [
            'status' => $is_compliant,
            'name' => $is_compliant ? 'Compliant' : 'Not Compliant',
            'description' => $is_compliant ? 'This company is complaint'
                                    : 'This company is not complaint'
        ];
    }

    /**
     *  Company is imported with CIPA status
     */
    public function getIsImportedFromCipaAttribute()
    {
        $status = is_null($this->details) == false;

        return [
            'status' => $status,
            'name' => $status ? 'Updated' : 'Not updated',
            'description' => $status ? 'This company record has been updated with CIPA'
                                     : 'This company record has never been updated with CIPA'
        ];
    }

    /**
     *  Company CIPA updated human readable time
     */
    public function getCipaUpdatedHumanTimeAttribute()
    {
        if( $this->cipa_updated_at ){

            $diffInDays = $this->cipa_updated_at->diffInDays( Carbon::now() );
            $diffInHours = $this->cipa_updated_at->diffInHours( Carbon::now() );
            $diffInMinutes = $this->cipa_updated_at->diffInMinutes( Carbon::now() );
            $diffInSeconds = $this->cipa_updated_at->diffInSeconds( Carbon::now() );

            if( $diffInDays ){
                return $diffInDays .' '. ($diffInDays == 1 ? 'day' : 'days' ). ' ago';
            }elseif( $diffInHours ){
                return $diffInHours .' '. ($diffInHours == 1 ? 'hour' : 'hours' ). ' ago';
            }elseif( $diffInMinutes ){
                return $diffInMinutes .' '. ($diffInMinutes == 1 ? 'min' : 'mins' ). ' ago';
            }elseif( $diffInSeconds ){
                return $diffInSeconds .' '. ($diffInSeconds == 1 ? 'sec' : 'secs' ). ' ago';
            }else{
                return 'just now';
            }

        }
    }

    /**
     *  Company is recently updated with CIPA status
     */
    public function getIsRecentlyUpdatedWithCipaAttribute()
    {
        $updated_with_cipa = is_null($this->details) == false;

        if( $updated_with_cipa ){

            //  If we add a day to the updated_at timestamp but the date is not in the past then the record is recently updated
            $status = $this->cipa_updated_at->addDays(1)->isPast() == false;

            $description = $status ? 'This company record is up to date (Recently updated '.$this->cipa_updated_human_time.')'
                                   : 'This company record is out of date (Last updated '.$this->cipa_updated_human_time.')';
        }else{
            $status = false;
            $description = 'This company record has never been updated with CIPA';
        }

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No',
            'description' => $description
        ];
    }

    public function setCompanyStatusAttribute($value)
    {
        $this->attributes['company_status'] = ucwords($value);
    }

    public function setExemptAttribute($value)
    {
        $this->attributes['exempt'] = strtolower($value) == 'true' ? 1 : 0;
    }

    public function setForeignCompanyAttribute($value)
    {
        $this->attributes['foreign_company'] = strtolower($value) == 'true' ? 1 : 0;
    }

    public function setCompanyTypeAttribute($value)
    {
        //  Convert "someValue" to "Some Value"
        $this->attributes['company_type'] = ucwords( preg_replace('/([a-z])([A-Z])/', '$1 $2', $value) );
    }

    public function setCompanySubTypeAttribute($value)
    {
        //  Convert "someValue" to "Some Value"
        $this->attributes['company_sub_type'] = ucwords( preg_replace('/([a-z])([A-Z])/', '$1 $2', $value) );
    }

}
