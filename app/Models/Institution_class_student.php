<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class Institution_class_student extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_class_students';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'institution_id', 'student_status_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
    protected $dates = ['date_of_birth', 'date_of_death', 'last_login', 'modified', 'created', 'start_date', 'end_date', 'modified', 'created', 'modified', 'created'];



    public $timestamps = false;


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
            $model->created = now();
        });
    }

    public function student(){
        return $this->belongsTo('App\Models\Security_user','student_id');
    }
}