<?php

namespace App\Providers;

namespace App\Providers;

use App\Models\Academic_period;
use App\Models\Area_administrative;
use App\Models\Security_user;
use App\Models\Identity_type;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Validator as IlluminateValidator;
use Illuminate\Support\Facades\Log;
use App\Models\Education_grade;

class ValidatorExtended extends IlluminateValidator {

    private $_custom_messages = array(
        "admission_age" => "The age limit not match with admission age for this class",
        "birth_place" => 'The Birth place combination in not valid, refer the Birth Registrar office only belongs to Divisional Secretariat',
        'user_unique' => 'The Birth place combination in not valid, refer the Birth Registrar office only belongs to Divisional Secretariat',
        "is_bc" => "The Birth Certificate number is not valid",
        "nic" => "NIC number is Not valid"
    );

    public function __construct($translator, $data, $rules, $messages = array(),
            $customAttributes = array()) {
        parent::__construct($translator, $data, $rules, $messages,
                $customAttributes);
        $this->_set_custom_stuff();
    }

    protected function _set_custom_stuff() {
        //setup our custom error messages
        $this->setCustomMessages($this->_custom_messages);
    }

    /**
     * this will validate admission age limit of the student
     *
     * admission age validation
     */
    protected function validateAdmissionAge($attribute, $value, $parameters, $validator) {
        foreach ($validator->getData() as $data) {
            $gradeEntity = Education_grade::where('code', '=', $data['education_grade'])->first();
            $academicPeriod = Academic_period::where('name', '=', $data['academic_period'])->first();
            if (empty($data['date_of_birth_yyyy_mm_dd'])) {
                return false;
            } elseif (($gradeEntity !== null) && ($academicPeriod !== null)) {
                $admissionAge = $gradeEntity->admission_age;
                $studentAge = ($data['date_of_birth_yyyy_mm_dd'])->format('Y');
                $ageOfStudent = ($academicPeriod->start_year) - $studentAge; //$data['academic_period'];
                $enrolmentMinimumAge = $admissionAge - 0;
                $enrolmentMaximumAge = $admissionAge + 10;
                return ($ageOfStudent <= $enrolmentMaximumAge) && ($ageOfStudent >= $enrolmentMinimumAge);
            } else {
                return false;
            }
        }
    }

    protected function validateBirthPlace($attribute, $value, $perameters, $validator) {
        foreach ($validator->getData() as $data) {
            if ($data['identity_type'] == 'BC' && key_exists('birth_divisional_secretariat', $data)) {
               // dd($data['birth_divisional_secretariat']);
                $BirthDivision = Area_administrative::where('name', '=',  '%'.$data['birth_divisional_secretariat'].'%')->where('area_administrative_level_id', '=', 5); //
                if ($BirthDivision->count() > 0 ) {
                    $BirthArea = Area_administrative::where('name', '=', '%'. $value.'%') //$data['birth_registrar_office_as_in_birth_certificate']
                                    ->where('parent_id', '=', $BirthDivision->first()->id)->count();
                    return $BirthArea  > 0;
                } elseif (key_exists('birth_divisional_secretariat', $data) && (!key_exists('birth_registrar_office_as_in_birth_certificate', $data))) {
                    $this->_custom_messages['birth_place'] = 'birth_registrar_office_as_in_birth_certificate required with BC';
                    $this->_set_custom_stuff();
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
    }
    
    protected function validateNic($attribute, $value, $perameters, $validator){  
       $valid =  preg_match('/^([0-9]{9}[VX]|[0-9]{12})$/', $value);
       if(!$valid){
             $this->_custom_messages['nic'] = $attribute. ' is not valid,  Please check the NIC number';
             $this->_set_custom_stuff();
             return false;
       }else{
           return true;
       }
    }

    protected function validateUserUnique($attribute, $value, $perameters, $validator) {
        foreach ($validator->getData() as $data) {
            $identityType = Identity_type::where('national_code', 'like', '%' . $data['identity_type'] . '%')->first();
            if ($identityType !== null && ($value !== null)) {
                if ($identityType->national_code === 'BC' && ($this->IsBc($data, $value))) {
                    return $this->checkUnique($value, $data,$identityType);
                } elseif ($identityType->national_code === 'NIC') {
                    return $this->checkUnique($value, $data,$identityType);
                }
            } elseif (($value == null) || $value == "") {
                return true;
            }
        }
    }

    protected function validateIsBc($attribute, $value, $perameters, $validator) {
        foreach ($validator->getData() as $data) {
            $identityType = Identity_type::where('national_code', 'like', '%' . $data['identity_type'] . '%')->first();
            if ($identityType !== null) {
                if (($identityType->national_code) === 'BC' && strlen((string) $value) < 8) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
    }

    protected function checkUnique($value, $data,$identityType) {
        $isUnique = Security_user::where('identity_number', '=', $value)->where('identity_type_id', '=', $identityType->id);
        if ($this->IsBc($data, $value)) {
            if ($isUnique->count() > 0) {
                $this->_custom_messages['user_unique'] = 'The identity number already in use. User ID is : ' . $isUnique->first()->openemis_no;
                $this->_set_custom_stuff();
                return false;
            } else {
                return true;
            }
        } elseif (!$this->IsBc($data, $value)) {
            return true;
        }
    }

    protected function IsBc($data, $value) {
        $identityType = Identity_type::where('national_code', 'like', '%' . $data['identity_type'] . '%')->first();
        if ($identityType !== null) {
            if (($identityType->national_code) === 'BC' && strlen((string) $value) < 8) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        //
    }

}
