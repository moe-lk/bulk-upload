<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Examination_student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
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

    protected function formateDate($row, $column, $format = 'Y-m-d')
    {
        try {
            if (!empty($row[$column]) && ($row[$column] !== null)) {
                switch (gettype($row[$column])) {
                    case 'string':
                        $row[$column] = preg_replace('/[^A-Za-z0-9\-]/', '-', $row[$column]);
                        $row[$column] = date($format, strtotime($row[$column])); //date($row[$column]);
                        $row[$column] =  \Carbon\Carbon::createFromFormat($format, $row[$column]);
                        break;
                    case 'double';
                        $row[$column] =  \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[$column]);
                        break;
                }
            }
            return $row;
        } catch (\Exception $e) {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failure = new Failure(3, 'remark', [0 => 'Template is not valid for upload, use the template given in the system ' . $row[$column] . ' Not a valid date formate'], [null]);
            $failures = [0 => $failure];
            throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
        }
    }

    /**
     * @param Collection $collection
     */
    public function model(array $row)
    {

        $this->formateDate($row,'b_date');
        if(array_keys($row)){
            $insertData = array(
                'st_no' => $row['st_no'],
                'stu_no' => $row['stu_no'],
                "f_name" => $row['f_name'],
                "medium" => $row['medium'],
                "gender" => $row['gender'],
                "b_date" =>   $row['b_date'],
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
}
