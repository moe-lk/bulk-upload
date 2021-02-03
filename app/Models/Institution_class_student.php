<?php

namespace App\Models;

use Webpatser\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution_class_student extends Model
{

    use SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_class_students';

    protected $softDelete = true;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'institution_id', 'student_status_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
    protected $dates = ['date_of_birth', 'date_of_death', 'last_login', 'modified', 'created', 'start_date', 'end_date', 'modified', 'created', 'modified', 'created'];



    public $timestamps = false;


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
            $model->created = now();
        });
    }

    public function student()
    {
        return $this->belongsTo('App\Models\Security_user', 'student_id');
    }

    public  static function getStudentsCount($institution_class_id)
    {
        $total_male_students = self::with(['student' => function ($query) {
            $query->where('student.gender_id', '=', 1);
        }])->whereHas('student', function ($query) {
            $query->where('gender_id', '=', 1);
        })->where('institution_class_id', '=', $institution_class_id)->count();

        $total_female_students = self::with(['student' => function ($query) {
            $query->where('student.gender_id', '=', 2);
        }])->whereHas('student', function ($query) {
            $query->where('gender_id', '=', 2);
        })->where('institution_class_id', '=', $institution_class_id)->count();

        $totalStudents = $total_female_students + $total_male_students;


        return [
            'total' => $totalStudents,
            'total_female_students' => $total_female_students,
            'total_male_students' => $total_male_students
        ];
    }

    public static function  isDuplicated($inputs)
    {

        $exists = self::where('student_id', '=', $inputs['student_id'])
            ->where('institution_class_id', $inputs['institution_class_id'])
            ->count();

        return $exists;
    }

    public function getStudentNewClass($student)
    {
        return self::query()
            ->where('student_id', $student['student_id'])
            ->join('institution_classes', 'institution_class_students.institution_class_id', '=', 'institution_classes.id')
            ->where('institution_class_students.student_id', $student['student_id'])
            ->where('institution_class_students.academic_period_id',$student['academic_period_id'])
            ->where('institution_class_students.institution_id',$student['institution_id'])
            ->whereNull('institution_class_students.deleted_at')
            ->get()->first();
    }


    /**
     * Create new Institution class entry for student from examination data
     *
     * @param [type] $student
     * @param [type] $admissionInfo
     * @return void
     */
    public static function createExaminationData($student, $admissionInfo)
    {
        try {
            self::updateOrcreate([
                'student_id' => $student['id'],
                'institution_class_id' => $admissionInfo['instituion_class']['id'],
                'education_grade_id' => $admissionInfo['education_grade']->id,
                'academic_period_id' => $admissionInfo['academic_period']->id,
                'institution_id' => $admissionInfo['instituion']->id,
                'student_status_id' => 1,
                'created_user_id' => 1
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }

    public static function createOrUpdate($studentId,$params,$file){
       return  self::create([
            'student_id' => $studentId,
            'institution_class_id' => $params['institution_class']->id,
            'education_grade_id' => $params['institution_grade']->education_grade_id,
            'academic_period_id' => $params['academic_period']->id,
            'institution_id' => $params['institution'],
            'student_status_id' => 1,
            'created_user_id' => $file['security_user_id']
        ]);
    }
}
