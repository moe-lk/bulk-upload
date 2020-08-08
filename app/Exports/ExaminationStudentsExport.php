<?php

namespace App\Exports;

use App\Models\Examination_student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExaminationStudentsExport implements FromQuery , WithHeadings , ShouldQueue 
{

    use Exportable;
    
    public function headings(): array
    {
        return [
            'st_no',
            'stu_no',
            'nsid',
            'schoolid',
            'f_name',
            'medium',
            'b_date',
            'gender',
            'pvt_address',
            'a_income',
            'spl_need',
            'disability_type',
            'disability',
            'sp_center',
            'created_at',
            'updated_at'
        ];
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        return Examination_student::query();
    }
}
