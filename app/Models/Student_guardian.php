<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Base_Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Uuid\Uuid;

class Student_guardian extends Base_Model  {

    use SoftDeletes;

    public const CREATED_AT = 'created';

    public const UPDATED_AT = 'modified';


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'student_guardians';

    protected $softDelete = true;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['student_id', 'guardian_id', 'guardian_relation_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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


    public $timestamps = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
            $model->created_user_id = 1;
        });
    }

    public static function createStudentGuardian($student,$guardian,$user){
     
        $exist = true;
        if(!is_null($guardian))
            $exist = self::where('student_id', $student->student_id)
            ->where('guardian_relation_id', $guardian->guardian_relation_id)
            ->exists();

        $totalGuardians = self::where('student_id',$student->student_id)->count();

        $data = [
            'student_id' => $student->student_id,
            'guardian_id' => $guardian->id,
            'guardian_relation_id' => $guardian->guardian_relation_id,
            'created_user_id' => $user
        ];
        if(!$exist){
            $data['created'] = now();
            self::create($data);
        }else{
            $data['modified'] = now();
            self::where('student_id' , $student->student_id)
            ->where('guardian_relation_id',$guardian->guardian_relation_id)
            ->update($data);
        }
    }

}