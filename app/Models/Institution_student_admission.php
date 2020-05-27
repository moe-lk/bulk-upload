<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution_student_admission extends Base_Model {

    public const CREATED_AT = 'created';

    public const UPDATED_AT = 'modified';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_student_admission';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['start_date', 'end_date', 'student_id', 'status_id', 'assignee_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'admission_id'];

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
    protected $dates = ['modified', 'created', 'modified', 'created', 'start_date', 'end_date', 'modified', 'created'];

}