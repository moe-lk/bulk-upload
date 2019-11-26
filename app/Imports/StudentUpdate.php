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
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use function foo\func;
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
use Maatwebsite\Excel\Jobs\AfterImportJob;
use Maatwebsite\Excel\Validators\Failure;
use Webpatser\Uuid\Uuid;

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

                $this->validateClass();
                $worksheet = $event->getSheet();
                $this->highestRow = $worksheet->getHighestDataRow('B');
            },
            BeforeImport::class => function (BeforeImport $event) {
                $event->getReader()->getDelegate()->setActiveSheetIndex(2);
                $this->highestRow = ($event->getReader()->getDelegate()->getActiveSheet()->getHighestDataRow('B'));
                if ($this->highestRow < 3) {
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'remark', [0 => 'No enough rows!'], [null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                }
            }
        ];
    }


    public function model(array $row) {

        try {
            $institutionClass = Institution_class::find($this->file['institution_class_id']);
            $institution = $institutionClass->institution_id;


            if (!array_filter($row)) {
                return nulll;
            }

            if (!empty($institutionClass)) {
                $mandatorySubject = Institution_class_subject::getMandetorySubjects($this->file['institution_class_id']);
                $subjects = getMatchingKeys($row);
                $genderId = $row['gender_mf'] == 'M' ? 1 : 2;
                switch ($row['gender_mf']) {
                    case 'M':
                        $this->maleStudentsCount += 1;
                        break;
                    case 'F':
                        $this->femaleStudentsCount += 1;
                        break;
                }

                $BirthArea = Area_administrative::where('name', 'like', '%' . $row['birth_registrar_office_as_in_birth_certificate'] . '%')->first();
                $nationalityId = Nationality::where('name', 'like', '%' . $row['nationality'] . '%')->first();
                $identityType = Identity_type::where('national_code', 'like', '%' . $row['identity_type'] . '%')->first();
                $academicPeriod = Academic_period::where('name', '=', $row['academic_period'])->first();


                $date = $row['date_of_birth_yyyy_mm_dd'];

                $identityType = $identityType !== null ? $identityType->id : null;
                $nationalityId = $nationalityId !== null ? $nationalityId->id : null;

                $BirthArea = $BirthArea !== null ? $BirthArea->id : null;


                $identityNUmber = $row['identity_number'];



                //create students data
                \Log::debug('Security_user');

                $studentInfo = Security_user::where('openemis_no', '=', $row['student_id'])->first();
                Security_user::where('openemis_no', $studentInfo['openemis_no'])
                        ->update([
                            'first_name' => $row['full_name'] ? $row['full_name'] : $studentInfo['first_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                            'last_name' => $row['full_name'] ? genNameWithInitials($row['full_name']) : genNameWithInitials($studentInfo['first_name']),
                            'gender_id' => $genderId ? $genderId : $studentInfo['gender_id'],
                            'date_of_birth' => $date ? $date : $studentInfo['date_of_birth'],
                            'address' => $row['address'] ? $row['address'] : $studentInfo['address'],
                            'birthplace_area_id' => $row['birth_registrar_office_as_in_birth_certificate'] ? $BirthArea : $studentInfo['birthplace_area_id'],
                            'nationality_id' => $row['nationality'] ? $nationalityId : $studentInfo['nationality_id'],
                            'identity_type_id' => $row['identity_type'] ? $identityType : $studentInfo['identity_type_id'],
                            'identity_number' => $row['identity_number'] ? $identityNUmber : $studentInfo['identity_number'],
                            'is_student' => 1,
                            'modified' => now(),
                            'modified_user_id' => $this->file['security_user_id']
                            ]);

                $student = Institution_class_student::where('student_id', '=', $studentInfo->id)->first();

                if (!empty($row['identity_number']) && $identityType !== null) {
                    User_identity::create([
                        'identity_type_id' => $identityType,
                        'number' => $identityNUmber,
                        'security_user_id' => $student->student_id,
                        'created_user_id' => $this->file['security_user_id']
                    ]);
                }

                if (!empty($row['special_need'])) {

                    $specialNeed = Special_need_difficulty::where('name', '=', $row['special_need'])->first();
                    $data = [
                        'special_need_date' => now(),
                        'security_user_id' => $student->student_id,
                        'special_need_type_id' => 1,
                        'special_need_difficulty_id' => $specialNeed->id,
                        'created_user_id' => $this->file['security_user_id']
                    ];

                    $check = User_special_need::isDuplicated($data);
                    if ($check) {
                        User_special_need::create($data);
                    }
                }



                if (!empty($row['bmi_height']) && (!empty($row['bmi_weight']))) {

                    // convert Meeter to CM
                    $hight = $row['bmi_height'] / 100;

                    //calculate BMI
                    $bodyMass = ($row['bmi_weight']) / pow($hight, 2);

                    $bmiAcademic = Academic_period::where('name', '=', $row['bmi_academic_period'])->first();
                    $count = User_body_mass::where('academic_period_id' ,'=',$bmiAcademic->id )
                            ->where('security_user_id','=',$student->student_id)->count();

                    \Log::debug('User_body_mass');
                    if(!($count > 0)){
                        User_body_mass::create([
                            'height' => $row['bmi_height'],
                            'weight' => $row['bmi_weight'],
                            'date' => $row['bmi_date_yyyy_mm_dd'],
                            'body_mass_index' => $bodyMass,
                            'academic_period_id' => $bmiAcademic->id,
                            'security_user_id' => $student->student_id,
                            'created_user_id' => $this->file['security_user_id']
                        ]);
                    }
                }

                if (!empty($row['fathers_full_name']) && ($row['fathers_date_of_birth_yyyy_mm_dd'] !== null)) {
                    $AddressArea = Area_administrative::where('name', 'like', '%' . $row['fathers_address_area'] . '%')->first();
                    $nationalityId = Nationality::where('name', 'like', '%' . $row['fathers_nationality'] . '%')->first();
                    $identityType = Identity_type::where('national_code', 'like', '%' . $row['fathers_identity_type'] . '%')->first();
                    $openemisFather = $this->getUniqueOpenemisId();;

                    $identityType = ($identityType !== null) ? $identityType->id : null;
                    $nationalityId = $nationalityId !== null ? $nationalityId->id : null;

                    $father = null;
                    if (!empty($row['fathers_identity_number'])) {
                        $father = Security_user::where('identity_type_id', '=', $nationalityId)
                                        ->where('identity_number', '=', $row['fathers_identity_number'])->first();
                    }


                    if ($father === null) {

                        $father = Security_user::create([
                                    'username' => $openemisFather,
                                    'openemis_no' => $openemisFather,
                                    'first_name' => $row['fathers_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                                    'last_name' => genNameWithInitials($row['fathers_full_name']),
                                    'gender_id' => 1,
                                    'date_of_birth' => $row['fathers_date_of_birth_yyyy_mm_dd'],
                                    'address' => $row['fathers_address'],
                                    'address_area_id' => $AddressArea->id,
                                    'nationality_id' => $nationalityId,
                                    'identity_type_id' => $identityType,
                                    'identity_number' => $row['fathers_identity_number'],
                                    'is_guardian' => 1,
                                    'created_user_id' => $this->file['security_user_id']
                        ]);

                        $father['guardian_relation_id'] = 1;
                        Student_guardian::createStudentGuardian($student, $father, $this->file['security_user_id']);
                    } else {
                        Security_user::where('id', '=', $father->id)
                                ->update(['is_guardian' => 1]);
                        $father['guardian_relation_id'] = 1;
                        Student_guardian::createStudentGuardian($student, $father, $this->file['security_user_id']);
                    }
                }

                if (!empty($row['mothers_full_name']) && ($row['mothers_date_of_birth_yyyy_mm_dd'] !== null)) {
                    $AddressArea = Area_administrative::where('name', 'like', '%' . $row['mothers_address_area'] . '%')->first();
                    $nationalityId = Nationality::where('name', 'like', '%' . $row['mothers_nationality'] . '%')->first();
                    $identityType = Identity_type::where('national_code', 'like', '%' . $row['mothers_identity_type'] . '%')->first();
                    $openemisMother = $this->getUniqueOpenemisId();;

                    $identityType = $identityType !== null ? $identityType->id : null;
                    $nationalityId = $nationalityId !== null ? $nationalityId->id : null;

                    $mother = null;

                    if (!empty($row['mothers_identity_number'])) {
                        $mother = Security_user::where('identity_type_id', '=', $nationalityId)
                                        ->where('identity_number', '=', $row['mothers_identity_number'])->first();
                    }

                    if ($mother === null) {
                        $mother = Security_user::create([
                                    'username' => $openemisMother,
                                    'openemis_no' => $openemisMother,
                                    'first_name' => $row['mothers_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                                    'last_name' => genNameWithInitials($row['mothers_full_name']),
                                    'gender_id' => 2,
                                    'date_of_birth' => $row['mothers_date_of_birth_yyyy_mm_dd'],
                                    'address' => $row['mothers_address'],
                                    'address_area_id' => $AddressArea->id,
                                    'nationality_id' => $nationalityId,
                                    'identity_type_id' => $identityType,
                                    'identity_number' => $row['mothers_identity_number'],
                                    'is_guardian' => 1,
                                    'created_user_id' => $this->file['security_user_id']
                        ]);

                        $mother['guardian_relation_id'] = 2;

                        Student_guardian::createStudentGuardian($student, $mother, $this->file['security_user_id']);
                    } else {
                        Security_user::where('id', '=', $mother->id)
                                ->update(['is_guardian' => 1]);
                        $mother['guardian_relation_id'] = 2;
                        Student_guardian::createStudentGuardian($student, $mother, $this->file['security_user_id']);
                    }
                }


                if (!empty($row['guardians_full_name']) && ($row['guardians_date_of_birth_yyyy_mm_dd'] !== null)) {
                    $genderId = $row['guardians_gender_mf'] == 'M' ? 1 : 2;
                    $AddressArea = Area_administrative::where('name', 'like', '%' . $row['guardians_address_area'] . '%')->first();
                    $nationalityId = Nationality::where('name', 'like', '%' . $row['guardians_nationality'] . '%')->first();
                    $identityType = Identity_type::where('national_code', 'like', '%' . $row['guardians_identity_type'] . '%')->first();
                    $openemisGuardian = $this->getUniqueOpenemisId();;

                    $identityType = $identityType !== null ? $identityType->id : null;
                    $nationalityId = $nationalityId !== null ? $nationalityId->id : null;

                    $guardian = null;

                    if (!empty($row['guardians_identity_number'])) {
                        $guardian = Security_user::where('identity_type_id', '=', $nationalityId)
                                        ->where('identity_number', '=', $row['guardians_identity_number'])->first();
                    }

                    if ($guardian === null) {
                        $guardian = Security_user::create([
                                    'username' => $openemisGuardian,
                                    'openemis_no' => $openemisGuardian,
                                    'first_name' => $row['guardians_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                                    'last_name' => genNameWithInitials($row['guardians_full_name']),
                                    'gender_id' => $genderId,
                                    'date_of_birth' => $row['guardians_date_of_birth_yyyy_mm_dd'],
                                    'address' => $row['guardians_address'],
                                    'address_area_id' => $AddressArea->id,
//                            'birthplace_area_id' => $BirthArea->id,
                                    'nationality_id' => $nationalityId,
                                    'identity_type_id' => $identityType,
                                    'identity_number' => $row['guardians_identity_number'],
                                    'is_guardian' => 1,
                                    'created_user_id' => $this->file['security_user_id']
                        ]);

                        $guardian['guardian_relation_id'] = 3;
                        Student_guardian::createStudentGuardian($student, $guardian, $this->file['security_user_id']);
                    } else {
                        Security_user::where('id', '=', $guardian->id)
                                ->update(['is_guardian' => 1]);
                        $guardian['guardian_relation_id'] = 3;
                        Student_guardian::createStudentGuardian($student, $guardian, $this->file['security_user_id']);
                    }
                }

                $optionalSubjects =  Institution_class_subject::getStudentOptionalSubject($subjects, $student, $row, $institution);

                $allSubjects = array_merge_recursive($optionalSubjects, $mandatorySubject);
                // $stundetSubjects = $this->getStudentSubjects($student);
                // $allSubjects = array_merge_recursive($newSubjects, $stundetSubjects);

                if (!empty($allSubjects)) {
                    $allSubjects = unique_multidim_array($allSubjects, 'institution_subject_id');
                    $this->student = $student;
                    $allSubjects = array_map(array($this,'setStudentSubjects'),$allSubjects);
                    // $allSubjects = array_unique($allSubjects,SORT_REGULAR);
                    $allSubjects = unique_multidim_array($allSubjects, 'education_subject_id');
                    array_walk($allSubjects,array($this,'insertSubject'));
                    // Institution_subject_student::insert((array) $allSubjects);
//                    array_walk($allSubjects, array($this, 'updateSubjectCount'));
                }

                unset($allSubjects);

                $totalStudents = Institution_class_student::getStudentsCount($this->file['institution_class_id']);

                if ($totalStudents['total'] > $institutionClass->no_of_students) {
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'rows', [3 => 'Class student count exceeded! Max number of students is ' . $institutionClass->no_of_students], [null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                    Log::info('email-sent', [$this->file]);
                }


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

    protected function insertSubject($subject){
        if(!Institution_subject_student::isDuplicated($subject))
                Institution_subject_student::updateOrInsert($subject);
    }



    public function rules(): array {

        return [
            '*.student_id' => 'required|exists:security_users,openemis_no|is_student_in_class:'.$this->file['institution_class_id'],
            '*.full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.gender_mf' => 'nullable|in:M,F',
            '*.date_of_birth_yyyy_mm_dd' => 'date|nullable',
            '*.address' => 'nullable',
            '*.birth_registrar_office_as_in_birth_certificate' => 'nullable|exists:area_administratives,name|required_if:identity_type,BC|birth_place',
            '*.birth_divisional_secretariat' => 'nullable|exists:area_administratives,name|required_with:birth_registrar_office_as_in_birth_certificate',
            '*.nationality' => 'nullable',
            '*.identity_type' => 'required_with:identity_number',
            '*.identity_number' => 'user_unique:identity_number',
            '*.academic_period' => 'nullable|exists:academic_periods,name',
            '*.education_grade' => 'nullable|exists:education_grades,code',
            '*.option_*' => 'nullable|exists:education_subjects,name',
            '*.bmi_height' => 'required_with:*.bmi_weight|nullable|numeric|max:200|min:60',
            '*.bmi_weight' => 'required_with:*.bmi_height|nullable|numeric|max:200|min:10',
            '*.bmi_date_yyyy_mm_dd' => 'required_with:*.bmi_height|nullable|date',
            '*.bmi_academic_period' => 'required_with:*.bmi_weight|nullable|exists:academic_periods,name',
            '*.admission_no' => 'nullable|max:12|min:4',
            '*.start_date_yyyy_mm_dd' => 'nullable|date',
            '*.special_need_type' => 'nullable',
            '*.special_need' => 'nullable|exists:special_need_difficulties,name|required_if:special_need_type,Differantly Able',//|exists:special_need_difficulties,name
            '*.fathers_full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.fathers_date_of_birth_yyyy_mm_dd' => 'nullable|required_with:*.fathers_full_name',
            '*.fathers_address' => 'required_with:*.fathers_full_name',
            '*.fathers_address_area' => 'required_with:*.fathers_full_name|nullable|exists:area_administratives,name',
            '*.fathers_nationality' => 'required_with:*.fathers_full_name',
            '*.fathers_identity_type' => 'required_with:*.fathers_identity_number',
            '*.fathers_identity_number' => 'nullable|required_with:*.fathers_identity_type|nic:fathers_identity_number',
            '*.mothers_full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.mothers_date_of_birth_yyyy_mm_dd' => 'nullable|required_with:*.mothers_full_name',
            '*.mothers_address' => 'required_with:*.mothers_full_name',
            '*.mothers_address_area' => 'required_with:*.mothers_full_name|nullable|exists:area_administratives,name',
            '*.mothers_nationality' => "required_with:*.mothers_full_name",
            '*.mothers_identity_type' => "required_with:*.mothers_identity_number",
            '*.mothers_identity_number' => 'nullable|required_with:*.mothers_identity_type|nic:mothers_identity_number',
            '*.guardians_full_name' => 'nullable|regex:/^[\pL\s\-]+$/u',
            '*.guardians_gender_mf' => 'required_with:*.guardians_full_name',
            '*.guardians_date_of_birth_yyyy_mm_dd' => 'nullable|required_with:*.guardians_full_name',
            '*.guardians_address' => 'required_with:*.guardians_full_name',
            '*.guardians_address_area' => 'required_with:*.guardians_full_name|nullable|exists:area_administratives,name',
            '*.guardians_nationality' => 'required_with:*.guardians_full_name',
            '*.guardians_identity_type' => 'required_with:*.guardians_identity_number',
            '*.guardians_identity_number' => 'nullable|required_with:*.guardians_identity_type|nic:guardians_identity_number',
        ];
    }

}
