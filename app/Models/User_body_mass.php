<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_body_mass extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */


    Public const CREATED_AT = 'created';
    Public const UPDATED_AT = 'modified';
    protected $table = 'user_body_masses';

    public $timestamps = false;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['date', 'height', 'weight', 'body_mass_index', 'comment', 'academic_period_id', 'security_user_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
    protected $dates = ['date', 'modified', 'created'];


}