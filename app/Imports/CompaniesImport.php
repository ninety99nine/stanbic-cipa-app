<?php

namespace App\Imports;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CompaniesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, WithUpserts
{
    public function model(array $row)
    {
        if ( !isset( $row['uin'] ) || empty( $row['uin'] ) ) {
            return null;
        }

        return new Company([
            'uin' => $row['uin']
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function uniqueBy()
    {
        return 'uin';
    }
}
