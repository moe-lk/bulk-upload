<?php

namespace App\Providers;

namespace App\Providers;

use App\Models\Academic_period;
use App\Models\Area_administrative;
use App\Models\Security_user;
use App\Models\Identity_type;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as IlluminateValidator;
use Illuminate\Support\Facades\Log;
use App\Models\Education_grade;
use App\Models\Institution_class;
use App\Models\Institution_class_grade;
use App\Models\Institution_class_student;

class ValidatorExtended extends IlluminateValidator
{

    private $_custom_messages = array(
        "admission_age" => "The age limit not match with admission age for this class",
        "birth_place" => 'The Birth place combination in not valid, refer the Birth Registrar office only belongs to Divisional Secretariat',
        'user_unique' => 'The Birth place combination in not valid, refer the Birth Registrar office only belongs to Divisional Secretariat',
        "is_bc" => "The Birth Certificate number is not valid",
        "is_student_in_class" => "The Student ID is not belong to this class",
        "bmi" => "The record must have BMI information",
        "identity" => "Identity number format should match with Identity type",
    );

    public function __construct(
        $translator,
        $data,
        $rules,
        $messages = array(),
        $customAttributes = array()
    ) {
        parent::__construct(
            $translator,
            $data,
            $rules,
            $messages,
            $customAttributes
        );
        $this->_set_custom_stuff();
    }

    protected function _set_custom_stuff()
    {
        //setup our custom error messages
        $this->setCustomMessages($this->_custom_messages);
    }

    /**
     * this will validate admission age limit of the student
     *
     * admission age validation
     */

    protected function validateAdmissionAge($attribute, $value, $parameters, $validator)
    {
        $institutionClass = Institution_class::find($parameters[0]);
        $institutionGrade = Institution_class_grade::where('institution_class_id', '=', $institutionClass->id)->first();
        if (!empty($institutionClass)) {
            $gradeEntity = Education_grade::where('id', '=', $institutionGrade->education_grade_id)->first();
            $academicPeriod = Academic_period::find($institutionClass->academic_period_id);
            if (empty($value)) {
                return false;
            } elseif ($gradeEntity !== null) {
                $admissionAge = (($gradeEntity->admission_age) * 12) - 1;
                $to = $academicPeriod->start_date;
                $diff_in_months = $to->diffInMonths($value);
                $ageOfStudent = $diff_in_months;
                $enrolmentMaximumAge = $admissionAge + 120;
                return ($ageOfStudent <= $enrolmentMaximumAge) && ($ageOfStudent >= $admissionAge);
            } else {
                return false;
            }
        } else {
            $this->_custom_messages['admission_age'] = 'given' . $attribute . 'Not found';
            $this->_set_custom_stuff();
            return false;
        }
    }

    protected function validateHW($attribute, $value)
    {

        if (is_numeric($value)) {
            if ($value < 10) {
                $this->_custom_messages['bmi'] =  $attribute . ' is must greater than 10';
                $this->_set_custom_stuff();
                return false;
            } elseif ($value > 250) {
                $this->_custom_messages['bmi'] =  $attribute . ' is must smaller than 250';
                $this->_set_custom_stuff();
                return false;
            }
        } else {
            $this->_custom_messages['bmi'] =  $attribute . ' is must a valid numeric';
            $this->_set_custom_stuff();
            return false;
        }
        return true;
    }

    protected function validateBmi($attribute, $value, $parameters)
    {
        $bmiGrades =  ['G1', 'G4', 'G7', 'G10'];
        $institutionGrade = Institution_class_grade::where('institution_class_id', '=', $parameters[0])
            ->join('education_grades', 'institution_class_grades.education_grade_id', 'education_grades.id')
            ->first();
        $educationGrade =  Education_grade::where('id', '=', $institutionGrade->education_grade_id)->first();
        if (in_array($institutionGrade->code, $bmiGrades)) {
            if (!empty($value)) {
                if (($attribute == 'bmi_height') || ('bmi_weight')) {
                    return $this->validateHW($attribute, $value);
                }
            } else {
                $this->_custom_messages['bmi'] =  $attribute . ' is required for ' . $educationGrade->name;
                $this->_set_custom_stuff();
                return false;
            }
        } elseif (!empty($value)) {
            if (($attribute == 'bmi_height') || ('bmi_weight')) {
                return $this->validateHW($attribute, $value);
            }
        } else {
            return true;
        }
    }

    protected function validateBirthPlace($attribute, $value, $perameters, $validator)
    {
        foreach ($validator->getData() as $data) {
            if ($data['identity_type'] == 'BC' && key_exists('birth_divisional_secretariat', $data)) {
                $BirthDivision = Area_administrative::where('name', '=',  '%' . $data['birth_divisional_secretariat'] . '%')->where('area_administrative_level_id', '=', 5); //
                if ($BirthDivision->count() > 0) {
                    $BirthArea = Area_administrative::where('name', '=', '%' . $value . '%') //$data['birth_registrar_office_as_in_birth_certificate']
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

    protected function validateIsStudentInClass($attribute, $value, $perameters, $validator)
    {
        $student =  Security_user::where('openemis_no', '=', $value);
        if ($student->count() > 0) {
            $student = $student->first()->toArray();
            $check =  Institution_class_student::where('student_id', '=', $student['id'])->where('institution_class_id', '=', $perameters[0])->count();
            if ($check == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

   

    protected function validateIdentity($attribute, $value, $perameters, $validator)
    {
        $valid = true;
        foreach ($validator->getData() as $data) {
            switch($data[$perameters[0]]){
                case 'BC':
                    $valid = preg_match('/^([0-9]{3,5})/i', $value);
                    break;
                case 'NIC':
                    $valid = preg_match('/^([0-9]{9}[VX]|([0-9]{12}))/i', $value);
                    break;
                default:
                    $valid = true;    
            }
        }
        
        if ($valid == 0) {
            $this->_custom_messages['identity'] = $attribute . ' is not valid  Please check the NIC number';
            $this->_set_custom_stuff();
            return false;
        } else {
            return true;
        }
    }

    protected function validateUserUnique($attribute, $value, $perameters, $validator)
    {
        foreach ($validator->getData() as $data) {
            $identityType = Identity_type::where('national_code', 'like', '%' . $data['identity_type'] . '%')->first();
            if ($identityType !== null && ($value !== null)) {
                if ($identityType->national_code === 'BC') {
                    return $this->checkUnique($value, $data, $identityType);
                } elseif ($identityType->national_code === 'NIC') {
                    return $this->checkUnique($value, $data, $identityType);
                }
            } elseif (($value == null) || $value == "") {
                return true;
            }
        }
    }

    protected function validateIsBc($attribute, $value, $perameters, $validator)
    {
        foreach ($validator->getData() as $data) {
            $identityType = Identity_type::where('national_code', 'like', '%' . $data['identity_type'] . '%')->first();
            if (($identityType !== null) && ($identityType !== "")) {
                if (($identityType->national_code) === 'BC') {
                    return (strlen((string) $data['identity_number']) < 7);
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
    }

    protected function checkUnique($value, $data, $identityType)
    {
        $isUnique = Security_user::where('identity_number', '=', $value)->where('identity_type_id', '=', $identityType->id);
        if ($isUnique->count() > 0) {
            $this->_custom_messages['user_unique'] = 'The identity number already in use. User ID is : ' . $isUnique->first()->openemis_no;
            $this->_set_custom_stuff();
            return false;
        } else {
            return true;
        }
    }

    protected function IsBc($data, $value)
    {
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
