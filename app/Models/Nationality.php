<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'nationalities';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'order', 'visible', 'editable', 'identity_type_id', 'default', 'international_code', 'national_code', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

}