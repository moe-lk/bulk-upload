<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution_shift extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_shifts';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['start_time', 'end_time', 'academic_period_id', 'institution_id', 'location_institution_id', 'shift_option_id', 'previous_shift_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public function shiftExists($shift){
       return self::query()
            ->where('institution_id',$shift['institution_id'])
            ->where('location_institution_id',$shift['location_institution_id'])
            ->where('shift_option_id',$shift['shift_option_id'])
            ->where('academic_period_id',$shift['academic_period_id'])->exists();
    }

    public function getShiftsToClone(string $year){
        return self::query()
            // ->select('institution_shifts.*')
            // ->select('institution_shifts.id','start_time','end_time','academic_period_id','institution_id','location_institution_id','shift_option_id','previous_shift_id','institution_shifts.modified_user_id','institution_shifts.modified','institution_shifts.created_user_id','institution_shifts.created','cloned')
            ->join('academic_periods','academic_periods.id','=','institution_shifts.academic_period_id')
            ->where('academic_periods.code',$year)
            ->where('institution_shifts.cloned',$year)
            ->get()->toArray();
    }

    public function getShiftsTodelete(string $year,$academic_period_id){
        return self::query()
            // ->select('institution_shifts.*','academic_periods.academic_period_id')
            ->join('academic_periods','academic_periods.id','=','institution_shifts.academic_period_id')
            ->where('academic_period_id',$academic_period_id)
            ->where('institution_shifts.cloned',$year)
            ->get()->toArray();
    }
}
