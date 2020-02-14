<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Institution_grade extends Base_Model  {

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

    public function isPool($grade){
        $classes = Institution_class_grade::query()->where('education_grade_id' ,'=',$grade->education_grade_id)
            ->where('institution_id','=',$grade->institution_id)->get();
        return true;
    }

    public function getNumberOfParallelClasses($id){
        $this->hasMany('App\Models\Institution_class_grade','education_grade_id','education_grade_id')->count();
    }

    public function parallelClasses(){
        $this->hasMany('App\Models\Institution_class_grade','education_grade_id');
//        $this->hasManyThrough('App\Models\Institution_class_grade','App\Models\Institution_class','institution_class_id','education_grade_id');
    }

    public function getParallelClasses($id){
      return   self::find($id)->with(['parallelClasses'])->whereHas(['parallelClasses']);
    }

    public function updatePromoted($year,$id){
        self::where('id',$id)->update(['promoted'=>$year]);
    }

    public function getInstitutionGrade($institutionId,$gradeId){
         return self::where('education_grade_id',$gradeId)
             ->where('institution_id',$institutionId)->get()->first();
    }

}
