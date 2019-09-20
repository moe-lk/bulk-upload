<?php

namespace App\Imports;

use App\Mail\StudentCountExceeded;
use App\Mail\StudentImportSuccess;
use App\Models\Education_grades_subject;
use App\Models\Institution_class_student;
use App\Models\Institution_class_subject;
use App\Models\Institution_student_admission;
use App\Models\Institution_subject;
use App\Models\Institution_subject_student;
use App\Models\User_special_need;
use App\Models\Security_group;
use App\Models\Security_user;
use App\Models\User;
use App\Models\User_body_mass;
use App\Models\Institution_student;
use App\Models\Import_mapping;
use App\Models\Identity_type;
use App\Models\Student_guardian;
use App\Models\Academic_period;
use App\Models\Institution_class;
use App\Models\Institution_class_grade;
use App\Models\Area_administrative;
use App\Models\Special_need_difficulty;
use App\Models\Workflow_transition;
use App\Models\User_nationality;
use App\Models\User_identity;
use App\Models\Nationality;
use App\Rules\admissionAge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Jobs\AfterImportJob;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Webpatser\Uuid\Uuid;
use Exception;
use App\Imports\StudentUpdate;
use Maatwebsite\Excel\Exceptions\ConcernConflictException;

class Import  
{
    //Parent class for import script
    use Importable,
    RegistersEventListeners;

    public function __construct($file) {
        $this->sheetNames = [];
        $this->file = $file;
        $this->sheetData = [];
        $this->worksheet = '';
        $this->failures = [];
        $this->request = new Request;
        $this->maleStudentsCount = 0;
        $this->femaleStudentsCount = 0;
        $this->highestRow = 0;
        $this->isValidSheet = true;
    }

    public function limit(): int {
        $highestColumn = $this->worksheet->getHighestDataColumn(3);
        $higestRow = 0;
        for ($row = $this->startRow(); $row <= $this->highestRow; $row++) {
            $rowData = $this->worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
            if (isEmptyRow(reset($rowData))) {
                continue;
            } else {
                $higestRow += 1;
            }
        }
        return $higestRow;
    }

    public function validateColumns($column) {
        $columns = [
            "remarks",
            "student_id",
            "full_name",
            "gender_mf",
            "date_of_birth_yyyy_mm_dd",
            "address",
            "birth_registrar_office_as_in_birth_certificate",
            "birth_divisional_secretariat",
            "nationality",
            "identity_type",
            "identity_number",
            "special_need_type",
            "special_need",
            "bmi_academic_period",
            "bmi_date_yyyy_mm_dd",
            "bmi_height",
            "bmi_weight",
            "admission_no",
            "academic_period",
            "education_grade",
            "start_date_yyyy_mm_dd",
            "option_1",
            "option_2",
            "option_3",
            "option_4",
            "option_5",
            "option_6",
            "option_7",
            "option_8",
            "option_9",
            "fathers_full_name",
            "fathers_date_of_birth_yyyy_mm_dd",
            "fathers_address",
            "fathers_address_area",
            "fathers_nationality",
            "fathers_identity_type",
            "fathers_identity_number",
            "mothers_full_name",
            "mothers_date_of_birth_yyyy_mm_dd",
            "mothers_address",
            "mothers_address_area",
            "mothers_nationality",
            "mothers_identity_type",
            "mothers_identity_number",
            "guardians_full_name",
            "name_with_initials",
            "guardians_gender_mf",
            "guardians_date_of_birth_yyyy_mm_dd",
            "guardians_address",
            "guardians_address_area",
            "guardians_nationality",
            "guardians_identity_type",
            "guardians_identity_number",
        ];

        
        if ( ($column !== "") && (!in_array($column,$columns))) {
            dd($column);
            $this->isValidSheet = false;
        }
    }


