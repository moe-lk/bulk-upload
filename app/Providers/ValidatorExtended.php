<?php

namespace App\Providers;

namespace App\Providers;
use App\Models\Academic_period;
use Illuminate\Validation\Validator as IlluminateValidator;
use Illuminate\Support\Facades\Log;
use App\Models\Education_grade;

class ValidatorExtended extends IlluminateValidator
{

    private $_custom_messages = array(
        "admission_age" => "The age limit not match with admission age for this class",
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
            if($gradeEntity !== null){
                //            dd($gradeEntity);
                $admissionAge = $gradeEntity->admission_age;
                $ageOfStudent =    ($academicPeriod->start_year) - $value->format('Y'); //$data['academic_period'];
                $enrolmentMinimumAge = $admissionAge - 0;
                $enrolmentMaximumAge = $admissionAge + 10;
                return ($ageOfStudent<=$enrolmentMaximumAge) && ($ageOfStudent>=$enrolmentMinimumAge);
            }else{
                return false;
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
