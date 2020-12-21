<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Security_group_institution extends Model  {


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'security_group_institutions';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['security_group_id', 'institution_id', 'created_user_id', 'created'];

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
    protected $dates = ['modified', 'created', 'created', 'created'];



    public function institution(){
        return $this->belongsTo('App\Models\Institution','institution_id');
    }

    public function institution_classes(){
        return $this->hasMany('App\Models\Institution_class','institution_id','institution_id')
        ->select('institution_classes.*')
        ->join('academic_periods', function($query){
            $query->on('institution_classes.academic_period_id','academic_periods.id');
            $query->whereIn('academic_periods.code',['2020','2019/2020']);
        });
    }

    public function security_group(){
        return $this->belongsTo('App\Models\Security_group','security_group_id');
    }

}