<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


class User extends Authenticatable   {

    use HasApiTokens, Notifiable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
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
        'remember_token'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

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
    protected $dates = ['email_verified_at'];


    public function permissions(){
        return $this->hasMany('App\Models\Security_group_user','security_user_id','id')
             ->where('security_group_users.security_role_id','=',5)
             ->with(['security_group_institution','institution_staff','security_group'  , 'staff_class','institution_group' , 'roles']);
    }

    public function principal(){
        return $this->hasMany('App\Models\Security_group_user','security_user_id','id')
            ->where('security_group_users.security_role_id','=',4)
            ->with(['security_group_institution','institution_staff','security_group'  , 'staff_class','institution_group' , 'roles']);
    }

    public function zonal_cordinator(){
        return $this->hasMany('App\Models\Security_group_user','security_user_id','id')
            ->where('security_group_users.security_role_id','=',3)
            ->with(['security_group_institution','institution_staff','security_group'  , 'staff_class','institution_group' , 'roles']);
    }


    public function institution_class_teacher(){
        return $this->hasMany('App\Models\Institution_staff','staff_id','id')
            ->with(['staff_class']);
    }

    public function teacher_classes(){
        return $this->hasMany('App\Models\Institution_class','staff_id','id');
    }

    public function findForPassport($username) {
        return self::where('username', $username)->first(); // change column name whatever you use in credentials
    }
}
