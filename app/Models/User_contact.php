<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_contact extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_contacts';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['contact_type_id', 'value', 'preferred', 'security_user_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public static function createOrUpdate($data,$user){

        if(!is_null($data['contact'])){
            $exists = self::where('security_user_id', $data->id)
                ->where('value',$data['contact']) 
                ->first(); 

            if(is_null($exists)){
                $data = [
                    'security_user_id' => $data->id,
                    'value' => $data['contact'],
                    'contact_type_id' => 13,
                    'created' => now(),
                    'created_user_id' => $user,
                    'preferred' => 1
                ];
                self::updateOrCreate($data);
            }else{
                $exists = $exists->toArray();
                $exists['preferred'] = 1;   
                $exists['value'] = $data['contact'];
                $exists['modified_user_id'] = $user;
                $exists['contact_type_id'] = 13;
                $exists['modified'] = now();
                self::updateOrCreate($exists);
            }    
        }

    }

}