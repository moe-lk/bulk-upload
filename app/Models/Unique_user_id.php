<?php

namespace App\Models;

use Lsf\UniqueUid\UniqueUid;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\Throw_;

class Unique_user_id extends Model
{

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

    public function __construct()
    {
        $this->uniqueUserId = new UniqueUid();
    }



    public  function updateOrInsertRecord($user)
    {
       try {
            // regenerate unique id if it's not available
        $uniqueId =  $this->uniqueUserId::isValidUniqueId($user['openemis_no'],9) ? $this->uniqueUserId::getUniqueAlphanumeric() : $user['openemis_no'];

        //check if the user's entry exits ?
        $exists = Unique_user_id::where('unique_id' , $uniqueId)->exists();

        if (!$exists) {
            // try to feed unique user id
            Unique_user_id::insert([
                'security_user_id' => $user['id'],
                'unique_id' =>  $uniqueId
            ]);
        }
        return $user;
       } catch (\Exception $th) {
            Log::error($th->getMessage());
           $user['openemis_no'] = $this->uniqueUserId::getUniqueAlphanumeric();
           $this->updateOrInsertRecord($user);
       }
    }
}
