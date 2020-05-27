<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\Examination_student;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExaminationStudentsExport implements FromCollection , WithHeadings
{

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
    public function collection()
    {
        //
        return Examination_student::all();
    }
}
