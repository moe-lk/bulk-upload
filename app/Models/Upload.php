<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'uploads';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['security_user_id', 'institution_id', 'institution_class_id', 'model', 'filename', 'deleted_at'];

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
    protected $dates = ['deleted_at'];

    public function user(){
        return $this->belongsTo('App\Models\Security_user','security_user_id');
    }

}