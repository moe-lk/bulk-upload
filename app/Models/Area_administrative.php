<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area_administrative extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'area_administratives';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'is_main_country', 'parent_id', 'lft', 'rght', 'area_administrative_level_id', 'order', 'visible', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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