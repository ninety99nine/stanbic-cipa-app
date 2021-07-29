<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class DirectorsExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths
{
    private $directors;

    public function __construct($directors)
    {
        $this->directors = $directors;
    }

    public function query()
    {
        return $this->directors;
    }

    public function headings(): array
    {
        return [

            //  Company
            'UIN', 'Name', 'Company Status', 'Company Type', 'Company Sub Type', 'Incorporation Date',

            //  Director
            'Director Name', 'Appointed Date', 'Ceased Date', 'Residential Address', 'Postal Address'
        ];

    }
    public function columnWidths(): array
    {
        return [
            'UIN' => 100
        ];
    }

    public function map($director): array
    {
        $variables = [
            'company_uin', 'company_name', 'company_status', 'company_type', 'company_sub_type', 'company_incorporation_date',
            'director_name', 'appointment_date', 'ceased_date', 'residential_addresses', 'postal_addresses',
        ];

        foreach ($variables as $variable) {

            //  Declare dynamic variable names with an empty value
            $$variable = '';

        }

        $appointment_date = $director->appointment_date;
        $ceased_date = $director->ceased_date;

        if( $director->individual ){

            //  Overide the variable names with actual individual information
            $director_name = $director->individual->full_name;
            $postal_addresses = $director->individual->postal_address_lines;
            $residential_addresses = $director->individual->residential_address_lines;

        }

        if( $director->company ){

            //  Overide the variable names with actual company information
            $company_uin = $director->company->uin;
            $company_name = $director->company->name;
            $company_status = $director->company->company_status;
            $company_type = $director->company->company_type;
            $company_sub_type = $director->company->company_sub_type;
            $company_incorporation_date = $director->company->incorporation_date;

        }

        return [
            $company_uin,
            $company_name,
            $company_status,
            $company_type,
            $company_sub_type,
            $company_incorporation_date,

            $director_name,
            $appointment_date,
            $ceased_date,
            $residential_addresses,
            $postal_addresses
        ];
    }
}
