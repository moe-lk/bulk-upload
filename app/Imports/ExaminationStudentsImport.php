<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Examination_student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ExaminationStudentsImport implements ToModel, WithStartRow, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use Importable;

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @return int
     */
    public function headingRow(): int
    {
        return 1;
    }

    public function chunkSize(): int
    {
        return 10000;
    }

    public function batchSize(): int
    {
        return 10000;
    }

    private function transformDateTime(string $value, string $format = 'm/d/Y')
    {
        $date = date_create_from_format('m/d/Y', $value);
        return date_format($date, 'Y-m-d');
    }


    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {
       
        $insertData = array(
            'st_no' => $row['st_no'],
            'stu_no' => $row['stu_no'],
            "f_name" => $row['f_name'],
            "medium" => $row['medium'],
            "gender" => $row['gender'],
            "b_date" =>  $this->transformDateTime($row['b_date']),
            "a_income" => $row['a_income'],
            "schoolid" => $row['schoolid'],
            "spl_need" => $row['spl_need'],
            "pvt_address" => $row['pvt_address'],
            "disability_type" => $row['disability_type'],
            "disability" => $row['disability'],
            "sp_center" => $row['sp_center']
        );
        Examination_student::insertData($insertData);
    }
}