    public function batchSize(): int {
        $highestColumn = $this->worksheet->getHighestDataColumn(3);
        $higestRow = 1;
        for ($row = $this->startRow(); $row <= $this->highestRow; $row++) {
            $rowData = $this->worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
            if (isEmptyRow(reset($rowData))) {
                continue;
            } else {
                $higestRow += 1;
            }
        }
        if ($higestRow == 0) {
            exit;
        } else {
            return $higestRow;
        }
    }

    
    public function startRow(): int {
        return 3;
    }

    public function headingRow(): int {
        return 2;
    }


    protected function formateDate($row,$column,$format = 'Y-m-d'){
        switch (gettype($row[$column])){
            case 'string':
                $row[$column] = preg_replace('/[^A-Za-z0-9\-]/', '-', $row[$column]);
                $row[$column] = date($format, strtotime($row[$column])); //date($row[$column]);
                $row[$column] =  \Carbon\Carbon::createFromFormat($format, $row[$column]);
                break;
            case 'double';
                $row[$column] = date($format, strtotime($row[$column])); //date($row[$column]);
                $row[$column] =  \Carbon\Carbon::createFromFormat($format, $row[$column]);
                break;
        }
        return $row;
    }


    protected function mapFields($row){

        $keys = array_keys($row);
        array_walk($keys,array($this,'validateColumns'));
        $row = $this->formateDate($row,'date_of_birth_yyyy_mm_dd');
        $row = $this->formateDate($row,'bmi_date_yyyy_mm_dd');
        $row = $this->formateDate($row,'start_date_yyyy_mm_dd');
        $row = $this->formateDate($row,'fathers_date_of_birth_yyyy_mm_dd');
        $row = $this->formateDate($row,'mothers_date_of_birth_yyyy_mm_dd');
        $row = $this->formateDate($row,'guardians_date_of_birth_yyyy_mm_dd');

        if ($row['identity_type'] == 'BC' && (!empty($row['birth_divisional_secretariat'])) && ($row['identity_number'] !== null)) {
            // dd(($row['date_of_birth_yyyy_mm_dd']));
            $BirthDivision = Area_administrative::where('name', 'like', '%' . $row['birth_divisional_secretariat'] . '%')->where('area_administrative_level_id', '=', 5)->first();
            if ($BirthDivision !== null) {
                $BirthArea = Area_administrative::where('name', 'like', '%' . $row['birth_registrar_office_as_in_birth_certificate'] . '%')
                                ->where('parent_id', '=', $BirthDivision->id)->first();
                if ($BirthArea !== null) {
                    $row['identity_number'] = $BirthArea->id . '' . $row['identity_number'] . '' . substr($row['date_of_birth_yyyy_mm_dd']->format("yy"), -2) . '' . $row['date_of_birth_yyyy_mm_dd']->format("m");
                }
            }
        }
        return $row;
    }


    public function array(array $array) {
        $this->sheetData[] = $array;
    }

    /**
     * @param mixed $row
     * @return array
     * @throws \Exception
     */
    public function map($row): array {
        try {
         $row = $this->mapFields($row);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $error = \Illuminate\Validation\ValidationException::withMessages([]);
                $failure = new Failure(3, 'remark', [0 => 'Template is not valid for upload, use the template given in the system'], [null]);
                $failures = [0 => $failure];
                throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
        }
        return $row;
    }


    public function validateClass() {

        $institutionClass = Institution_class::find($this->file['institution_class_id']);

        $totalMaleStudents = $institutionClass->total_male_students;
        $totalFemaleStudents = $institutionClass->total_female_students;
        $totalStudents = $totalMaleStudents + $totalFemaleStudents;

        $exceededStudents = ($totalStudents + $this->limit()) > $institutionClass->no_of_students ? true : false;

        if ($exceededStudents == true) {
            try {
                $error = \Illuminate\Validation\ValidationException::withMessages([]);
                $failure = new Failure(3, 'remark', [3 => 'Class student count exceeded! Max number of students is' . $institutionClass->no_of_students], [null]);
                $failures = [0 => $failure];
                throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                Log::info('email-sent', [$this->file]);
            } catch (Exception $e) {
                Log::info('email-sending-failed', [$e]);
            }
        } else {
            return true;
        }
    }

