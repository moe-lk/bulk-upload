<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution_subject extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_subjects';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'no_of_seats', 'total_male_students', 'total_female_students', 'institution_id', 'education_grade_id', 'education_subject_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
    protected $dates = ['modified', 'created', 'modified', 'created'];


    public  function institutionGradeSubject(){
        return $this->belongsTo('App\Models\Education_grades_subject','education_subject_id','education_subject_id');
    }

    public  function institutionOptionalGradeSubject(){
        return $this->belongsTo('App\Models\Education_grades_subject','education_grade_id','education_grade_id');
    }

    public  function institutionMandatoryGradeSubject(){
        return $this->belongsTo('App\Models\Education_grades_subject','education_grade_id','education_grade_id');
    }


    public  function institutionClassSubject(){
        return $this->hasMany('App\Models\Institution_class_subject','institution_class_id','id');
    }



    public function getInstitutionSubjects($institution_id,$academic_period_id){
        $query =  self::query()->where('institution_id',$institution_id)
            ->where('academic_period_id',$academic_period_id)
            ->join('education_grades_subjects','institution_subjects.education_subject_id','education_grades_subjects.id')
            ->join('education_grades', 'education_grades_subjects.education_grade_id', 'education_grades.id')
            ->groupBy('education_grades_subjects.id');
        return $query->get()->toArray();
    }

    public  static function getStudentsCount($institution_subject_id)
    {
        $total_male_students = self::with(['student' => function ($query) {
            $query->where('student.gender_id', '=', 1);
        }])->whereHas('student', function ($query) {
            $query->where('gender_id', '=', 1);
        })->where('institution_subject_id', '=', $institution_subject_id)->count();

        $total_female_students = self::with(['student' => function ($query) {
            $query->where('student.gender_id', '=', 2);
        }])->whereHas('student', function ($query) {
            $query->where('gender_id', '=', 2);
        })->where('institution_subject_id', '=', $institution_subject_id)->count();

        $totalStudents = $total_female_students + $total_male_students;


        return [
            'total' => $totalStudents,
            'total_female_students' => $total_female_students,
            'total_male_students' => $total_male_students
        ];
    }


}
