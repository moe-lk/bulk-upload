<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education_grades_subject extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'education_grades_subjects';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['education_grade_id', 'education_subject_id', 'hours_required', 'visible', 'auto_allocation', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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


    public function institutionGradeSubject(){
        return $this->hasMany('App\Models\Institution_subject','education_grade_id','education_grade_id');
    }

    public function getGradeSubjects($educationGrade){
        self::where('education_grade_id',$educationGrade['education_grade_id'])
        ->get()
        ->toArray();
        ;
    }

}