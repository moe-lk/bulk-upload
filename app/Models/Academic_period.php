<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Academic_period extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'academic_periods';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'start_date', 'start_year', 'end_date', 'end_year', 'school_days', 'current', 'editable', 'parent_id', 'lft', 'rght', 'academic_period_level_id', 'order', 'visible', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public function getAcademicPeriod($year){
        return self::query()->where('code',$year)->first();
    }

}
