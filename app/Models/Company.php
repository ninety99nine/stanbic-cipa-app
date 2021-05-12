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
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['addresses'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $casts = [
        'details' => 'array',
        'marked_as_client' => 'boolean'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'cipa_updated_at',
        'dissolution_date',
        'incorporation_date',
        're_registration_date',
        'annual_return_last_filed_date',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uin', 'name', 'info', 'company_status', 'exempt', 'foreign_company', 'company_type', 'company_sub_type',
        'incorporation_date', 're_registration_date', 'old_company_number', 'dissolution_date', 'own_constitution_yn',
        'business_sector', 'annual_return_filing_month', 'annual_return_last_filed_date', 'details', 'cipa_updated_at',

        'marked_as_client'
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
        return $query->where('exempt', 'y');
    }

    /*
     *  Scope:
     *  Returns companies that are not exempt
     */
    public function scopeNotExempt($query)
    {
        return $query->where('exempt','!=','y');
    }

    /*
     *  Scope:
     *  Returns companies that are not specified exempt
     */
    public function scopeNotSpecifiedExempt($query)
    {
        return $query->whereNull('exempt');
    }

    /*
     *  Scope:
     *  Returns companies that are foreign companies
     */
    public function scopeForeignCompany($query)
    {
        return $query->where('foreign_company', 'y');
    }

    /*
     *  Scope:
     *  Returns companies that are not foreign companies
     */
    public function scopeNotForeignCompany($query)
    {
        return $query->where('foreign_company','!=','y');
    }

    /*
     *  Scope:
     *  Returns companies that are not specified foreign company
     */
    public function scopeNotSpecifiedForeignCompany($query)
    {
        return $query->whereNull('foreign_company');
    }

    /*
     *  Scope:
     *  Returns companies that are own constitution
     */
    public function scopeOwnConstitutionYn($query)
    {
        return $query->where('own_constitution_yn', 'y');
    }

    /*
     *  Scope:
     *  Returns companies that are not own constitution
     */
    public function scopeNotOwnConstitutionYn($query)
    {
        return $query->where('own_constitution_yn','!=','y');
    }

    /*
     *  Scope:
     *  Returns companies that are not specified own constitution
     */
    public function scopeNotSpecifiedOwnConstitutionYn($query)
    {
        return $query->whereNull('own_constitution_yn');
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
     *  Returns companies that match the given company sub type
     */
    public function scopeBusinessSector($query, $status)
    {
        if( is_array($status) ){
            return $query->whereIn('business_sector', $status);
        }else{
            return $query->where('business_sector', $status);
        }
    }

    /*
     *  Scope:
     *  Returns companies that are imported from cipa
     */
    public function scopeImportedFromCipa($query)
    {
        return $query->whereNotNull('cipa_updated_at');
    }

    /*
     *  Scope:
     *  Returns companies that are not imported from cipa
     */
    public function scopeNotImportedFromCipa($query)
    {
        return $query->whereNull('cipa_updated_at');
    }

    /*
     *  Scope:
     *  Returns companies that are outdated
     */
    public function scopeOutdatedWithCipa($query, $frequency = 'days', $duration = [])
    {
        $duration = [
            'hours' => isset($duration['hours']) && !empty($duration['hours']) ? $duration : 24,
            'days' => isset($duration['days']) && !empty($duration['days']) ? $duration : 1
        ];

        if( $frequency == 'hours' ){
            $date = Carbon::now()->subHours($duration['hours'])->format('Y-m-d H:i:s');
        }else{
            $date = Carbon::now()->subDays($duration['days'])->format('Y-m-d H:i:s');
        }

        return $query->whereNull('cipa_updated_at')->orWhere(function($query) use ($date){
                    $query->whereNotNull('cipa_updated_at')
                          ->where('cipa_updated_at', '<', $date);
                });
    }

    /*
     *  Scope:
     *  Returns companies that are recently updated
     */
    public function scopeRecentlyUpdatedWithCipa($query, $frequency = 'days', $duration = [])
    {
        $duration = [
            'hours' => isset($duration['hours']) && !empty($duration['hours']) ? $duration : 24,
            'days' => isset($duration['days']) && !empty($duration['days']) ? $duration : 1
        ];

        if( $frequency == 'hours' ){
            $date = Carbon::now()->subHours($duration['hours'])->format('Y-m-d H:i:s');
        }else{
            $date = Carbon::now()->subDays($duration['days'])->format('Y-m-d H:i:s');
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
        $query->importedFromCipa()->where('company_status', '!=','Registered');
    }

    /*
     *  Scope:
     *  Returns companies that match the given dissolution date
     */
    public function scopeDissolutionDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){

            $query = $query->whereDate('dissolution_date', '>=', $start_date);
        }

        if( $end_date ){
            $query = $query->whereDate('dissolution_date', '<=', $end_date);
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns companies that match the given incorporation date
     */
    public function scopeIncorporationDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){

            $query = $query->whereDate('incorporation_date', '>=', $start_date);
        }

        if( $end_date ){
            $query = $query->whereDate('incorporation_date', '<=', $end_date);
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns companies that match the given re-registration date
     */
    public function scopeReRegistrationDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){

            $query = $query->whereDate('re_registration_date', '>=', $start_date);
        }

        if( $end_date ){
            $query = $query->whereDate('re_registration_date', '<=', $end_date);
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns companies that match the given annual return last filed date
     */
    public function scopeAnnualReturnLastFiledDate($query, $start_date = null, $end_date = null)
    {
        if( $start_date ){

            $query = $query->whereDate('annual_return_last_filed_date', '>=', $start_date);
        }

        if( $end_date ){
            $query = $query->whereDate('annual_return_last_filed_date', '<=', $end_date);
        }

        return $query;
    }

    /*
     *  Scope:
     *  Returns companies that match the given annual return filling month
     */
    public function scopeAnnualReturnFilingMonth($query, $month_number)
    {
        $query->importedFromCipa()->where('annual_return_filing_month', $month_number);
    }

    /*
     *  Scope:
     *  Returns companies that match the updated with Cipa date
     */
    public function scopeUpdatedWithCipaDate($query, $date, $type = 'after')
    {
        if( $date && $type ){
            $operation = ($type == 'after') ? '>' : '<';
            return $query->where('cipa_updated_at', $operation, $date);
        }else{
            return $query;
        }
    }

    /*************************************
     *  RELATIONSHIPS                    *
     ************************************/

    public function directors()
    {
        return $this->hasMany(Director::class, 'director_of_company_id');
    }

    public function shareholders()
    {
        return $this->hasMany(Shareholder::class, 'shareholder_of_company_id');
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
        'resource_type', 'is_registered', 'is_cancelled', 'is_removed', 'is_not_found', 'is_compliant',
        'is_imported_from_cipa', 'is_recently_updated_with_cipa', 'cipa_updated_human_time'
    ];

    /**
     *  Company return month
     */
    public function getAnnualReturnFilingMonthAttribute($value)
    {
        if($value){
            $date = Carbon::createFromFormat('d/n/Y', '01/'.$value.'/2020');
            $long_name = $date->format('F');
            $short_name = $date->format('M');
        }else{
            $long_name = null;
            $short_name = null;
        }

        return [
            'number' => $value,          //   1 - 12
            'long_name' => $long_name,   //   January - December
            'short_name' => $short_name  //   Jan - Dec
        ];
    }

    /**
     *  Company exempt status
     */
    public function getExemptAttribute($value)
    {
        $status = ($value == 'y');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : ($status == null ? 'Not specified' : 'No')
        ];
    }

    /**
     *  Company foreign company status
     */
    public function getForeignCompanyAttribute($value)
    {
        $status = ($value == 'y');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : ($status == null ? 'Not specified' : 'No')
        ];
    }

    /**
     *  Company own constitution status
     */
    public function getOwnConstitutionYnAttribute($value)
    {
        $status = ($value == 'y');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : ($status == null ? 'Not specified' : 'No')
        ];
    }

    /**
     *  Company incorporation date
     */
    public function getIncorporationDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d M Y') : null;
    }

    /**
     *  Company re-registration date
     */
    public function getReRegistrationDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d M Y') : null;
    }

    /**
     *  Company dissolution date
     */
    public function getDissolutionDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d M Y') : null;
    }

    /**
     *  Company annual return last filed date
     */
    public function getAnnualReturnLastFiledDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('d M Y') : null;
    }

    public function getRegisteredOfficeAddressAttribute()
    {
        //  Foreach address return the address line
        return collect($this->addresses)->where('type', 'registered_office_address')->map(function($address){

            return $address->address_line;

        //  Join multiple addresses with the symbol below
        })->join(' | ');
    }

    public function getPostalAddressAttribute()
    {
        //  Foreach address return the address line
        return collect($this->addresses)->where('type', 'postal_address')->map(function($address){

            return $address->address_line;

        //  Join multiple addresses with the symbol below
        })->join(' | ');
    }

    public function getPrincipalPlaceOfBusinessAttribute()
    {
        //  Foreach address return the address line
        return collect($this->addresses)->where('type', 'principal_place_of_business')->map(function($address){

            return $address->address_line;

        //  Join multiple addresses with the symbol below
        })->join(' | ');
    }

    /**
     *  Company registration status
     */
    public function getIsRegisteredAttribute()
    {
        $status = ($this->company_status == 'Registered');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No',
        ];
    }

    /**
     *  Company cancellation status
     */
    public function getIsCancelledAttribute()
    {
        $status = ($this->company_status == 'Cancelled');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No',
        ];
    }

    /**
     *  Company removed status
     */
    public function getIsRemovedAttribute()
    {
        $status = ($this->company_status == 'Removed');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No',
        ];
    }

    /**
     *  Company not found status
     */
    public function getIsNotFoundAttribute()
    {
        $status = ($this->company_status == 'Not Found');

        return [
            'status' => $status,
            'name' => $status ? 'Yes' : 'No',
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
        $status = is_null($this->cipa_updated_at) == false;

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
                return $diffInHours .' '. ($diffInHours == 1 ? 'hr' : 'hrs' ). ' ago';
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
        $updated_with_cipa = is_null($this->cipa_updated_at) == false;

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

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    public function setCompanyStatusAttribute($value)
    {
        $this->attributes['company_status'] = ucwords($value);
    }

    public function setExemptAttribute($value)
    {
        if( ($value === true) || ($value === false) ){
            $this->attributes['exempt'] = ($value == true ? 'y' : 'n');
        }else{
            $this->attributes['exempt'] = null;
        }
    }

    public function setForeignCompanyAttribute($value)
    {
        if( ($value === true) || ($value === false) ){
            $this->attributes['foreign_company'] = ($value == true ? 'y' : 'n');
        }else{
            $this->attributes['foreign_company'] = null;
        }
    }

    public function setOwnConstitutionYnAttribute($value)
    {
        if( ($value === true) || ($value === false) ){
            $this->attributes['own_constitution_yn'] = ($value == true ? 'y' : 'n');
        }else{
            $this->attributes['own_constitution_yn'] = null;
        }
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

    public function setBusinessSectorAttribute($value)
    {
        //  Convert "someValue" to "Some Value"
        $this->attributes['business_sector'] = ucwords( preg_replace('/([a-z])([A-Z])/', '$1 $2', $value) );
    }

}
