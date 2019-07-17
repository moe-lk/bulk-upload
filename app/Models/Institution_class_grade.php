<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution_class_grade extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_class_grades';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['institution_class_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public function educationSubject(){
        return $this->hasManyThrough('App\Models\Education_grades_subject','App\Models\Institution_subject',
            'education_subject_id' ,'education_subject_id');
    }

}