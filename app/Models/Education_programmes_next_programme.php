<?php

namespace App;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Education_programmes_next_programme extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'education_programmes_next_programmes';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['education_programme_id', 'next_programme_id'];

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
