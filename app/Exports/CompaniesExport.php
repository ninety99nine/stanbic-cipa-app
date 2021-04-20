<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class CompaniesExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths
{
    private $companies;

    public function __construct($companies)
    {
        $this->companies = $companies;
    }

    public function query()
    {
        return $this->companies;
    }

    public function headings(): array
    {
        return [
            'UIN', 'Name', 'Info', 'Company Status', 'Exempt', 'Foreign Company', 'Company Type', 'Company Sub Type',
            'Incorporation Date', 'Re-registration Date', 'Old Company Number', 'Dissolution Date', 'Own Constitution Yn',
            'Business Sector', 'Annual Return Filing Month', 'Annual Return Last Filed Date', 'Last Updated (With CIPA)',

            //  Attributes
            'Is Registered', 'Is Cancelled', 'Is Removed', 'Is Compliant'
        ];
    }
    public function columnWidths(): array
    {
        return [
            'UIN' => 100
        ];
    }

    public function map($company): array
    {
        return [
            $company->uin,
            $company->name,
            $company->info,
            $company->company_status,
            $company->exempt['name'],
            $company->foreign_company['name'],
            $company->company_type,
            $company->company_sub_type,
            !empty($company->incorporation_date) ? $company->incorporation_date : '',
            $company->re_registration_date,
            $company->old_company_number,
            $company->dissolution_date,
            $company->own_constitution_yn['name'],
            $company->business_sector,
            $company->annual_return_filing_month['long_name'],
            $company->annual_return_last_filed_date,
            $company->cipa_updated_at,

            //  Attributes
            $company->is_registered['name'],
            $company->is_cancelled['name'],
            $company->is_removed['name'],
            $company->is_compliant['name']
        ];
    }
}
