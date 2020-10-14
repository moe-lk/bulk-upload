<?php

namespace App\Models;

use function foo\func;
use Webpatser\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution_subject_student extends Model  {


    use SoftDeletes;
    
    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_subject_students';

    protected $softDelete = true;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['student_id', 'institution_class_id', 'institution_id', 'academic_period_id', 'education_subject_id', 'education_grade_id', 'total_mark', 'institution_subject_id', 'student_status_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
    protected $dates = ['modified', 'created'];


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
        });

    }

    /**
     * @param $inputs
     * @return bool
     *
     *
     */
    public static function  isDuplicated($inputs){

       $exists = self::where('student_id','=',$inputs['student_id'])
           ->where('institution_subject_id','=',$inputs['institution_subject_id'])
           ->where('education_subject_id','=',$inputs['education_subject_id'])->count();


        return $exists ? true :false;
    }

    
     public function student(){
        return $this->belongsTo('App\Models\Security_user','student_id');
    }
    
    public static function getStudentsCount($institution_subject_id){
         $total_male_students = self::with(['student' => function($query) {
                        $query->where('student.gender_id', '=', 1);
                    }])->whereHas('student', function ($query) {
                    $query->where('gender_id', '=', 1);
                })->where('institution_subject_id', '=', $institution_subject_id)->count();

        $total_female_students = self::with(['student' => function($query) {
                        $query->where('student.gender_id', '=', 2);
                    }])->whereHas('student', function ($query) {
                    $query->where('gender_id', '=', 2);
                })->where('institution_subject_id', '=', $institution_subject_id)->count();

        $totalStudents = $total_female_students + $total_male_students;
        
        
        return [
            'total'=> $totalStudents,
            'total_female_students' => $total_female_students,
            'total_male_students' => $total_male_students
        ];
    }
}