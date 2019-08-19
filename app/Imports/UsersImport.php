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
use App\Models\Nationality;
use App\Models\Identity_type;
use App\Models\Student_guardian;
use App\Models\Academic_period;
use App\Models\Institution_class;
use App\Models\Institution_class_grade;
use App\Models\Area_administrative;
use App\Models\Special_need_difficulty;
use App\Models\Workflow_transition;
use App\Rules\admissionAge;
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

class UsersImport implements ToModel , WithStartRow  , WithHeadingRow , WithMultipleSheets , WithEvents , WithMapping , WithLimit , WithBatchInserts , WithValidation
{
    use Importable , RegistersEventListeners;

    public function __construct($file)
    {
        $this->sheetNames = [];
        $this->file = $file;
        $this->sheetData = [];
        $this->worksheet = '';
        $this->failures = [];
        $this->request = new Request;
        $this->maleStudentsCount = 0;
        $this->femaleStudentsCount = 0;
        $this->highestRow = 3;
    }
    

    public function sheets(): array
    {
        return [
            1 => $this

        ];
    }

    public function limit(): int {
        $highestColumn =  $this->worksheet->getHighestDataColumn(53);
        $higestRow = 0;
        for ($row = $this->startRow(); $row <= $this->highestRow; $row++){ 
            $rowData = $this->worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,NULL,TRUE,FALSE);
            if(isEmptyRow(reset($rowData))) { continue; }else{
                $higestRow += 1;
            }
        }
//        dd($higestRow);
        return $higestRow;
    }

        public function batchSize(): int
    {
        $highestColumn =  $this->worksheet->getHighestDataColumn(53);
        $higestRow = 0;
        for ($row = $this->startRow(); $row <= $this->highestRow; $row++){ 
            $rowData = $this->worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,NULL,TRUE,FALSE);
            if(isEmptyRow(reset($rowData))) { continue; }else{
                $higestRow += 1;
            }
        }
