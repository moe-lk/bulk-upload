<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Institution_student;
use App\User_body_mass;


class Security_user extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */

    public $timestamps = false;

    protected $table = 'security_users';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openemis_no', 
        'first_name', 
        'last_name', 
        'address', 
        'address_area_id', 
        'birthplace_area_id', 
        'gender_id', 
        'date_of_birth', 
        'nationality_id', 
        'identity_type_id', 
        'identity_number', 
        'is_student', 
        'modified_user_id', 
        'modified', 
        'created_user_id', 
        'created',
        'username',
        'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'username',
        'modified_user_id', 
        'middle_name', 
        'third_name', 
        'modified', 
        'created_user_id', 
        'created'
        
    ];
    


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function institutionStudents(){
        return $this->hasOne(Institution_student::class,'student_id');
    }

   

    public function institutionStudentsClass(){
        return $this->hasOne(Institution_student::class, 'student_id');
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date_of_birth', 'date_of_death', 'last_login', 'modified', 'created'];

}