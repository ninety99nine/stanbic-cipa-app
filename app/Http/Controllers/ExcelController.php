<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Exports\CompaniesExport;
use App\Imports\CompaniesImport;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function importExportView()
    {
       return view('excel.index');
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function exportExcel($type)
    {
        return Excel::download(new CompaniesExport, 'companies.'.$type);

        return redirect()->route('companies');
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function importExcel(Request $request)
    {
        Excel::import(new CompaniesImport, $request->import_file);

        \Session::put('success', 'Your file is imported successfully in database.');

        return back();
    }
}