    /**
     * @param array $options
     * @return string
     */
    public static function getUniqueOpenemisId($options = []) {
        $prefix = '';

        $prefix = DB::table('config_items')->where('code', '=', 'openemis_id_prefix')->get();
        $prefix = explode(",", $prefix);
        $prefix = ($prefix[1] > 0) ? $prefix[0] : '';

        $latest = Security_user::orderBy('id', 'DESC')
                ->first();

        if (is_array($latest)) {
            $latestOpenemisNo = $latest['SecurityUser']['openemis_no'];
        } else {
            $latestOpenemisNo = $latest->openemis_no;
        }
        if (empty($prefix)) {
            $latestDbStamp = $latestOpenemisNo;
        } else {
            $latestDbStamp = substr($latestOpenemisNo, strlen($prefix));
        }

        $currentStamp = time();

        if ($latestDbStamp >= $currentStamp) {
            $newStamp = $latestDbStamp + 1;
        } else {
            $newStamp = $currentStamp;
        }
        $check = Security_user::where('openemis_no', '=', $prefix . $newStamp)->first();
        if ($check === null) {
            return $prefix . $newStamp;
        } else {
            $newStamp = $latestOpenemisNo + random_int(1, 40);
            return $newStamp;
        }
    }


    protected function updateSubjectCount($subject) {
        $$totalStudents = Institution_subject_student::getStudentsCount($subject['institution_subject_id']);
        Institution_subject::where(['institution_subject_id' => $subject->institution_subject_id])
                ->update([
                    'total_male_students' => $totalStudents['total_male_students'],
                    'total_female_students' => $totalStudents['total_female_students']]);
    }


    /** 
     * 
     */
    protected function setStudentSubjects($subject){
        return [
            'id' => (string) Uuid::generate(4),
            'student_id' => $this->student->student_id,
            'institution_class_id' => $this->student->institution_class_id,
            'institution_subject_id' => $subject['institution_subject_id'],
            'institution_id' => $this->student->institution_id,
            'academic_period_id' => $this->student->academic_period_id,
            'education_subject_id' => $subject['institution_subject']['education_subject_id'],
            'education_grade_id' => $this->student->education_grade_id,
            'student_status_id' => 1,
            'created_user_id' => $this->file['security_user_id'],
            'created' => now()
        ];
    }

    protected function insertSubject($subject){
        if(!Institution_subject_student::isDuplicated($subject)){
            Institution_subject_student::updateOrInsert($subject);
        }     
    }


    public function getMandetorySubjects($institutionClass){
        $institutionGrade = Institution_class_grade::where('institution_class_id', '=', $institutionClass->id)->first();
        $mandatorySubject = Institution_class_subject::with(['institutionSubject'])
                        ->whereHas('institutionSubject', function ($query) use ($institutionGrade) {
                            $query->whereHas('institutionGradeSubject',function($query){
                                $query->where('auto_allocation',1);
                            })->where('education_grade_id', $institutionGrade->education_grade_id);
                            // ->where('auto_allocation', $institutionGrade->education_grade_id);
                        })
                        ->where('institution_class_id', '=', $institutionClass->id)
                        ->get()->toArray();
        return $mandatorySubject;                
    }

    public function getStudentOptionalSubject($subjects, $student, $row, $institution) {
        $data = [];
        foreach ($subjects as $subject) {
            $subjectId = Institution_class_subject::with(['institutionSubject'])
                            ->whereHas('institutionSubject', function ($query) use ($row, $subject, $student) {
                                $query->whereHas('institutionGradeSubject',function($query){
                                    $query->where('auto_allocation',0);
                                })
                                ->where('name', '=', $row[$subject])
                                ->where('education_grade_id', '=', $student->education_grade_id);
                            })
                            ->where('institution_class_id', '=', $student->institution_class_id)
                            ->get()->toArray();
            if (!empty($subjectId))
                $data[] = $subjectId[0];
        }
        return $data;
    }


}
