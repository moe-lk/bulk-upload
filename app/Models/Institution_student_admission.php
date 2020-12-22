<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution_student_admission extends Base_Model
{

    use SoftDeletes;
    
    public const CREATED_AT = 'created';

    public const UPDATED_AT = 'modified';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_student_admission';

    protected $softDelete = true;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['start_date', 'end_date', 'student_id', 'status_id', 'assignee_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'admission_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['modified', 'created', 'modified', 'created', 'start_date', 'end_date', 'modified', 'created'];

    /**
     * Create new Institution student admission from examination data
     *
     * @param [type] $student
     * @param [type] $admissionInfo
     * @return void
     */
    public static function createExaminationData($student, $admissionInfo)
    {
        try {
            $data = [
                'start_date' => $admissionInfo['academic_period']->start_date,
                'start_year' => $admissionInfo['academic_period']->start_year,
                'end_date' => $admissionInfo['academic_period']->end_date,
                'end_year' => $admissionInfo['academic_period']->end_year,
                'student_id' => $student['id'],
                'status_id' => 124,
                'institution_id' => $admissionInfo['instituion']->id,
                'academic_period_id' => $admissionInfo['academic_period']->id,
                'education_grade_id' => $admissionInfo['education_grade']->id,
                'institution_class_id' => (($admissionInfo['instituion_class']  != []) && (count($admissionInfo['instituion_class'])==1)) ? $admissionInfo['instituion_class']['id'] : null,
                'comment' => 'Imported From Examination Data',
                'updated_from' => 'doe',
                'created_user_id' => 1
            ];
            self::create($data);
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }

    public static function createAdmission($studentId,$row,$params,$file){
        self::create([
            'start_date' => $row['start_date_yyyy_mm_dd'],
            'start_year' => $row['start_date_yyyy_mm_dd']->format('Y'),
            'end_date' => $params['academic_period']->end_date,
            'end_year' => $params['academic_period']->end_year,
            'student_id' => $studentId,
            'status_id' => 124,
            'assignee_id' => $params['assignee_id'],
            'institution_id' => $params['institution'],
            'academic_period_id' => $params['academic_period']->id,
            'education_grade_id' => $params['institution_grade']->education_grade_id,
            'institution_class_id' => $params['institution_class']->id,
            'comment' => 'Imported using bulk data upload',
            'admission_id' => $row['admission_no'],
            'created_user_id' => $file['security_user_id']
        ]);
    }
}
