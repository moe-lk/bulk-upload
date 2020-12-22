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


    public static function createOrUpdate($studentId,$row,$file){
        if (!empty($row['bmi_weight']) && !empty($row['bmi_weight']) && !empty($row['bmi_date_yyyy_mm_dd'])) {
            try {
                // convert Meeter to CM
                $hight = $row['bmi_height'] / 100;
                //calculate BMI
                $bodyMass = ($row['bmi_weight']) / pow($hight, 2);
                $bmiAcademic = Academic_period::where('name', '=', $row['bmi_academic_period'])->first();
                
                $count = User_body_mass::where('academic_period_id' ,'=',$bmiAcademic->id)
                            ->where('security_user_id',$studentId)->count();
                $data = [
                    'height' => $row['bmi_height'],
                    'weight' => $row['bmi_weight'],
                    'date' => $row['bmi_date_yyyy_mm_dd'],
                    'body_mass_index' => $bodyMass,
                    'academic_period_id' => $bmiAcademic->id,
                    'security_user_id' => $studentId,
                    'created_user_id' => $file['security_user_id']
                ];
                // dd($data);
                if($count == 0){
                    self::create($data);
                }else{
                    self::where('security_user_id',$studentId)
                    ->update($data);
                }         
            } catch (\Throwable $th) {
                \Log::error('User_body_mass:' . $th->getMessage());
            }
        }
    }
}