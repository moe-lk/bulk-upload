<?php

namespace App\Imports;

use Exception;
use App\Models\User;
use Webpatser\Uuid\Uuid;
use App\Models\Nationality;
use App\Rules\admissionAge;
use App\Models\User_contact;
use Lsf\UniqueUid\UniqueUid;
use App\Models\Identity_type;
use App\Models\Security_user;
use App\Models\User_identity;
use App\Imports\StudentUpdate;
use App\Models\Import_mapping;
use App\Models\Security_group;
use App\Models\User_body_mass;
use App\Models\Academic_period;
use App\Models\Student_guardian;
use App\Models\User_nationality;
use App\Models\Institution_class;
use App\Models\User_special_need;
use App\Mail\StudentCountExceeded;
use App\Mail\StudentImportSuccess;
use Illuminate\Support\Facades\DB;
use App\Models\Area_administrative;
use App\Models\Institution_student;
use App\Models\Institution_subject;
use App\Models\Workflow_transition;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Models\Institution_class_grade;
use App\Models\Special_need_difficulty;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Education_grades_subject;
use App\Models\Institution_class_student;
use App\Models\Institution_class_subject;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Jobs\AfterImportJob;
use App\Models\Institution_subject_student;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\Institution_student_admission;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Exceptions\ConcernConflictException;

class Import
{
    //Parent class for import script
    use Importable,
        RegistersEventListeners;

    public function __construct($file)
    {
        $this->sheetNames = [];
        $this->file = $file;
        $this->sheetData = [];
        $this->template = false;
        $this->worksheet = '';
        $this->failures = [];
        $this->request = new Request;
        $this->maleStudentsCount = 0;
        $this->femaleStudentsCount = 0;
        $this->highestRow = 0;
        $this->isValidSheet = true;
        $this->uniqueUid = new UniqueUid();
    }

