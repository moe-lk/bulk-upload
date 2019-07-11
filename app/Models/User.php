<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable  {

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

    public function roles(){
        return $this->newBelongsToMany(Role::class);
    }



}