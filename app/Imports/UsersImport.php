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
use App\Models\User_contact;
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
use App\Imports\StudentUpdate;
use Maatwebsite\Excel\Exceptions\ConcernConflictException;

class UsersImport extends Import implements ToModel, WithStartRow, WithHeadingRow, WithMultipleSheets, WithEvents, WithMapping, WithLimit, WithBatchInserts, WithValidation, SkipsOnFailure, SkipsOnError
{

    use Importable, SkipsFailures, SkipsErrors;


    public function sheets(): array
    {
        return [
            'Insert Students' => $this,
        ];
    }


    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetNames[] = $event->getSheet()->getTitle();
                $this->worksheet = $event->getSheet();
                $this->validateClass();
                $this->highestRow = $this->worksheet->getHighestDataRow(); // e.g. 10
                if ($this->highestRow < 3) {
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'remark', [0 => 'No enough rows!'], [null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                }
            },
            BeforeImport::class => function (BeforeImport $event) {
                $this->highestRow = ($event->getReader()->getDelegate()->getActiveSheet()->getHighestDataRow('C'));
                if ($this->highestRow < 3) {
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'remark', [0 => 'No enough rows!'], [null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                }
            }
        ];
    }




    public function model(array $row)
    {
        try {
            $institutionClass = Institution_class::find($this->file['institution_class_id']);
            $institution = $institutionClass->institution_id;
            if (!array_filter($row)) {
                return null;
            }

            if (!empty($institutionClass)) {
                $row = $this->setGender($row);
                $studentInfo = Security_user::createOrUpdateStudentProfile($row,'create',$this->file);  
                $academicPeriod = Academic_period::where('id', '=', $institutionClass->academic_period_id)->first();
                $institutionGrade = Institution_class_grade::where('institution_class_id', '=', $institutionClass->id)->first();
                $assignee_id = $institutionClass->staff_id ? $institutionClass->staff_id : $this->file['security_user_id'];
              
                $params = [
                    'assignee_id' => $assignee_id,
                    'academic_period' => $academicPeriod,
                    'institution' => $institution,
                    'institution_grade' => $institutionGrade,
                    'institution_class' => $institutionClass
                ];

                Institution_student_admission::createAdmission($studentInfo->id,$row,$params,$this->file);
                Institution_student::createOrUpdate($studentInfo->id,$row,$params,$this->file);
                $student = Institution_class_student::createOrUpdate($studentInfo->id,$params,$this->file);
                User_special_need::createOrUpdate($student->student_id,$row,$this->file);
                User_body_mass::createOrUpdate($student->student_id,$row,$this->file);

                $this->createOrUpdateGuardian($row,$student,'father');
                $this->createOrUpdateGuardian($row,$student,'mother');
                $this->createOrUpdateGuardian($row,$student,'guardian');
                
                $studentInfo['student_id'] = $studentInfo->id;
                Institution_student::updateStudentArea($studentInfo->toArray());

                $this->insertOrUpdateSubjects($row,$student,$institution);

                $totalStudents = Institution_class_student::getStudentsCount($this->file['institution_class_id']);
                if ($totalStudents['total'] > $institutionClass->no_of_students) {
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'rows', [3 => 'Class student count exceeded! Max number of students is ' . $institutionClass->no_of_students], [null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                    Log::info('email-sent', [$this->file]);
                }

                $institutionClass = new Institution_class();
                $institutionClass->updateClassCount($this->file);
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failures = $e->failures();
            throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
            Log::info('email-sent', [$e]);
        }
    }

    public function rules(): array
    {

        return [
            '*.full_name' => 'required|regex:/^[a-zA-Z .]*$/u|max:256',
            '*.preferred_name' => 'nullable|regex:/^[a-zA-Z .]*$/u|max:90',
            '*.gender_mf' => 'required|in:M,F',
            '*.date_of_birth_yyyy_mm_dd' => 'date|required|admission_age:' . $this->file['institution_class_id'],
            '*.address' => 'nullable',
            '*.birth_registrar_office_as_in_birth_certificate' => 'nullable|exists:area_administratives,name|required_if:identity_type,BC|birth_place',
            '*.birth_divisional_secretariat' => 'nullable|exists:area_administratives,name|required_with:birth_registrar_office_as_in_birth_certificate',
            '*.nationality' => 'required',
            '*.identity_type' => 'nullable|required_with:*.identity_number|in:NIC,BC',
            '*.identity_number' => 'nullable|identity:identity_type|required_with:*.identity_type',
            '*.education_grade' => 'required',
            '*.option_*' => 'nullable|exists:education_subjects,name',
            '*.bmi_height' => 'bail|required_with:*.bmi_weight|bmi:' . $this->file['institution_class_id'],
            '*.bmi_weight' => 'bail|required_with:*.bmi_height|bmi:' . $this->file['institution_class_id'],
            '*.bmi_date_yyyy_mm_dd' => 'bail|required_with:*.bmi_height|date', //bmi:'. $this->file['institution_class_id'].'
            '*.bmi_academic_period' => 'bail|required_with:*.bmi_height|exists:academic_periods,name',
            '*.admission_no' => 'required|max:12|min:4|regex:/^[A-Za-z0-9\/]+$/',
            '*.start_date_yyyy_mm_dd' => 'required',
            '*.special_need_type' => 'nullable',
            '*.special_need' => 'nullable|exists:special_need_difficulties,name|required_if:special_need_type,Differantly Able',
            '*.fathers_full_name' => 'nullable|regex:/^[a-zA-Z .]*$/u',
            '*.fathers_date_of_birth_yyyy_mm_dd' => 'required_with:fathers_full_name',
            '*.fathers_address' => 'required_with:fathers_full_name',
            '*.fathers_address_area' => 'required_with:fathers_full_name|nullable|exists:area_administratives,name',
            '*.fathers_phone' => 'nullable|required_with:fathers_full_name|regex:/[0-9]{9,10}/',
            '*.fathers_nationality' => 'required_with:fathers_full_name',
            '*.fathers_identity_type' => 'nullable|required_with:*.fathers_identity_number|in:NIC,BC',
            '*.fathers_identity_number' => 'nullable|required_with:*.fathers_identity_type|identity:fathers_identity_type',
            '*.mothers_full_name' => 'nullable|regex:/^[a-zA-Z .]*$/u',
            '*.mothers_date_of_birth_yyyy_mm_dd' => 'required_with:mothers_full_name',
            '*.mothers_address' => 'required_with:mothers_full_name',
            '*.mothers_address_area' => 'required_with:mothers_full_name|nullable|exists:area_administratives,name',
            '*.mothers_phone' => 'nullable|required_with:mothers_full_name|regex:/[0-9]{9,10}/',
            '*.mothers_nationality' => "required_with:mothers_full_name",
            '*.mothers_identity_type' => "nullable|required_with:*.mothers_identity_number|in:NIC,BC",
            '*.mothers_identity_number' => 'nullable|identity:mothers_identity_type',
            '*.guardians_full_name' => 'nullable|required_without_all:*.fathers_full_name,*.mothers_full_name|regex:/^[a-zA-Z .]*$/u',
            '*.guardians_gender_mf' => 'required_with:guardians_full_name',
            '*.guardians_date_of_birth_yyyy_mm_dd' => 'sometimes|required_with:guardians_full_name',
            '*.guardians_address' => 'required_with:guardians_full_name',
            '*.guardians_address_area' => 'required_with:guardians_full_name|nullable|exists:area_administratives,name',
            '*.guardians_phone' => 'nullable|required_with:guardians_full_name|regex:/[0-9]{9,10}/',
            '*.guardians_nationality' => 'required_with:guardians_full_name',
            '*.guardians_identity_type' => 'nullable|required_with:*.guardians_identity_number|in:NIC,BC',
            '*.guardians_identity_number' => 'nullable|identity:guardians_identity_type',
        ];
    }
}