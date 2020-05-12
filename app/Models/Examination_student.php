<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examination_student extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'examination_students';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nsid', 'school_id', 'full_name', 'dob', 'gender', 'address', 'annual_income', 'has_special_need', 'disable_type', 'disable_details', 'special_education_centre'];

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
    protected $casts = ['has_special_need' => 'boolean'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['dob'];

}
