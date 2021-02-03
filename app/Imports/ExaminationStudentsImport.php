<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Examination_student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ExaminationStudentsImport implements ToModel, WithStartRow, WithHeadingRow, WithChunkReading, WithBatchInserts , WithValidation ,WithMapping, SkipsOnFailure, SkipsOnError
{
    use Importable , SkipsFailures, SkipsErrors;

    public function __construct($year,$grade)
    {
        $this->year = $year;
        $this->grade = $grade;
    }
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

    public function map($row): array
    {
        $row['b_date'] = $this->transformDateTime($row['b_date']);
        return $row;
    }


    private function transformDateTime(string $value, string $format = 'm/d/Y')
    {
        try{
            $date = date_create_from_format('m/d/Y', $value);
            if(gettype($date)=='boolean'){
                $date = date_create_from_format('Y-m-d', $value);
            }
            $date = date_format($date, 'Y-m-d');
            return  $date;
        }catch(\Exception $e){
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failure = new Failure(2, 'remark', [0 => 'The given date is wrong']);
            $failures = [0 => $failure];
            new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
        }
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
            "b_date" =>  $row['b_date'],
            "a_income" => $row['a_income'] ? $row['a_income'] : 0 ,
            "grade" => $this->grade,
            'year' => $this->year,
            "schoolid" => $row['schoolid'],
            "spl_need" => $row['spl_need'],
            "pvt_address" => $row['pvt_address'],
            "disability_type" => $row['disability_type'],
            "disability" => $row['disability'],
            "sp_center" => $row['sp_center']
        );
        Examination_student::insertData($insertData);
    }

    public function rules(): array
    {
        return [
            'b_date' => 'date|required',
            'schoolid' => 'exists:institutions,code'
        ];
    }
}
