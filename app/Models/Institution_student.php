<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;


class Institution_student extends Base_Model  {


    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_students';


    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['student_status_id', 'student_id', 'education_grade_id', 'academic_period_id', 'start_date', 'start_year', 'end_date', 'end_year', 'institution_id', 'previous_institution_student_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'area_administrative_id','admission_id'];

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institutionStudents(){
        return $this->belongsTo('App\Security_user','student_id');
    }

    /**
     *
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
            $model->created = now();
        });
    }

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * @param $inputs
     * @return bool
     *
     *
     */
    public static function  isDuplicated($inputs){

        $exists = self::where('student_id','=',$inputs['student_id'])
            ->where('institution_id',$inputs['institution_id'])
            ->where('academic_period_id',$inputs['academic_period_id'])
            ->exist();

        return $exists;
    }


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date_of_birth', 'date_of_death', 'last_login', 'modified', 'created', 'start_date', 'end_date', 'modified', 'created'];

    /**
     * get list of students which are going to be promoted
     *
     * @param $institutionGrade
     * @param $academicPeriod
     * @return array
     */
    public function getStudentListToPromote($institutionGrade, $academicPeriod){
        return self::query()
            ->select('institution_students.id','institution_students.student_id','institution_students.student_status_id',
                'institution_students.education_grade_id','institution_students.education_grade_id',
                'institution_students.academic_period_id','institution_students.institution_id',
                'institution_students.created_user_id','institution_students.admission_id')
            ->where('institution_students.institution_id', $institutionGrade['institution_id'])
            ->where('institution_students.education_grade_id', $institutionGrade['education_grade_id'])
            ->where('institution_students.academic_period_id', $academicPeriod->id)->get()->toArray();
    }

  

}
