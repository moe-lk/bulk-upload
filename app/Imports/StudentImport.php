<?php

namespace App\Imports;

use App\Institution_student;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeSheet;

class StudentImport implements WithMultipleSheets, WithEvents
{

    public function __construct()
    {
        $this->sheetNames = [];
        $this->sheetData = [];
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

    public function sheets(): array
    {
        return [

            0 => $this
        ];
    }

    public function array(array $array){
        $this->sheetData[] = $array;
    }

    public function registerEvents(): array
    {
        // TODO: Implement registerEvents() method.

        return [
            BeforeSheet::class => function(BeforeSheet $event) {
                $this->sheetNames[] = $event->getSheet()->getTitle();

            }
        ];

    }

}