    public function limit(): int
    {
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

    public function validateColumns($column, $existingColumns)
    {
        $columns = Config::get('excel.columns');
        $error = \Illuminate\Validation\ValidationException::withMessages([]);
        $this->failures = [];
        if (($column !== "") && (!in_array($column, $columns))) {
            $this->isValidSheet = false;
            $this->error[] = 'Unsupported column found ,remove:' . $column;
            $this->failure = new Failure(3, 'remark', $this->error, [null]);
            $this->failures = new \Maatwebsite\Excel\Validators\ValidationException($error, [$this->failure]);
        }
        if (is_object($this->failures)) {
            throw $this->failures;
        }
    }

    public function validateColumnsToMap($existingColumns)
    {
        $columns = Config::get('excel.columns');
        $optional_columns = Config::get('excel.optional_columns');
        $columns = array_diff($columns, $optional_columns);
        $error = \Illuminate\Validation\ValidationException::withMessages([]);
        $this->failures = [];
        foreach ($columns as  $column) {
            if (($column !== "") && (!in_array($column, $existingColumns))) {
                $this->isValidSheet = false;
                $this->error[] = 'Missing Column :' . $column . ' Not found';
                $this->failure = new Failure(3, 'remark', $this->error, [null]);
                $this->failures = new \Maatwebsite\Excel\Validators\ValidationException($error, [$this->failure]);
            }
        }
        if (is_object($this->failures)) {
            throw $this->failures;
        }
    }



    public function batchSize(): int
    {
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


    public function startRow(): int
    {
        return 3;
    }

    public function headingRow(): int
    {
        return 2;
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
        } catch (Exception $e) {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failure = new Failure(3, 'remark', [0 => 'Template is not valid for upload, use the template given in the system ' . $row[$column] . ' Not a valid date formate'], [null]);
            $failures = [0 => $failure];
            throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
        }
    }


    protected function mapFields($row)
    {

        $keys = array_keys($row);

        $this->validateColumnsToMap($keys);
        array_walk($keys, array($this, 'validateColumns'));
        $row = $this->formateDate($row, 'date_of_birth_yyyy_mm_dd');
        $row = $this->formateDate($row, 'bmi_date_yyyy_mm_dd');
        $row = $this->formateDate($row, 'start_date_yyyy_mm_dd');
        $row = $this->formateDate($row, 'fathers_date_of_birth_yyyy_mm_dd');
        $row = $this->formateDate($row, 'mothers_date_of_birth_yyyy_mm_dd');
        $row = $this->formateDate($row, 'guardians_date_of_birth_yyyy_mm_dd');

        $row['admission_no'] =  str_pad($row['admission_no'], 4, '0', STR_PAD_LEFT);

        if (array_key_exists('identity_type', $row)) {
            if ($row['identity_type'] == 'BC' && (!empty($row['birth_divisional_secretariat'])) && ($row['identity_number'] !== null) && $row['date_of_birth_yyyy_mm_dd'] !== null) {
                $row['identity_number'] =  str_pad($row['identity_number'], 4, '0', STR_PAD_LEFT);
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
        }
        return $row;
    }

    protected function checkKeys($key, $count, $row)
    {
        if (array_key_exists($key, $row)) {
            return true;
        } else {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failure = new Failure($count, 'remark', [0 => 'Template is not valid for upload, use the template given in the system ' . $key, ' Is missing form the template'], [null]);
            $failures = [0 => $failure];
            new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
        };
    }


    public function array(array $array)
    {
        $this->sheetData[] = $array;
    }

    /**
     * @param mixed $row
     * @return array
     * @throws \Exception
     */
    public function map($row): array
    {
        $row = $this->mapFields($row);
        return $row;
    }


    public function validateClass()
    {

        $institutionClass = Institution_class::find($this->file['institution_class_id']);
        $totalMaleStudents = $institutionClass->total_male_students;
        $totalFemaleStudents = $institutionClass->total_female_students;
        $totalStudents = $totalMaleStudents + $totalFemaleStudents;

        $exceededStudents = ($totalStudents + $this->limit()) > $institutionClass->no_of_students ? true : false;
        if ($exceededStudents == true) {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failure = new Failure(3, 'remark', ['Class student count exceeded! Max number of students is' . $institutionClass->no_of_students], [null]);
            $failures = [0 => $failure];
            throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
            Log::info('email-sent', [$this->file]);
        } else {
            return true;
        }
    }

    public function getNode()
    {
        return $this->file['node'];
    }

    /**
     * @param array $options
     * @return string
     */
    public  function getUniqueOpenemisId($options = [])
    {
        return Uuid::generate(4);
    }


    protected function updateSubjectCount($subject)
    {
        $totalStudents = Institution_subject_student::getStudentsCount($subject['institution_subject_id']);
        Institution_subject::where(['id' => $subject['institution_subject_id']])
            ->update([
                'total_male_students' => $totalStudents['total_male_students'],
                'total_female_students' => $totalStudents['total_female_students']
            ]);
    }


    /**
     *
     */
    protected function setStudentSubjects($subject)
    {
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

    protected function insertSubject($subject)
    {
        if (!Institution_subject_student::isDuplicated($subject)) {
            Institution_subject_student::updateOrInsert($subject);
        }
        $this->updateSubjectCount($subject);
    }

    public function createOrUpdateGuardian($row, $student, $param)
    {
        if (!empty($row[$param . 's_full_name']) && ($row[$param . 's_date_of_birth_yyyy_mm_dd'] !== null)) {
            $guardian = Security_user::createOrUpdateGuardianProfile($row, $param, $this->file);
            if (!is_null($guardian)) {
                Security_user::where('id', '=', $guardian->id)
                    ->update(['is_guardian' => 1]);
                $guardian['guardian_relation_id'] = $this->setRelation($param, $guardian);
                Student_guardian::createStudentGuardian($student, $guardian, $this->file['security_user_id']);
            }
        }
    }

    protected function setRelation($param, $guardian)
    {
        switch ($param) {
            case 'father':
                return 1;
            case 'mother':
                return 2;
            case 'guardian':
                return 3;
        }
    }

    protected function setGender($row)
    {
        switch ($row['gender_mf']) {
            case 'M':
                $row['gender_mf'] = 1;
                $this->maleStudentsCount += 1;
                break;
            case 'F':
                $row['gender_mf'] = 2;
                $this->femaleStudentsCount += 1;
                break;
        }
        return $row;
    }

    protected function insertOrUpdateSubjects($row, $student, $institution)
    {
        $mandatorySubject = Institution_class_subject::getMandatorySubjects($this->file['institution_class_id']);
        $subjects = getMatchingKeys($row);
        $optionalSubjects =  Institution_class_subject::getStudentOptionalSubject($subjects, $student, $row, $institution);
        $allSubjects = array_merge_recursive($optionalSubjects, $mandatorySubject);
        if (!empty($allSubjects)) {
            $allSubjects = unique_multidim_array($allSubjects, 'institution_subject_id');
            $this->student = $student;
            $allSubjects = array_map(array($this, 'setStudentSubjects'), $allSubjects);
            $allSubjects = unique_multidim_array($allSubjects, 'education_subject_id');
            array_walk($allSubjects, array($this, 'insertSubject'));
            array_walk($allSubjects, array($this, 'updateSubjectCount'));
        }
    }
}
