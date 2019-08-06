<?php

namespace App\Providers;

namespace App\Providers;
use App\Models\Academic_period;
use App\Models\Area_administrative;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Validator as IlluminateValidator;
use Illuminate\Support\Facades\Log;
use App\Models\Education_grade;

class ValidatorExtended extends IlluminateValidator
{

    private $_custom_messages = array(
        "admission_age" => "The age limit not match with admission age for this class",
        "birth_place" => 'The Birth place combination in not valid, refer the Birth Registrar office only belongs to Divisional Secretariat'
    );

    public function __construct( $translator, $data, $rules, $messages = array(),
                                 $customAttributes = array() ) {
        parent::__construct( $translator, $data, $rules, $messages,
            $customAttributes );
        $this->_set_custom_stuff();
    }


    protected function _set_custom_stuff() {
        //setup our custom error messages
        $this->setCustomMessages( $this->_custom_messages );
    }

    /**
     * this will validate admission age limit of the student
     *
     * admission age validation
     */
    protected function validateAdmissionAge( $attribute, $value, $parameters, $validator) {

        foreach ($validator->getData() as $data){
            $gradeEntity = Education_grade::where('code','=',$data['education_grade'])->first();
            $academicPeriod = Academic_period::where('name', '=',$data['academic_period'])->first();
            if(empty($data['date_of_birth_yyyy_mm_dd'])){
                return false;
            }elseif($gradeEntity !== null){
                $admissionAge = $gradeEntity->admission_age;
                $studentAge = gettype($value) == 'double' ?  \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['date_of_birth_yyyy_mm_dd'])->format('Y') : ( ($value)->format('Y'));
                $ageOfStudent =    ($academicPeriod->start_year)  - $studentAge  ; //$data['academic_period'];
                $enrolmentMinimumAge = $admissionAge - 0;
                $enrolmentMaximumAge = $admissionAge + 10;
                return ($ageOfStudent<=$enrolmentMaximumAge) && ($ageOfStudent>=$enrolmentMinimumAge);
            }else{
                return false;
            }
        }

    }

    protected function validateBirthPlace($attribute,$value,$perameters,$validator){
        foreach ($validator->getData() as $data){
            if($data['identity_type'] == 'BC' && key_exists('birth_divisional_secretariat',$data)){
                $BirthDivision = Area_administrative::where('name','like','%'.$data['birth_divisional_secretariat'].'%')->where('area_administrative_level_id','=',5)->first();
                if(empty($BirthDivision)) return false;
                $BirthArea = Area_administrative::where('name', 'like', '%'.$data['birth_registrar_office_as_in_birth_certificate'].'%')
                    ->where('parent_id','=',$BirthDivision->id)->count();
                return $BirthArea > 0;
            }elseif($data['identity_type'] == 'BC' && key_exists('birth_divisional_secretariat',$data)){
                return false;
            }
            else{
                return true;
            }

        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
