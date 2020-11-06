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

    /**
     * @param $id
     */
    public function getNumberOfParallelClasses($id)
    {
        $this->hasMany('App\Models\Institution_class_grade', 'education_grade_id', 'education_grade_id')->count();
    }

    /**
     * get parallel class information of a grade
     *
     * @param $id
     * @param $institutionId
     * @param $educationGradeId
     * @param $academicPeriodId
     * @return |null
     */
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


    /**
     * update processed grade
     *
     * @param $year
     * @param $id
     */
    public function updatePromoted($year, $id){
        self::where('id',$id)->update(['promoted'=>$year]);
    }

    /**
     * get grade of an institution
     *
     * @param $institutionId
     * @param $gradeId
     * @return mixed
     */
    public function getInstitutionGrade($institutionId, $gradeId){
         return self::where('education_grade_id',$gradeId)
             ->where('institution_id',$institutionId)->get()->first();
    }

    /**
     * @param $year
     * @param null $institution
     * @return mixed
     */
    public function getInstitutionGradeToPromoted($year, $institution = null){
        return self::query()
            ->select('education_grades.name','institutions.code','institutions.name as institution_name','institution_grades.id','institution_grades.institution_id','institution_grades.education_grade_id')
            ->where('promoted','=',$year)
            ->join('education_grades','institution_grades.education_grade_id','=','education_grades.id')
            ->join('institutions', function($join) use ($year,$institution){
                $join->on('institutions.id','=','institution_grades.institution_id')
                    ->where('institutions.code','=',$institution);
            })
                ->orderBy('institution_id')
            ->get()->toArray();
    }

    /**
     * @param $year
     * @return mixed
     */
    public function getInstitutionGradeList($year,$limit){
        return self::query()
            ->select('education_grades.name','institutions.code','institutions.name as institution_name','institution_grades.id','institution_grades.institution_id','institution_grades.education_grade_id')
            ->where('promoted','=',$year)
            ->join('education_grades','institution_grades.education_grade_id','=','education_grades.id')
            ->join('institutions', function($join) use ($year){
                $join->on('institutions.id','=','institution_grades.institution_id');
            })
            ->orderBy('institution_id')
            ->groupBy('institution_grades.id')
            ->limit($limit)
            ->get()
            ->toArray();
    }

}
