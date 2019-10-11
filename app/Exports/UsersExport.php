<?php

namespace App\Exports;

use App\Models\Security_user;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\Import_mapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Events\BeforeExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Files\LocalTemporaryFile;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Events\BeforeSheet;

class UsersExport implements FromCollection, WithEvents, WithStartRow, WithMultipleSheets {

    /**
     * @return \Illuminate\Support\Collection
     */
    use Exportable,
        RegistersEventListeners;

    public $_file_name = 'users.xls';

    public function __construct($class) {

        $this->class = $class;
        $this->_file_name = time() . '_' . Auth::user()->id . '_' . 'student_upload.xls';
    }

    public function registerEvents(): array {
        return [
        BeforeSheet::class => function(BeforeSheet $event){
//          dd($event->crea)  
        },
            BeforeWriting::class => function(BeforeWriting $event) {
                $file_path = 'app/censusNo_className_sis_students_bulk_upload_v1.xlsx';
                $event->writer->reopen(new LocalTemporaryFile(storage_path($file_path)), Excel::XLSX);
                $event->writer->getDelegate()->setActiveSheetIndex(1);
                return $event->getWriter()->getDelegate()->getActiveSheet();
            }
        ];
    }

    public function sheets(): array {
        return [
            1 => $this
        ];
    }

    public function startRow(): int {
        return 3;
    }

    public function collection() {

        $class = $this->class;
        return Security_user::select('openemis_no', 'first_name', 'gender_id', 'date_of_birth', 'address', 'birthplace_area_id')
                        ->with(['class', 'special_needs'])
                        ->whereHas('class', function ($query) use ($class) {
                            $query->where('institution_class_id', '=', $class);
                        })
                        ->get();
    }

}
