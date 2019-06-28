<?php

namespace App\Exports;

use App\Security_user;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Import_mapping;
use Illuminate\Support\Facades\Log;


class UsersExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    use Exportable;

    private $fileName = 'users.xls';
    


	public function headings(): array
    {
        $columns =  Import_mapping::where('model', '=', 'Student.Info')
        ->orderBy('order')
        ->get()->toArray();
        $headers = [];
        foreach($columns as $column){
        
            $headers[] = $column['description'];
        }
        return $headers;
    }

	public function collection()
	{
        $queryResults = Import_mapping::select('column_name')->where('model', '=', 'Student.Info')
            ->orderBy('order')
            ->get()->toArray();
        $columns = [];
        foreach ($queryResults as $column) {
            $columns[] = $column['column_name'];
        }

		return Security_user::all($columns);
    }

}
