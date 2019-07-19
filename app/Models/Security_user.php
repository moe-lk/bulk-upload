<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use App\Models\Base_Model;


class Security_user extends Base_Model  {

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


   public function rules()
    {
        return [
                'identity_number' => [
                    'required',
                    'unique:security_users,identity_number',
                ],
            // 'identity_number' => 'unique:security_users,identity_number,NULL,id,identity_type_id,'.,
            // 'identity_number' => 'unique:identity_type_id',
        ];
    }




    public function getAuthPassword(){
        return $this->password;
    }

    public function uploads(){
       return $this->hasMany('App\Models\Upload');
    }

}