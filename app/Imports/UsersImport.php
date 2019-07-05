<?php

namespace App\Imports;

use App\Models\Institution_class_student;
use App\Models\Institution_subject;
use App\Models\Institution_subject_student;
use App\Models\Security_user;
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
//use App\Imports\StudentImport;
//use App\Model\Institution_subject_student;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Webpatser\Uuid\Uuid;


class UsersImport implements ToCollection , WithStartRow , WithValidation , WithHeadingRow , WithMultipleSheets , WithEvents
{
    use Importable;

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

    public function startRow(): int
    {
        return 3;
    }


    public function headingRow(): int
    {
        return 2;
    }

    public function sheets(): array
    {
            return [
                // Select by sheet index
//                0 => $this->sheetData,
                1 => $this

            ];
    }

    public function registerEvents(): array
    {
        // TODO: Implement registerEvents() method.
        return [
            BeforeSheet::class => function(BeforeSheet $event){
                $this->sheetNames[] = $event->getSheet()->getTitle();
            }
        ];
    }

    public  function array(array $array){
        $this->sheetData[] = $array;
    }

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
    

    public function collection(Collection $rows)
    {
        
//       $configStudentInfo = Import_mapping::getSheetColumns('Student.Info');
//       $configStudentInstitution = Import_mapping::getSheetColumns('Student.Institution');
//       $configStudentBmi = Import_mapping::getSheetColumns('Student.BMI');

       $subjects =  getMatchingKeys($rows[0]) ;
       $institution = 9909;



       $institutionClass = Institution_class::where('name','like', $this->sheetNames[0])->where('institution_id','=',$institution)->first();
       $institutionGrade = Institution_class_grade::where('institution_class_id','=',$institutionClass->id)->first();


        $this->validateRow($rows);
       foreach ($rows as $row) {

            $genderId = $row['gender_mf'] == 'M' ? 1 : 2;
            // $identityType = $row['identity_type'] == 'BC' ? 1 : 2;

//            $AddressArea = Area_administrative::where('name', 'like', '%'.$row['address_area'].'%')->first();
            $BirthArea = Area_administrative::where('name', 'like', '%'.$row['birth_registrar_office_as_in_birth_certificate'].'%')->first();
            $nationalityId = Nationality::where('name','like','%'.$row['nationality'].'%')->first();
            $identityType = Identity_type::where('national_code','like','%'.$row['identity_type'].'%')->first();
            $academicPeriod = Academic_period::where('name', '=',$row['academic_period'])->first();



            $date = \DateTime::createFromFormat("Y/m/d", $row['date_of_birth_ddmmyyyy']);
            $identityNUmber = $row['identity_number'];
            if($row['identity_type'] == 'BC'){
                $identityNUmber = $BirthArea->id . '' . $row['identity_number'] . '' . substr($date->format("yy"), -2) . '' . $date->format("m");
            }
           
            $openemisStudent = $this::getUniqueOpenemisId();



            \Log::debug('Security_user');
            $student =  Security_user::create([
                'username'=> $openemisStudent,
                'openemis_no'=>$openemisStudent,
                'first_name'=> $row['full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                'last_name' => genNameWithInitials($row['full_name']),
                'gender_id' => $genderId,
                'date_of_birth' => $date ,
                'address'   => $row['address'],
//                'address_area_id'   => $AddressArea->id,
                'birthplace_area_id' => $BirthArea->id,
                'nationality_id' => $nationalityId->id,
                'identity_type_id' => $identityType->id,
                'identity_number' => $identityNUmber ,
                'created_user_id'=> 1,
                'created'=> now(),
                'is_student' => 1
            ]); 

             \Log::debug('Institution_student');
            Institution_student::create([
                'student_status_id' => 1,
                'student_id' => $student->id,
                'education_grade_id' => $institutionGrade->education_grade_id,
                'academic_period_id' => $academicPeriod->id,
                'start_date' => '2019-01-01',
                'start_year' => '2019',
                'end_date' => '2019-12-31',
                'end_year' => '2019',
                'institution_id' => $institution,
                'created_user_id'=> 1,
                'created'=> now(),
                'admission_id' => '4555'
            ]);

            // convert Meeter to CM
            $hight = $row['height']/100;

            //calculate BMI 
            $bodyMass = ($row['weight']) / pow($hight,2);

             \Log::debug('User_body_mass');
            User_body_mass::create([
                'height' => $row['height'],
                'weight' => $row['weight'],
                'date' => $row['date'],
                'body_mass_index' => $bodyMass,
                'academic_period_id' => 1,
                'security_user_id' => $student->id,
                'created_user_id' => 1,
                'created' => now(),
            ]);

            //import father's information
            if(!empty($row['fathers_full_name'])){
                $AddressArea = Area_administrative::where('name', 'like', '%'.$row['fathers_address_area'].'%')->first();
                $nationalityId = Nationality::where('name','like','%'.$row['fathers_nationality'].'%')->first();
                $identityType = Identity_type::where('national_code','like','%'.$row['fathers_identity_type'].'%')->first();
                $date = \DateTime::createFromFormat("Y/m/d", $row['fathers_date_of_birth_ddmmyyyy']);
                $openemisFather = $this::getUniqueOpenemisId();
                $father  =   Security_user::create([
                        'username'=> $openemisFather,
                        'openemis_no'=>$openemisFather,
                        'first_name'=> $row['fathers_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                        'last_name' => genNameWithInitials($row['fathers_full_name']),
                        'gender_id' => 1,
                        'date_of_birth' => $date ,
                        'address'   => $row['fathers_address'],
                        'address_area_id'   => $AddressArea->id,
                        'birthplace_area_id' => $BirthArea->id,
                        'nationality_id' => $nationalityId->id,
                        'identity_type_id' => $identityType->id,
                        'identity_number' => $row['fathers_identity_number'] ,
                        'created_user_id'=> 1,
                        'created'=> now(),
                        'is_guardian' => 1
                ]);

                Student_guardian::create([
                    'student_id' => $student->id,
                    'guardian_id' => $father->id,
                    'guardian_relation_id' => 1,
                    'created_user_id' => 1,
                    'created' => now()
                ]);
            }

            if(!empty($row['mothers_full_name'])){
                $AddressArea = Area_administrative::where('name', 'like', '%'.$row['mothers_address_area'].'%')->first();
                $nationalityId = Nationality::where('name','like','%'.$row['mothers_nationality'].'%')->first();
                $identityType = Identity_type::where('national_code','like','%'.$row['mothers_identity_type'].'%')->first();
                $date = \DateTime::createFromFormat("Y/m/d", $row['mothers_date_of_birth_ddmmyyyy']);
                $openemisMother = $this::getUniqueOpenemisId();
                $mother = Security_user::create([
                        'username'=> $openemisMother,
                        'openemis_no'=>$openemisMother,
                        'first_name'=> $row['mothers_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                        'last_name' => genNameWithInitials($row['mothers_full_name']),
                        'gender_id' => 2,
                        'date_of_birth' => $date ,
                        'address'   => $row['mothers_address'],
                        'address_area_id'   => $AddressArea->id,
                        'birthplace_area_id' => $BirthArea->id,
                        'nationality_id' => $nationalityId->id,
                        'identity_type_id' => $identityType->id,
                        'identity_number' => $row['mothers_identity_number'] ,
                        'created_user_id'=> 1,
                        'created'=> now(),
                        'is_guardian' => 1
                ]);

                Student_guardian::create([
                    'student_id' => $student->id,
                    'guardian_id' => $mother->id,
                    'guardian_relation_id' => 2,
                    'created_user_id' => 1,
                    'created' => now()
                ]);
            }

             if(!empty($row['guardians_full_name'])){
                 $genderId = $row['guardians_gender_mf'] == 'M' ? 1 : 2;
                 $AddressArea = Area_administrative::where('name', 'like', '%'.$row['guardians_address_area'].'%')->first();
                 $nationalityId = Nationality::where('name','like','%'.$row['guardians_nationality'].'%')->first();
                 $identityType = Identity_type::where('national_code','like','%'.$row['guardians_identity_type'].'%')->first();
                 $date = \DateTime::createFromFormat("Y/m/d", $row['guardians_date_of_birth_ddmmyyyy']);
                 $openemisGuardian = $this::getUniqueOpenemisId();
                 $guardian =  Security_user::create([
                        'username'=> $openemisGuardian,
                        'openemis_no'=>$openemisGuardian,
                        'first_name'=> $row['guardians_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                        'last_name' => genNameWithInitials($row['guardians_full_name']),
                        'gender_id' => $genderId,
                        'date_of_birth' => $date ,
                        'address'   => $row['guardians_address'],
                        'address_area_id'   => $AddressArea->id,
                        'birthplace_area_id' => $BirthArea->id,
                        'nationality_id' => $nationalityId->id,
                        'identity_type_id' => $identityType->id,
                        'identity_number' => $row['guardians_identity_number'] ,
                        'created_user_id'=> 1,
                        'created'=> now(),
                        'is_guardian' => 1
                ]);

                 Student_guardian::create([
                     'student_id' => $student->id,
                     'guardian_id' => $guardian->id,
                     'guardian_relation_id' => 3,
                     'created_user_id' => 1,
                     'created' => now()
                 ]);
            }



            $student = Institution_class_student::create([
                'student_id'  => $student->id,
                'institution_class_id' => $institutionClass->id,
                'education_grade_id' => $institutionGrade->education_grade_id,
                'academic_period_id'=>$academicPeriod->id,
                'institution_id' => $institution,
                'student_status_id' => 1
            ]);


           //Option subject feed
           $subjects = $this->setStudentOptionalSubject($subjects,$student,$row);

           $mandatorySubject = Institution_subject::with(['institutionGradeSubject' => function($query) use ($student){
               $query->where('auto_allocation','=',1)
                   ->where('education_grade_id','=',$student->education_grade_id)
                   ->where('education_subject_id','=',$student->education_subject_id);
           }])
               ->where('institution_id','=',$institution)
               ->where('education_grade_id','=',$student->education_grade_id)->get()->toArray();


           $subjects = array_merge_recursive($subjects,$mandatorySubject);
           $subjects = array_unique($subjects,SORT_REGULAR);

           $subjects = $this->setStudentSubjects($subjects,$student);
           Institution_subject_student::insert((array) $subjects);


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
                $data[]  = [
                    'id' => (string) Uuid::generate(4),
                    'student_id' => $student->student_id,
                    'institution_class_id' => $student->institution_class_id,
                    'institution_subject_id' => $subject['id'],
                    'institution_id' => $student->institution_id,
                    'academic_period_id' => $student->academic_period_id,
                    'education_subject_id' => $subject['education_subject_id'],
                    'education_grade_id' => $student->education_grade_id,
                    'student_status_id' => 1,
                    'created_user_id' => 1,
                    'created' => now()
                ];

        }
        return $data;
    }

    public function setStudentOptionalSubject($subjects,$student,$row){
        $data = [];
        foreach ($subjects as $subject){
            $subject = Institution_subject::with(['institutionGradeSubject' => function($query) use ($student){
                $query->where('auto_allocation','=',0)
                    ->where('education_grade_id','=',$student->education_grade_id)
                    ->where('education_subject_id','=',$student->education_subject_id);
                }])
                ->where('name','=',$row[$subject])
                ->where('institution_id','=',$student->institution_id)
                ->where('education_grade_id','=',$student->education_grade_id)
                ->where('academic_period_id','=',$student->academic_period_id)->get()->toArray();

            if(!empty($subject))
                $data[] = $subject[0];
        }

        return $data;
    }


    public function validateRow($rows){
                Validator::make($rows->toArray(), [
                '*.full_name' => 'required|regex:/^[\pL\s\-]+$/u',
                '*.gender_mf' => 'required',
                '*.date_of_birth_ddmmyyyy' => 'required|date',
                '*.address' => 'required',
//                '*.address_area' => 'required',
                '*.birth_registrar_office_as_in_birth_certificate' => 'required',
                '*.nationality' => 'required',
                '*.identity_type' => 'required',
                '*.identity_number' =>  'required|unique:security_users,identity_number', //'required|unique:security_users,identity_type_id',
                '*.academic_period' => 'required',
                '*.education_grade' => 'required',
                '*.height' => 'required',
                '*.weight' => 'required',
                '*.admission_no' => 'required',
                '*.start_date_ddmmyyyy' => 'required|date',
                '*.option_*' => 'required',
                '*.need_type' => 'required',
                '*.guardians_*' => 'required_without_all:*.fathers_*,*.mothers_*',
                '*.fathers_identity_number' => 'unique:security_users,identity_number',
                '*.mothers_identity_number' => 'unique:security_users,identity_number',
                '*.guardians_identity_number' => 'unique:security_users,identity_number'

        ])->validate();

    }

    public function rules(): array
    {
        return [
            // '*.0' => 'required',
            // '*.student_idleave_as_blank_for_new_entries' => Rule::in(['required']) ,
            // '*.full_name' => 'required'
            // '*.2' => 'required',
            // '*.3' => 'required',
            // '*.4' => 'required',
             // Above is alias for as it always validates in batches
            //  '*.1' => Rule::in(['patrick@maatwebsite.nl']),
             
            //  // Can also use callback validation rules
            //  '0' => function($attribute, $value, $onFailure) {
            //       if ($value !== 'Patrick Brouwers') {
            //            $onFailure('Name is not Patrick Brouwers');
            //       }
            //   }
        ];
    }
    
}
