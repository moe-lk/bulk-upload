<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use App\Models\Base_Model;
use Mohamednizar\MoeUuid\MoeUuid;

class Security_user extends Base_Model  {

    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';
    /**
     * The database table used by the model.
     *
     * @var string
     */

    public $timestamps = true;

    protected $table = 'security_users';

    protected $appends = [
      'special_need_name'
    ];

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openemis_no',
        'first_name',
        'last_name',
        'address',
        'address_area_id',
        'birthplace_area_id',
        'gender_id',
        'remember_token',
        'date_of_birth',
        'nationality_id',
        'identity_type_id',
        'identity_number',
        'is_student',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created',
        'username',
        'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'modified_user_id',
        'middle_name',
        'third_name',
        'modified',
        'created_user_id',
        'created'

    ];




    public function getSpecialNeedNameAttribute() {
        return optional($this->special_needs())->special_need_difficulty_id;
    }

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function institutionStudents(){
        return $this->hasOne(Institution_student::class,'student_id');
    }



    public function institutionStudentsClass(){
        return $this->hasOne(Institution_student::class, 'student_id');
    }



    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date_of_birth', 'date_of_death', 'last_login', 'modified', 'created'];



   public function rules()
    {
        return [
                'identity_number' => [
                    'required',
                    'unique:security_users,identity_number',
                ],
            // 'identity_number' => 'unique:security_users,identity_number,NULL,id,identity_type_id,'.,
            // 'identity_number' => 'unique:identity_type_id',
        ];
    }






    public function getAuthPassword(){
        return $this->password;
    }

    public function uploads(){
       return $this->hasMany('App\Models\Upload');
    }

    public function class(){
        return $this->belongsTo('App\Models\Institution_class_student','id','student_id');
    }

    public function special_needs(){
        return $this->hasMany('App\Models\User_special_need','id','security_user_id');
    }

    public function genUUID(){
        $uuid = Uuid::generate(4);
        return str_split($uuid,'8')[0];
    }

    /**
     * First level search for students
     */

    public function getMatches($student){
       return $this->where([
            'gender_id' => $student['gender'], 
            'date_of_birth' => $student['b_date'],
            'institutions.code' => $student['schoolid']
            ])
            ->join('institution_students','security_users.id','institution_students.student_id')
            ->join('institutions','institution_students.institution_id','institutions.id')
            ->get()->toArray();
    }

    /**
     * insert student data from examination
     * @input array
     * @return array
     */
    public function insertExaminationStudent ($student){
        $studentNSID = MoeUuid::getUniqueAlphanumeric(3);
        $student = $this->create([
            'username' => str_replace('-','',$studentNSID),
            'openemis_no' => $studentNSID,
            'first_name' => $student['f_name'], // here we save full name in the column of first name. re reduce breaks of the system.
            'last_name' => genNameWithInitials($student['f_name']),
            'gender_id' => $student['gender'],
            'date_of_birth' => $student['b_date'],
            'address' => $student['pvt_address'],
            'is_student' => 1,
            'created_user_id' => 1
        ]);

        return $student;
    }

    public function updateExaminationStudent($student,$sis_student){
        $studentNSID = !MoeUuid::isValidMoeUuid($sis_student['openemis_no']) ? MoeUuid::getUniqueAlphanumeric(3) : $sis_student['openemis_no'];
        $student = $this->update([
            'id' => $sis_student['id'],
            'username' => str_replace('-','',$studentNSID),
            'openemis_no' => $studentNSID,
            'first_name' => $student['f_name'], // here we save full name in the column of first name. re reduce breaks of the system.
            'last_name' => genNameWithInitials($student['f_name']),
            'gender_id' => $student['gender'],
            'date_of_birth' => $student['b_date'],
            'address' => $student['pvt_address'],
            'modified' => now()
        ]);
    }
}
