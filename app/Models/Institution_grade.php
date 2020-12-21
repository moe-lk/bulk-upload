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
        $data = self::find($id)->select('institution_grades.id as insGrade', 'institution_classes.id', 'institution_classes.name', 'institution_grades.education_grade_id')
            ->join('institution_classes', function ($join) use ($educationGradeId, $academicPeriodId) {
                $join->on('institution_classes.institution_id', '=', 'institution_grades.institution_id')
                    ->where('institution_classes.academic_period_id', $academicPeriodId)
                    ->join('institution_class_grades', function ($join) use ($educationGradeId) {
                        $join->on('institution_class_grades.institution_class_id', '=', 'institution_classes.id')
                            ->where('institution_class_grades.education_grade_id', $educationGradeId);
                    })
                    ->groupBy('institution_classes.id');
            })
            ->where('institution_grades.education_grade_id', $educationGradeId)
            ->where('institution_grades.institution_id', $institutionId)
            ->groupBy('institution_classes.id')
            ->get();
        return $data;
    }


    /**
     * update processed grade
     *
     * @param $year
     * @param $id
     */
    public function updatePromoted($year, $id)
    {
        self::where('id', $id)->update(['promoted' => $year]);
    }

    /**
     * get grade of an institution
     *
     * @param $institutionId
     * @param $gradeId
     * @return mixed
     */
    public function getInstitutionGrade($institutionId, $gradeId)
    {
        return self::where('education_grade_id', $gradeId)
            ->where('institution_id', $institutionId)->get()->first();
    }

    /**
     * @param $year
     * @param null $institution
     * @return mixed
     */
    public function getInstitutionGradeToPromoted($year, $institution = null, $mode)
    {
        $data = array();
        $query = self::query()
            ->select('education_grades.name', 'institutions.code', 'institutions.name as institution_name', 'institution_grades.id', 'institution_grades.institution_id', 'institution_grades.education_grade_id')
            // ->where('promoted', '=', $year)
            ->join('education_grades', 'institution_grades.education_grade_id', '=', 'education_grades.id')
            ->join('institutions', function ($join) use ($year, $institution) {
                $join->on('institutions.id', '=', 'institution_grades.institution_id')
                    ->where('institutions.code', '=', $institution);
            })
            ->join('education_programmes', 'education_grades.education_programme_id', 'education_programmes.id');
        switch ($mode) {
            case '1-5':
                $query->where('education_programmes.education_cycle_id', 1);
                break;
            case '6-11':
                $query->whereIn('education_programmes.education_cycle_id', [2, 3]);
                $query->whereNotIn('education_grades.id',[29,34]);
                break;
            case 'AL':
                $query->where('education_programmes.education_cycle_id', 4);
                break; 
            case 'SP':
                $query->where('education_programmes.education_cycle_id', 7);
                break;
        }
        $data = $query->groupBy('institution_grades.id')
            ->get()->toArray();
        return $data;
    }

    /**
     * @param $year
     * @return mixed
     */
    public function getInstitutionGradeList($year, $limit,$mode)
    {
        $query = $this->select('education_grades.name', 'institutions.code', 'institutions.name as institution_name', 'institution_grades.id', 'institution_grades.institution_id', 'institution_grades.education_grade_id')
            // ->where('promoted', '=', $year)
            ->join('education_grades', 'institution_grades.education_grade_id', '=', 'education_grades.id')
            ->join('institutions', function ($join) use ($year) {
                $join->on('institutions.id', '=', 'institution_grades.institution_id');
            })
            ->join('education_programmes', 'education_grades.education_programme_id', 'education_programmes.id');
            switch ($mode) {
                case '1-5':
                    $query->whereIn('education_programmes.education_cycle_id', [1,2]);
                    break;
                case '6-11':
                    $query->whereIn('education_programmes.education_cycle_id', [2,3,4]);
                    break;
                case 'AL':
                    $query->where('education_programmes.education_cycle_id', 4);
                    break; 
                case 'SP':
                    $query->where('education_programmes.education_cycle_id', 7);
                    break;
            }
            $data = $query->groupBy('institutions.id')
                ->limit($limit)
                ->get()->toArray();
            return $data;
    }

    public function getGradeSubjects($institutionId){
        return self::query()
        ->select('institution_grades.institution_id','education_grades_subjects.education_grade_id','education_grades_subjects.education_subject_id','education_subjects.name')
        ->where('institution_grades.institution_id',$institutionId)
        ->join('education_grades', 'institution_grades.education_grade_id', 'education_grades.id')
        ->join('education_grades_subjects','education_grades.id','education_grades_subjects.education_grade_id')
        ->join('education_subjects','education_grades_subjects.education_subject_id','education_subjects.id')
        ->groupBy('education_grades_subjects.id')
        ->get()
        ->toArray();
    }
}
