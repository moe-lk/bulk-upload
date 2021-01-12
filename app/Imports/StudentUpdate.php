<?php

namespace App\Imports;

use App\Models\User;
use function foo\func;
use App\Models\Nationality;
use App\Rules\admissionAge;
use App\Models\User_contact;
use App\Models\Identity_type;
use App\Models\Security_user;
use App\Models\User_identity;
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
use App\Models\Institution_class_grade;
use App\Models\Special_need_difficulty;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Education_grades_subject;
use App\Models\Institution_class_student;
use App\Models\Institution_class_subject;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
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

class StudentUpdate extends Import implements  ToModel, WithStartRow, WithHeadingRow, WithMultipleSheets, WithEvents, WithMapping, WithLimit, WithBatchInserts, WithValidation , SkipsOnFailure , SkipsOnError{

    use Importable,
        RegistersEventListeners,
        SkipsFailures,
        SkipsErrors;


    public function sheets(): array {
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


    public function model(array $row) {

        try {
            $institutionClass = Institution_class::find($this->file['institution_class_id']);
            $institution = $institutionClass->institution_id;
            if (!empty($institutionClass)) {
                $this->setGender($row);
                $studentInfo = Security_user::createOrUpdateStudentProfile($row,'update',$this->file);
                $student = Institution_class_student::where('student_id', '=', $studentInfo->id)->first();
                if(!empty($row['admission_no'])){
                    Institution_student::where('student_id','=',$studentInfo->id)
                    ->where('institution_id','=', $institution)
                    ->update(['admission_id'=> $row['admission_no']]);
                }
                User_special_need::createOrUpdate($studentInfo->id,$row,$this->file);
                User_body_mass::createOrUpdate($studentInfo->id,$row,$this->file);

                $this->createOrUpdateGuardian($row,$student,'father');
                $this->createOrUpdateGuardian($row,$student,'mother');
                $this->createOrUpdateGuardian($row,$student,'guardian');

                $studentInfo['student_id'] = $studentInfo->id;
                Institution_student::updateStudentArea($studentInfo->toArray());
                $this->insertOrUpdateSubjects($row,$student,$institution);
                $totalStudents = Institution_class_student::getStudentsCount($this->file['institution_class_id']);
                Institution_class::where('id', '=', $institutionClass->id)
                        ->update([
                            'total_male_students' => $totalStudents['total_male_students'],
                            'total_female_students' => $totalStudents['total_female_students']]);
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failures = $e->failures();
            throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
            Log::info('email-sent', [$e]);
        }
        unset($row);
    }

    public function getStudentSubjects($student) {
        return Institution_subject_student::where('student_id', '=', $student->student_id)
                        ->where('institution_class_id', '=', $student->institution_class_id)->get()->toArray();
    }


    public function rules(): array {

        return [
            '*.student_id' => 'required|exists:security_users,openemis_no|is_student_in_class:'.$this->file['institution_class_id'],
            '*.full_name' => 'nullable|regex:/^[a-zA-Z .]*$/u|max:256',
            '*.preferred_name' => 'nullable|regex:/^[a-zA-Z .]*$/u|max:90',
            '*.gender_mf' => 'nullable|in:M,F',
            '*.date_of_birth_yyyy_mm_dd' => 'date|nullable',
            '*.address' => 'nullable',
            '*.birth_registrar_office_as_in_birth_certificate' => 'nullable|exists:area_administratives,name|required_if:identity_type,BC|birth_place',
            '*.birth_divisional_secretariat' => 'nullable|exists:area_administratives,name|required_with:birth_registrar_office_as_in_birth_certificate',
            '*.nationality' => 'nullable',
            '*.identity_type' => 'nullable|required_with:*.identity_number',
            '*.identity_number' => 'nullable|identity:identity_type|required_with:*.identity_type',
            '*.education_grade' => 'nullable|exists:education_grades,code',
            '*.option_*' => 'nullable|exists:education_subjects,name',
            '*.bmi_height' => 'required_with:*.bmi_weight|nullable|numeric|max:200|min:60',
            '*.bmi_weight' => 'required_with:*.bmi_height|nullable|numeric|max:200|min:10',
            '*.bmi_date_yyyy_mm_dd' => 'required_with:*.bmi_height|nullable|date',
            '*.bmi_academic_period' => 'required_with:*.bmi_weight|nullable|exists:academic_periods,name',
            '*.admission_no' => 'nullable|min:4|max:12|regex:/^[A-Za-z0-9\/]+$/',
            '*.start_date_yyyy_mm_dd' => 'nullable|date',
            '*.special_need_type' => 'nullable',
            '*.special_need' => 'nullable|exists:special_need_difficulties,name|required_if:special_need_type,Differantly Able',
            '*.fathers_full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.fathers_date_of_birth_yyyy_mm_dd' => 'nullable|required_with:*.fathers_full_name',
            '*.fathers_address' => 'required_with:*.fathers_full_name',
            '*.fathers_address_area' => 'required_with:*.fathers_full_name|nullable|exists:area_administratives,name',
            '*.fathers_nationality' => 'required_with:*.fathers_full_name',
            '*.fathers_identity_type' => 'nullable|required_with:*.fathers_identity_number|in:NIC,BC',
            '*.fathers_identity_number' => 'nullable|required_with:*.fathers_identity_type|identity:fathers_identity_type',
            '*.mothers_full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.mothers_date_of_birth_yyyy_mm_dd' => 'nullable|required_with:*.mothers_full_name',
            '*.mothers_address' => 'required_with:*.mothers_full_name',
            '*.mothers_address_area' => 'required_with:*.mothers_full_name|nullable|exists:area_administratives,name',
            '*.mothers_nationality' => "required_with:*.mothers_full_name",
            '*.mothers_identity_type' => "nullable|required_with:*.mothers_identity_number|in:NIC,BC",
            '*.mothers_identity_number' => 'nullable|identity:mothers_identity_type',
            '*.guardians_full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.guardians_gender_mf' => 'required_with:*.guardians_full_name',
            '*.guardians_date_of_birth_yyyy_mm_dd' => 'nullable|required_with:*.guardians_full_name',
            '*.guardians_address' => 'required_with:*.guardians_full_name',
            '*.guardians_address_area' => 'required_with:*.guardians_full_name|nullable|exists:area_administratives,name',
            '*.guardians_nationality' => 'required_with:*.guardians_full_name',
            '*.guardians_identity_type' => 'nullable|required_with:*.guardians_identity_number|in:NIC,BC',
            '*.guardians_identity_number' => 'nullable|identity:guardians_identity_type',
        ];
    }

}
