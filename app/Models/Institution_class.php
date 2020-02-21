<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution_class extends Model  {

    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_classes';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'no_of_students', 'class_number', 'total_male_students', 'total_female_students', 'staff_id', 'secondary_staff_id', 'institution_shift_id', 'institution_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public function class_teacher(){
        return $this->belongsTo('App\Models\Security_group_user','staff_id','security_user_id');
    }

    public function institution(){
        return $this->belongsTo('App\Models\Institution','institution_id');
    }


}
