<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

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

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function educationSubject(){
        return $this->hasManyThrough('App\Models\Education_grades_subject','App\Models\Institution_subject',
            'education_subject_id' ,'education_subject_id');
    }

    /**
     * @param $id
     * @param $educationGradeId
     * @param $institutionId
     * @return mixed
     */
    public function getParallelClasses($id, $educationGradeId, $institutionId)
    {
        return self::find($id)
            ->where('institution_grades.id', $id)
            ->where('institution_grades.education_grade_id', $educationGradeId)
            ->where('institution_grades.institution_id', $institutionId)
            ->join('institution_grades', 'institution_classes.id', '=', 'institution_grades.institution_class_id')
            ->get()->toArray();
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
        });
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classes(){
        return $this->belongsTo('App\Models\Institution_grade','institution_class_id','id');
    }
}
