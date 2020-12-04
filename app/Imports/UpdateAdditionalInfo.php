<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class UpdateAdditionalInfo extends  Import implements ToModel, WithStartRow, WithHeadingRow, WithMultipleSheets, WithEvents, WithMapping, WithLimit, WithBatchInserts, WithValidation, SkipsOnFailure, SkipsOnError
{

    use Importable,
        RegistersEventListeners,
        SkipsFailures,
        SkipsErrors;


    public function sheets(): array
    {
        return [
            'Update Students' => $this
        ];
    }

    public function registerEvents(): array {
        // TODO: Implement registerEvents() method.
        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $this->sheetNames[] = $event->getSheet()->getTitle();
                $this->worksheet = $event->getSheet();
                $worksheet = $event->getSheet();
                $this->highestRow = $worksheet->getHighestDataRow('B');
            }
        ];
    }

    /**
     * Read every row and update specific fields
     *
     * @param array $row
     * @return void
     */
    public function model(array $row)
    {
        //implement following insertions
        //TODO:39
        //TODO:40
        //TODO:41
        //TODO:42
        //TODO:43
        //TODO:44
        //TODO:68
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function rules(): array
    {
        //implement validations for before insertions
        //TODO:39
        //TODO:40
        //TODO:41
        //TODO:42
        //TODO:43
        //TODO:44
        //TODO:68
        // 0 => "remarks"
        // 1 => "student_idnsid"
        // 2 => "type_of_device_the_student_own"
        // 3 => "type_of_device_the_students_home_has"
        // 4 => "internet_connectivity_at_home"
        // 5 => "method_of_access_to_internet"
        // 6 => "has_a_tv_at_home"
        // 7 => "has_a_satellite_tv_connection_at_home"
        // 8 => "electricity_at_home"
        // 9 => "tv_channel_1"
        // 10 => "tv_channel_2"
        // 11 => "tv_channel_3"
        // 12 => "tv_channel_4"
        // 13 => "tv_channel_5"
        // 14 => "radio_channel_1"
        // 15 => "radio_channel_2"
        // 16 => "radio_channel_3"
        // 17 => "radio_channel_4"
        // 18 => "radio_channel_5"
        return [
            '*.student_idnsid' => 'required|exists:security_users,openemis_no|is_student_in_class:'.$this->file['institution_class_id'],
        ];
    }
}