//        dd($higestRow);
        return $higestRow;
    }


    public function registerEvents(): array
    {
        // TODO: Implement registerEvents() method.
        return [
            BeforeSheet::class => function(BeforeSheet $event){
                $this->sheetNames[] = $event->getSheet()->getTitle();
                $this->worksheet = $event->getSheet();
               
                $this->validateClass();

                $worksheet = $event->getSheet();
                $this->highestRow = $worksheet->getHighestDataRow(); // e.g. 10
                if ($this->highestRow < 3) {
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'remark', [0 => 'No enough rows!'],[null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                }
            }
        ];
    }


    public function startRow(): int
    {

       return 3;
    }


    public function headingRow(): int
    {
        return 2;
    }

    public function validateColumns($row){
        $columns = [
             "student_id_leave_blank_for_new_student" ,
             "full_name" ,
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

        if($columns == array_keys($row)){

            return true;
        }else{
            $error = \Illuminate\Validation\ValidationException::withMessages([]);
            $failure = new Failure(1, 'remark', [0 => 'Template is not valid for upload, use the template given in the system'],[null]);
            $failures = [0 => $failure];
            throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
            Log::info('error-email-sent',[$this->file]);
            return false;
        }
    }


    /**
     * @param mixed $row
     * @return array
     * @throws \Exception
     */
    public function map($row): array
    {

        try{

            if(gettype($row['date_of_birth_yyyy_mm_dd']) == 'double' || 'string'){
                $row['date_of_birth_yyyy_mm_dd'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_of_birth_yyyy_mm_dd']);
            }

            if(gettype($row['bmi_date_yyyy_mm_dd']) == 'double'){
                $row['bmi_date_yyyy_mm_dd'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['bmi_date_yyyy_mm_dd']);
            }

            if(gettype($row['start_date_yyyy_mm_dd']) == 'double'){
                $row['start_date_yyyy_mm_dd'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['start_date_yyyy_mm_dd']);
            }

            if(gettype($row['fathers_date_of_birth_yyyy_mm_dd']) == 'double'){
                $row['fathers_date_of_birth_yyyy_mm_dd'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fathers_date_of_birth_yyyy_mm_dd']);
            }

            if(gettype($row['mothers_date_of_birth_yyyy_mm_dd']) == 'double'){
                $row['mothers_date_of_birth_yyyy_mm_dd'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['mothers_date_of_birth_yyyy_mm_dd']);
            }

            if(gettype($row['guardians_date_of_birth_yyyy_mm_dd']) == 'double'){
                $row['guardians_date_of_birth_yyyy_mm_dd'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['guardians_date_of_birth_yyyy_mm_dd']);
            }
            
        
            if($row['identity_type'] == 'BC' && (!empty($row['birth_divisional_secretariat']))){
                $BirthDivision = Area_administrative::where('name','like','%'.$row['birth_divisional_secretariat'].'%')->where('area_administrative_level_id','=',5)->first();
                if($BirthDivision !== null){
                    $BirthArea = Area_administrative::where('name', 'like', '%'.$row['birth_registrar_office_as_in_birth_certificate'].'%')
                        ->where('parent_id','=',$BirthDivision->id)->first();
                    $row['identity_number'] = $BirthArea->id . '' . $row['identity_number'] . '' . substr($row['date_of_birth_yyyy_mm_dd']->format("yy"), -2) . '' . $row['date_of_birth_yyyy_mm_dd']->format("m");

                }
            }




        }catch (\Exception $e){
            \Log::error('Import Error',[$e]);

        }

        return $row;

    }

    public  function array(array $array){
        $this->sheetData[] = $array;
    }


    /**
     * @param array $options
     * @return string
     */
    public static  function getUniqueOpenemisId($options = [])
    {
        $prefix = '';

        $prefix =  DB::table('config_items')->where('code','=','openemis_id_prefix')->get();
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

        return $prefix . $newStamp;
    }


    public function model(array $row)
    {
        
        

        $institutionClass = Institution_class::find($this->file['institution_class_id']);
        $institution = $institutionClass->institution_id;


        if(!array_filter($row)) { return nulll;}



        Log::info('row data:',[$row]);
        if(!empty($institutionClass)){

            $institutionGrade = Institution_class_grade::where('institution_class_id','=',$institutionClass->id)->first();
            $mandatorySubject = Institution_class_subject::with(['institutionMandatorySubject'])
                ->whereHas('institutionMandatorySubject',function ($query) use ($institutionGrade) {
                    $query->where('education_grade_id','=',$institutionGrade->education_grade_id);
                })
                ->where('institution_class_id','=',$institutionClass->id)
                ->get()->toArray();
            $subjects =  getMatchingKeys($row) ;
                $genderId = $row['gender_mf'] == 'M' ? 1 : 2;
                switch ($row['gender_mf']){
                    case 'M':
                        $this->maleStudentsCount += 1;
                        break;
                    case 'F':
                        $this->femaleStudentsCount += 1;
                        break;
                }

                $BirthArea = Area_administrative::where('name', 'like', '%'.$row['birth_registrar_office_as_in_birth_certificate'].'%')->first();
                $nationalityId = Nationality::where('name','like','%'.$row['nationality'].'%')->first();
                $identityType = Identity_type::where('national_code','like','%'.$row['identity_type'].'%')->first();
                $academicPeriod = Academic_period::where('name', '=',$row['academic_period'])->first();


                $date = $row['date_of_birth_yyyy_mm_dd'];

                $identityType = $identityType !== null ? $identityType->id : null;
                
                $BirthArea = $BirthArea !== null ? $BirthArea->id : null;


                $identityNUmber = $row['identity_number'];

                $openemisStudent = $this::getUniqueOpenemisId();



                //create students data
                \Log::debug('Security_user');


//                $student = Security_user::where('openemis_no','=',$row['student_id_leave_blank_for_new_student'])->get();
//                if(empty($row['student_id_leave_blank_for_new_student'])){
                    $student =  Security_user::create([
                        'username'=> $openemisStudent,
                        'openemis_no'=>$openemisStudent,
                        'first_name'=> $row['full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                        'last_name' => genNameWithInitials($row['full_name']),
                        'gender_id' => $genderId,
                        'date_of_birth' => $date ,
                        'address'   => $row['address'],
//                        'address_area_id'   => $AddressArea->id,
                        'birthplace_area_id' => $BirthArea,
                        'nationality_id' => $nationalityId->id,
                        'identity_type_id' => $identityType,
                        'identity_number' => $identityNUmber ,
                        'is_student' => 1,
                        'created_user_id' => $this->file['security_user_id']
                    ]);

                    Institution_student_admission::create([
                        'start_date' => $row['start_date_yyyy_mm_dd'],
                        'start_year' => $row['start_date_yyyy_mm_dd']->format('Y'),
                        'end_date' => $academicPeriod->end_date,
                        'end_year' =>  $academicPeriod->end_year,
                        'student_id' => $student->id,
                        'status_id' => 124,
                        'assignee_id' => $institutionClass->staff_id,
                        'institution_id' => $institution,
                        'academic_period_id' => $academicPeriod->id,
                        'education_grade_id' => $institutionGrade->education_grade_id,
                        'institution_class_id' => $institutionClass->id,
                        'comment' => 'Imported using bulk data upload',
                        'admission_id' => $row['admission_no'],
                        'created_user_id' => $this->file['security_user_id']
                    ]);


                    \Log::debug('Institution_student');
                    Institution_student::create([
                        'student_status_id' => 1,
                        'student_id' => $student->id,
                        'education_grade_id' => $institutionGrade->education_grade_id,
                        'academic_period_id' => $academicPeriod->id,
                        'start_date' => $row['start_date_yyyy_mm_dd'],
                        'start_year' => $row['start_date_yyyy_mm_dd']->format('Y'),
                        'end_date' => $academicPeriod->end_date,
                        'end_year' =>  $academicPeriod->end_year,
                        'institution_id' => $institution,
                        'admission_id' => $row['admission_no'],
                        'created_user_id' => $this->file['security_user_id']
                    ]);

                    $student =  Institution_class_student::create([
                        'student_id'  => $student->id,
                        'institution_class_id' => $institutionClass->id,
                        'education_grade_id' => $institutionGrade->education_grade_id,
                        'academic_period_id'=>$academicPeriod->id,
                        'institution_id' => $institution,
                        'student_status_id' => 1,
                        'created_user_id' => $this->file['security_user_id']
                    ]);
//                }


                if(!empty($row['special_need'])){
                    $specialNeed = Special_need_difficulty::where('name','=',$row['special_need'])->first();
                    User_special_need::create([
                        'special_need_date' => now(),
                        'security_user_id' => $student->student_id,
                        'special_need_type_id' => 1,
                        'special_need_difficulty_id' => $specialNeed->id,
                        'created_user_id' => $this->file['security_user_id']
                    ]);
                }



                // convert Meeter to CM
                $hight = $row['bmi_height']/100;

                //calculate BMI
                $bodyMass = ($row['bmi_weight']) / pow($hight,2);

                $bmiAcademic = Academic_period::where('name', '=', $row['bmi_academic_period'])->first();

                \Log::debug('User_body_mass');
                User_body_mass::create([
                    'height' => $row['bmi_height'],
                    'weight' => $row['bmi_weight'],
                    'date' => $row['bmi_date_yyyy_mm_dd'],
                    'body_mass_index' => $bodyMass,
                    'academic_period_id' => $bmiAcademic->id,
                    'security_user_id' => $student->student_id,
                    'created_user_id' => $this->file['security_user_id']
                ]);

                if(!empty($row['fathers_full_name'])){

                    $AddressArea = Area_administrative::where('name', 'like', '%'.$row['fathers_address_area'].'%')->first();
                    $nationalityId = Nationality::where('name','like','%'.$row['fathers_nationality'].'%')->first();
                    $identityType = Identity_type::where('national_code','like','%'.$row['fathers_identity_type'].'%')->first();
                    $openemisFather = $this::getUniqueOpenemisId();

                    $identityType = ($identityType !== null) ? $identityType->id : null;

                    $father = Security_user::where('identity_type_id','=', $nationalityId->id)
                        ->where('identity_number' , '=', $row['fathers_identity_number'])->first();

                    if(empty($father)){
                        $father  =   Security_user::create([
                            'username'=> $openemisFather,
                            'openemis_no'=>$openemisFather,
                            'first_name'=> $row['fathers_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                            'last_name' => genNameWithInitials($row['fathers_full_name']),
                            'gender_id' => 1,
                            'date_of_birth' => $row['fathers_date_of_birth_yyyy_mm_dd'] ,
                            'address'   => $row['fathers_address'],
                            'address_area_id'   => $AddressArea->id,
                            'nationality_id' => $nationalityId->id,
                            'identity_type_id' => $identityType,
                            'identity_number' => $row['fathers_identity_number'] ,
                            'is_guardian' => 1,
                            'created_user_id' => $this->file['security_user_id']
                        ]);
                        $father['guardian_relation_id'] = 1;
                        Student_guardian::createStudentGuardian($student,$father,$this->file['security_user_id']);
                    }else{
                        Security_user::where('id' , '=', $father->id)
                            ->update(['is_guardian' => 1]);
                        $father['guardian_relation_id'] = 1;
                        Student_guardian::createStudentGuardian($student,$father,$this->file['security_user_id']);
                    }
                }

                if(!empty($row['mothers_full_name'])){
                    $AddressArea = Area_administrative::where('name', 'like', '%'.$row['mothers_address_area'].'%')->first();
                    $nationalityId = Nationality::where('name','like','%'.$row['mothers_nationality'].'%')->first();
                    $identityType = Identity_type::where('national_code','like','%'.$row['mothers_identity_type'].'%')->first();
                    $openemisMother = $this::getUniqueOpenemisId();

                    $identityType = $identityType !== null ? $identityType->id : null;

                    $mother = Security_user::where('identity_type_id','=', $nationalityId->id)
                        ->where('identity_number' , '=', $row['mothers_identity_number'])->first();


                    if(empty($mother)){
                        $mother = Security_user::create([
                            'username'=> $openemisMother,
                            'openemis_no'=>$openemisMother,
                            'first_name'=> $row['mothers_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                            'last_name' => genNameWithInitials($row['mothers_full_name']),
                            'gender_id' => 2,
                            'date_of_birth' => $row['mothers_date_of_birth_yyyy_mm_dd'] ,
                            'address'   => $row['mothers_address'],
                            'address_area_id'   => $AddressArea->id,
//                            'birthplace_area_id' => $BirthArea->id,
                            'nationality_id' => $nationalityId->id,
                            'identity_type_id' => $identityType,
                            'identity_number' => $row['mothers_identity_number'] ,
                            'is_guardian' => 1,
                            'created_user_id' => $this->file['security_user_id']
                        ]);
                        $mother['guardian_relation_id'] = 2;
                        Student_guardian::createStudentGuardian($student,$mother,$this->file['security_user_id']);
                    }else{
                        Security_user::where('id' , '=', $mother->id)
                            ->update(['is_guardian' => 1]);
                        $mother['guardian_relation_id'] = 2;
                        Student_guardian::createStudentGuardian($student,$mother,$this->file['security_user_id']);
                    }
                }

            
                if(!empty($row['guardians_full_name'])){
                    $genderId = $row['guardians_gender_mf'] == 'M' ? 1 : 2;
                    $AddressArea = Area_administrative::where('name', 'like', '%'.$row['guardians_address_area'].'%')->first();
                    $nationalityId = Nationality::where('name','like','%'.$row['guardians_nationality'].'%')->first();
                    $identityType = Identity_type::where('national_code','like','%'.$row['guardians_identity_type'].'%')->first();
                    $openemisGuardian = $this::getUniqueOpenemisId();

                    $identityType = $identityType !== null ? $identityType->id : null;

                    $guardian = Security_user::where('identity_type_id','=', $nationalityId->id)
                        ->where('identity_number' , '=', $row['guardians_identity_number'])->first();
                    if(empty($guardian)){
                        $guardian =  Security_user::create([
                            'username'=> $openemisGuardian,
                            'openemis_no'=>$openemisGuardian,
                            'first_name'=> $row['guardians_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                            'last_name' => genNameWithInitials($row['guardians_full_name']),
                            'gender_id' => $genderId,
                            'date_of_birth' => $row['guardians_date_of_birth_yyyy_mm_dd'] ,
                            'address'   => $row['guardians_address'],
                            'address_area_id'   => $AddressArea->id,
//                            'birthplace_area_id' => $BirthArea->id,
                            'nationality_id' => $nationalityId->id,
                            'identity_type_id' => $identityType,
                            'identity_number' => $row['guardians_identity_number'] ,
                            'is_guardian' => 1,
                            'created_user_id' => $this->file['security_user_id']
                        ]);
                        $guardian['guardian_relation_id'] = 3;
                        Student_guardian::createStudentGuardian($student,$guardian,$this->file['security_user_id']);
                    }else{
                        Security_user::where('id' , '=',  $guardian->id)
                            ->update(['is_guardian' => 1]);
                        $guardian['guardian_relation_id'] = 3;
                        Student_guardian::createStudentGuardian($student,$guardian,$this->file['security_user_id']);
                    }
                }






                //Option subject feed
                $optionalSubjects = $this->getStudentOptionalSubject($subjects,$student,$row,$institution);

                $allSubjects = array_merge_recursive($optionalSubjects,$mandatorySubject);
                if(!empty($allSubjects)){


                    $allSubjects = unique_multidim_array($allSubjects,'institution_subject_id');
                    $allSubjects = $this->setStudentSubjects($allSubjects,$student);
//                   $allSubjects = array_unique($allSubjects,SORT_REGULAR);
                    $allSubjects = unique_multidim_array($allSubjects,'education_subject_id');

                    Institution_subject_student::insert((array) $allSubjects);
                }

                unset($allSubjects);
               $total_male_students =   Institution_class_student::with(['student' => function($query) {
                       $query->where('student.gender_id','=',1);
                   }])->whereHas('student',function ($query) {
                       $query->where('gender_id','=',1);
                   })->where('institution_class_id','=' , $this->file['institution_class_id'])->count();

                $total_female_students =  Institution_class_student::with(['student' => function($query){
                        $query->where('student.gender_id','=',2);
                    }])->whereHas('student',function ($query) {
                        $query->where('gender_id', '=', 2);
                    })->where('institution_class_id','=' , $this->file['institution_class_id'])->count();

                $totalStudents = $total_female_students + $total_male_students;

                if($totalStudents > $institutionClass->no_of_students ){
                    $error = \Illuminate\Validation\ValidationException::withMessages([]);
                    $failure = new Failure(3, 'rows', [3 => 'Class student count exceeded! Max number of students is ' .$institutionClass->no_of_students ],[null]);
                    $failures = [0 => $failure];
                    throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                    Log::info('email-sent',[$this->file]);
                }

                Institution_class::where('id','=',$institutionClass->id)
                    ->update([
                        'total_male_students' => $total_male_students ,
                        'total_female_students' => $total_female_students ]);

            }


        }





    /**
     * @param $subjects
     * @param $student
     * @return array
     * @throws \Exception
     */
    public  function  setStudentSubjects($subjects,$student){
        $data = [];

        foreach ($subjects as $subject){
            $educationSubjectId =  key_exists('institution_optional_subject',$subject) ? $subject['institution_optional_subject']['education_subject_id'] : $subject['institution_mandatory_subject']['education_subject_id'];


                $data[]  = [
                    'id' => (string) Uuid::generate(4),
                    'student_id' => $student->student_id,
                    'institution_class_id' => $student->institution_class_id,
                    'institution_subject_id' => $subject['institution_subject_id'],
                    'institution_id' => $student->institution_id,
                    'academic_period_id' => $student->academic_period_id,
                    'education_subject_id' => $educationSubjectId,
                    'education_grade_id' => $student->education_grade_id,
                    'student_status_id' => 1,
                    'created_user_id' => $this->file['security_user_id'],
                    'created' => now()
                ];

        }
        return $data;
    }

    public function getStudentOptionalSubject($subjects,$student,$row,$institution){
        $data = [];


        foreach ($subjects as $subject){

            $subjectId = Institution_class_subject::with(['institutionOptionalSubject'])
                ->whereHas('institutionOptionalSubject',function ($query) use ($row,$subject,$student){
                $query->where('name','=',$row[$subject])
                    ->where('education_grade_id','=',$student->education_grade_id);
                })
                ->where('institution_class_id','=',$student->institution_class_id)
                ->get()->toArray();
                if(!empty($subjectId))
                    $data[] = $subjectId[0];

        }

        return $data;
    }



    public function validateClass(){

        $institutionClass = Institution_class::find($this->file['institution_class_id']);

        $totalMaleStudents = $institutionClass->total_male_students;
        $totalFemaleStudents = $institutionClass->total_female_students;
        $totalStudents = $totalMaleStudents + $totalFemaleStudents;

        $exceededStudents = ($totalStudents + ($this->highestRow - 2)) > $institutionClass->no_of_students ? true : false;

        if($exceededStudents == true){
            try{
                $error = \Illuminate\Validation\ValidationException::withMessages([]);
                $failure = new Failure(3, 'remark', [3 => 'Class student count exceeded! Max number of students is' .$institutionClass->no_of_students ],[null]);
                $failures = [0 => $failure];
                throw new \Maatwebsite\Excel\Validators\ValidationException($error, $failures);
                Log::info('email-sent',[$this->file]);
            }catch (Exception $e){
                Logg::info('email-sending-failed',[$e]);
            }
        }else{
            return true;
        }

    }

    public function rules(): array
    {

        return [
            '*.full_name' => 'required|regex:/^[\pL\s\-]+$/u',
            '*.gender_mf' => 'required',
            '*.date_of_birth_yyyy_mm_dd' => 'required|admission_age:education_grade',
            '*.address' => 'nullable',
            '*.birth_registrar_office_as_in_birth_certificate' => 'required_if:identity_type,BC|birth_place',
            '*.birth_divisional_secretariat' => 'required_with:birth_registrar_office_as_in_birth_certificate',
            '*.nationality' => 'required',
            '*.identity_type' => 'required_with:identity_number',
            '*.identity_number' =>  'nullable|user_unique:identity_number',
            '*.academic_period' => 'required|exists:academic_periods,name',
            '*.education_grade' => 'required',
            '*.bmi_height' => 'required|numeric',
            '*.bmi_weight' => 'required|numeric',
            '*.bmi_date_yyyy_mm_dd' => 'required',
            '*.bmi_academic_period' => 'required|exists:academic_periods,name',
            '*.admission_no' => 'required',
            '*.start_date_yyyy_mm_dd' => 'required',
            '*.special_need_type' => 'nullable',
            '*.special_need' => 'required_if:special_need_type,Differantly Able',
            '*.fathers_full_name' => 'sometimes|required_with:fathers_identity_number',
            '*.fathers_date_of_birth_yyyy_mm_dd' => 'required_with:fathers_full_name',
            '*.fathers_address' =>  'required_with:fathers_full_name',
            '*.fathers_address_area' => 'required_with:fathers_full_name|nullable|exists:area_administratives,name',
            '*.fathers_nationality' => 'required_with:fathers_full_name',
            '*.fathers_identity_type' => 'required_with:fathers_identity_number',
            '*.fathers_identity_number' => 'nullable',
            '*.mothers_full_name' => 'sometimes|required_with:.mothers_identity_number',
            '*.mothers_date_of_birth_yyyy_mm_dd' =>  'required_with:mothers_full_name',
            '*.mothers_address' =>  'required_with:mothers_full_name',
            '*.mothers_address_area' => 'required_with:mothers_full_name|nullable|exists:area_administratives,name',
            '*.mothers_nationality' => "required_with:mothers_full_name",
            '*.mothers_identity_type' => "required_with:mothers_identity_number",
            '*.mothers_identity_number' => 'nullable',
            '*.guardians_full_name' => 'required_without_all:*.fathers_full_name,*.mothers_full_name',
            '*.guardians_gender_mf' =>  'required_with:guardians_full_name',
            '*.guardians_date_of_birth_yyyy_mm_dd' =>  'required_with:guardians_full_name',
            '*.guardians_address' => 'required_with:guardians_full_name',
            '*.guardians_address_area' => 'required_with:guardians_full_name|nullable|exists:area_administratives,name',
            '*.guardians_nationality' => 'required_with:guardians_full_name',
            '*.guardians_identity_type' => 'required_with:guardians_identity_number',
            '*.guardians_identity_number' => 'nullable',
        ];
    }

}
