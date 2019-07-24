<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education_grade extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'education_grades';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'admission_age', 'order', 'visible', 'education_stage_id', 'education_programme_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

}