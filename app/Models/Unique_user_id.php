<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unique_user_id extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'unique_user_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['unique_id', 'security_user_id'];

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
    protected $dates = [];

}