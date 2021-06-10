<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class OwnershipBundlesExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths
{
    private $ownership_bundles;

    public function __construct($ownership_bundles)
    {
        $this->ownership_bundles = $ownership_bundles;
    }

    public function query()
    {
        return $this->ownership_bundles;
    }

    public function headings(): array
    {
        return [

            //  Company
            'UIN', 'Name', 'Company Status', 'Company Type', 'Company Sub Type', 'Incorporation Date',

            //  Ownership bundle
            'Shareholder Name', 'Shareholder Type', 'Shareholder UIN', 'Occurances', 'Is Shareholder To Self',

            '% Shares', 'Number Of Shares', 'Total Shares',

            'Nominee',
            'Shareholder Appointment Date', 'Shareholder Ceased Date',

            'Is Director', 'Director Appointment Date', 'Director Ceased Date',

            'Residential Address', 'Postal Address'
        ];

    }
    public function columnWidths(): array
    {
        return [
            'UIN' => 100
        ];
    }

    public function map($ownership_bundle): array
    {
        $variables = [
            'company_uin', 'company_name', 'company_status', 'company_type', 'company_sub_type', 'incorporation_date',
            'shareholder_name', 'shareholder_uin', 'shareholder_percentage_of_shares', 'shareholder_number_of_shares',
            'shareholder_total_shares', 'total_shareholder_occurances', 'is_shareholder_to_self', 'shareholder_nominee',
            'shareholder_owner_type', 'shareholder_ceased_date', 'shareholder_appointment_date',
            'shareholder_residential_addresses', 'shareholder_postal_addresses',
            'director_appointment_date', 'director_ceased_date', 'is_director'
        ];

        foreach ($variables as $variable) {

            //  Declare dynamic variable names with an empty value
            $$variable = '';

        }

        //  Overide the variable names with actual ownership bundle information
        $company_uin = $ownership_bundle->company->uin;
        $company_name = $ownership_bundle->company->name;
        $company_status = $ownership_bundle->company->company_status;
        $company_type = $ownership_bundle->company->company_type;
        $company_sub_type = $ownership_bundle->company->company_sub_type;
        $company_incorporation_date = $ownership_bundle->company->incorporation_date;

        $shareholder_percentage_of_shares = $ownership_bundle->percentage_of_shares->original;
        $shareholder_number_of_shares = $ownership_bundle->number_of_shares;
        $shareholder_total_shares = $ownership_bundle->total_shares;
        $shareholder_name = $ownership_bundle->shareholder_name;

        $total_shareholder_occurances = $ownership_bundle->total_shareholder_occurances;
        $is_shareholder_to_self = $ownership_bundle->is_shareholder_to_self['name'];

        $shareholder = $ownership_bundle->shareholder;

        //  If we have a shareholder
        if( !empty($shareholder) ){

            //  Shareholder information
            $shareholder_owner_type = $shareholder->owner_type;
            $shareholder_nominee = $shareholder->nominee['name'];
            $shareholder_ceased_date = $shareholder->ceased_date;
            $shareholder_appointment_date = $shareholder->appointment_date;

            //  Additional Shareholder details (If Individual Shareholder)
            if( $shareholder->owner_type == 'individual' ){

                if( $shareholder->owner ){

                    //  Residential Addresses
                    $shareholder_residential_addresses = collect($shareholder->owner->addresses)->filter(function($address){
                        return $address->type == 'residential_address';
                    })->map(function($residential_address){
                        return $residential_address->address_line;
                    })->join(' | ');

                    //  Postal Addresses
                    $shareholder_postal_addresses = collect($shareholder->owner->addresses)->filter(function($address){
                        return $address->type == 'postal_address';
                    })->map(function($postal_address){
                        return $postal_address->address_line;
                    })->join(' | ');

                }

            }else if( $shareholder->owner_type == 'company' ){

                if( $shareholder->owner ){

                    $shareholder_uin = $shareholder->owner->uin;

                }

            }

        }

        //  If we have a director
        if( $ownership_bundle->director ){

            //  Director information
            $director_appointment_date = $ownership_bundle->director->appointment_date;
            $director_ceased_date = $ownership_bundle->director->ceased_date;

        }

        $is_director = $ownership_bundle->director_id ? 'Yes' : 'No';

        return [
            $company_uin,
            $company_name,
            $company_status,
            $company_type,
            $company_sub_type,
            $company_incorporation_date,

            $shareholder_name,
            ucfirst($shareholder_owner_type),
            $shareholder_uin,
            $total_shareholder_occurances,
            $is_shareholder_to_self,

            $shareholder_percentage_of_shares,
            $shareholder_number_of_shares,
            $shareholder_total_shares,

            $shareholder_nominee,
            $shareholder_appointment_date,
            $shareholder_ceased_date,

            $is_director,
            $director_appointment_date,
            $director_ceased_date,

            $shareholder_residential_addresses,
            $shareholder_postal_addresses,
        ];
    }
}
