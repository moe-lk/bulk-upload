<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Institution_grade extends Base_Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_grades';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['education_grade_id', 'start_date', 'start_year', 'end_date', 'end_year', 'institution_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'promoted'];

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
    protected $dates = ['start_date', 'end_date', 'modified', 'created'];

    public function isPool($grade)
    {
        $classes = Institution_class_grade::query()->where('education_grade_id', '=', $grade->education_grade_id)
            ->where('institution_id', '=', $grade->institution_id)->get();
        return true;
    }

    public function getNumberOfParallelClasses($id)
    {
        $this->hasMany('App\Models\Institution_class_grade', 'education_grade_id', 'education_grade_id')->count();
    }

    public function parallelClasses()
    {
        $this->hasMany('App\Models\Institution_class_grade', 'education_grade_id');
//        $this->hasManyThrough('App\Models\Institution_class_grade','App\Models\Institution_class','institution_class_id','education_grade_id');
    }

    public function getParallelClasses($id, $institutionId, $educationGradeId, $academicPeriodId)
    {
        if (!is_null($id)) {
            return self::find($id)
                ->select('institution_grades.id as insGrade','institution_classes.id', 'institution_classes.name', 'institution_grades.education_grade_id')
                ->join('institution_classes', function ($join) use ($educationGradeId, $academicPeriodId) {
                    $join->on('institution_classes.institution_id', '=', 'institution_grades.institution_id')
                        ->where('institution_classes.academic_period_id', $academicPeriodId)
                        ->join('institution_class_grades', function ($join) use ($educationGradeId) {
                            $join->on('institution_class_grades.institution_class_id', '=', 'institution_classes.id')
                                ->where('institution_class_grades.education_grade_id', $educationGradeId);
                        });
                })
                ->where('institution_grades.education_grade_id', $educationGradeId)
                ->where('institution_grades.institution_id', $institutionId)
                ->get();
        }else{
            return null;
        }
    }



    public function updatePromoted($year,$id){
        self::where('id',$id)->update(['promoted'=>$year]);
    }

    public function getInstitutionGrade($institutionId,$gradeId){
         return self::where('education_grade_id',$gradeId)
             ->where('institution_id',$institutionId)->get()->first();
    }

    public function getInstitutionGradeToPromoted($year){
        return self::query()
            ->where('promoted','=',$year-1)
//            ->join('institutions', function($join) use ($year){
//                $join->on('institutions.id','=','institution_grades.institution_id')
//                    ->where('institution_grades.promoted','=',$year-1);
//            })
                ->orderBy('institution_id')
            ->get()->first();
    }

}
