<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution_subjects_room extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_subjects_rooms';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['institution_subject_id', 'institution_room_id'];

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
    protected $dates = ['modified', 'created', 'modified', 'created'];

}