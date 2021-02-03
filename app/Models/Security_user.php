<?php

namespace App\Models;

use App\Models\User_contact;
use Lsf\UniqueUid\UniqueUid;
use App\Models\Unique_user_id;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Security_user extends Model
{

    use SoftDeletes;

    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';
    /**
     * The database table used by the model.
     *
     * @var string
     */

    public $timestamps = true;

    protected $softDelete = true;

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


    public function getSpecialNeedNameAttribute()
    {
        return optional($this->special_needs())->special_need_difficulty_id;
    }

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function institutionStudents()
    {
        return $this->hasOne(Institution_student::class, 'student_id');
    }

    public function institutionStudentsClass()
    {
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
            ]
        ];
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function uploads()
    {
        return $this->hasMany('App\Models\Upload');
    }

    public function class()
    {
        return $this->belongsTo('App\Models\Institution_class_student', 'id', 'student_id');
    }

    public function special_needs()
    {
        return $this->hasMany('App\Models\User_special_need', 'id', 'security_user_id');
    }

    public function genUUID()
    {
        $uuid = Uuid::generate(4);
        return str_split($uuid, '8')[0];
    }

    /**
     * First level search for students
     *
     * @param array $student
     * @return array
     */
    public function getMatches($student)
    {
        return $this
            ->where('gender_id', $student['gender'] + 1)
            ->where('institutions.code', $student['schoolid'])
            ->where('date_of_birth', $student['b_date'])
            ->join('institution_students', 'security_users.id', 'institution_students.student_id')
            ->join('institutions', 'institution_students.institution_id', 'institutions.id')
            ->get()->toArray();
    }

    /**
     * First level search for students
     *
     * @param array $student
     * @return array
     */
    public function getStudentCount($student)
    {
        return $this
            ->where('gender_id', $student['gender'] + 1)
            ->where('institutions.code', $student['schoolid'])
            ->where('date_of_birth', $student['b_date'])
            ->join('institution_students', 'security_users.id', 'institution_students.student_id')
            ->join('institutions', 'institution_students.institution_id', 'institutions.id')
            ->count();
    }

    /**
     * insert student data from examination
     * @input array
     * @return array
     */
    public function insertExaminationStudent($student)
    {
        $this->uniqueUserId = new Unique_user_id();
        $this->uniqueUId = new UniqueUid();
        $uniqueId = $this->uniqueUId::getUniqueAlphanumeric();
        $studentData = [
            'username' => str_replace('-', '', $uniqueId),
            'openemis_no' => $uniqueId, // Openemis no is unique field, in case of the duplication it will failed
            'first_name' => $student['f_name'], // here we save full name in the column of first name. re reduce breaks of the system.
            'last_name' => genNameWithInitials($student['f_name']),
            'gender_id' => $student['gender'] + 1,
            'date_of_birth' => $student['b_date'],
            'address' => $student['pvt_address'],
            'is_student' => 1,
            'updated_from' => 'doe',
            'created' => now(),
            'created_user_id' => 1
        ];
        try {
            $id = $this->insertGetId($studentData);
            $studentData['id'] = $id;
            $this->uniqueUserId->updateOrInsertRecord($studentData);
            return $studentData;
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            // in case of duplication of the Unique ID this will recursive.
            $sis_student['openemis_no'] = $this->uniqueUId::getUniqueAlphanumeric();
            $this->insertExaminationStudent($sis_student);
        }
        return $studentData;
    }

    /**
     * Update the existing student's data
     *
     * @param array $student
     * @param array $sis_student
     * @return array
     */
    public function updateExaminationStudent($student, $sis_student)
    {
        $this->uniqueUserId = new Unique_user_id();
        $this->uniqueUId = new UniqueUid();
        // regenerate unique id if it's not available
        $uniqueId = ($this->uniqueUId::isValidUniqueId($sis_student['openemis_no'], 9)) ?  $sis_student['openemis_no'] : $this->uniqueUId::getUniqueAlphanumeric();

        $studentData = [
            'username' => str_replace('-', '', $uniqueId),
            'openemis_no' => $uniqueId, // Openemis no is unique field, in case of the duplication it will failed
            'first_name' => $student['f_name'], // here we save full name in the column of first name. re reduce breaks of the system.
            'last_name' => genNameWithInitials($student['f_name']),
            'date_of_birth' => $student['b_date'],
            'address' => $student['pvt_address'],
            'updated_from' => 'doe',
            'modified' => now()
        ];

        try {
            self::where('id', $sis_student['student_id'])->update($studentData);
            $studentData['id'] = $sis_student['student_id'];
            $this->uniqueUserId->updateOrInsertRecord($studentData);
            return $studentData;
        } catch (\Exception $th) {
            Log::error($th);
            // in case of duplication of the Unique ID this will recursive.
            $sis_student['openemis_no'] = $this->uniqueUId::getUniqueAlphanumeric();
            $this->updateExaminationStudent($student, $sis_student);
        }
    }

    public static function createOrUpdateStudentProfile($row, $prefix, $file)
    {
        try {
            $uniqueUid = new UniqueUid();
            $BirthArea = Area_administrative::where('name', 'like', '%' . $row['birth_registrar_office_as_in_birth_certificate'] . '%')->first();
            $nationalityId = Nationality::where('name', 'like', '%' . $row['nationality'] . '%')->first();
            $identityType = Identity_type::where('national_code', 'like', '%' . $row['identity_type'] . '%')->first();
            
            $date = $row['date_of_birth_yyyy_mm_dd'];
            $identityType = $identityType !== null ? $identityType->id : null;
            $nationalityId = $nationalityId !== null ? $nationalityId->id : null;

            $BirthArea = $BirthArea !== null ? $BirthArea->id : null;
            $openemisNo = $uniqueUid::getUniqueAlphanumeric();
            $preferred_name = null;
            if (array_key_exists('preferred_name', $row)) {
                $preferred_name = $row['preferred_name'];
            }
            switch ($prefix) {
                case 'create':
                    return  Security_user::create([
                        'username' => str_replace('-', '', $openemisNo),
                        'openemis_no' => $openemisNo,
                        'first_name' => $row['full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                        'last_name' => genNameWithInitials($row['full_name']),
                        'preferred_name' => $preferred_name,
                        'gender_id' => $row['gender_mf'],
                        'date_of_birth' => $date,
                        'address' => $row['address'],
                        'birthplace_area_id' => $BirthArea,
                        'nationality_id' => $nationalityId,
                        'identity_type_id' => $identityType,
                        'identity_number' => $row['identity_number'],
                        'is_student' => 1,
                        'created_user_id' => $file['security_user_id']
                    ]);
                case 'update':
                    if (!is_null($row['student_id'])) {
                        $studentInfo =  Security_user::where('openemis_no',trim($row['student_id']))->first();
                        self::query()->where('openemis_no', $row['student_id'])
                            ->update([
                                'first_name' => $row['full_name'] ? $row['full_name'] : $studentInfo['first_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                                'last_name' => $row['full_name'] ? genNameWithInitials($row['full_name']) : genNameWithInitials($studentInfo['first_name']),
                                'preferred_name' => $preferred_name,
                                'gender_id' => is_numeric($row['gender_mf']) ? $row['gender_mf'] : $studentInfo['gender_id'],
                                'date_of_birth' => $date ? $date : $studentInfo['date_of_birth'],
                                'address' => $row['address'] ? $row['address'] : $studentInfo['address'],
                                'birthplace_area_id' => $row['birth_registrar_office_as_in_birth_certificate'] ? $BirthArea : $studentInfo['birthplace_area_id'],
                                'nationality_id' => $row['nationality'] ? $nationalityId : $studentInfo['nationality_id'],
                                'identity_type_id' => $row['identity_type'] ? $identityType : $studentInfo['identity_type_id'],
                                'identity_number' => $row['identity_number'] ? $row['identity_number'] : $studentInfo['identity_number'],
                                'is_student' => 1,
                                'modified' => now(),
                                'modified_user_id' => $file['security_user_id']
                            ]);
                            return $studentInfo;
                    }
                    break;
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public static function createOrUpdateGuardianProfile($row, $prefix, $file)
    {
        try {
            $uniqueUid = new UniqueUid();
            $AddressArea = Area_administrative::where('name', 'like', '%' . $row[$prefix . 's_address_area'] . '%')->first();
            $nationalityId = Nationality::where('name', 'like', '%' . $row[$prefix . 's_nationality'] . '%')->first();
            $identityType = Identity_type::where('national_code', 'like', '%' . $row[$prefix . 's_identity_type'] . '%')->first();
            $openemisNo = $uniqueUid::getUniqueAlphanumeric();

            $identityType = ($identityType !== null) ? $identityType->id : null;
            $nationalityId = $nationalityId !== null ? $nationalityId->id : null;

            $guardian = null;
            if (!empty($row[$prefix . 's_identity_number'])) {
                $guardian = Security_user::where('identity_type_id', '=', $nationalityId)
                    ->where('identity_number', '=', $row[$prefix . 's_identity_number'])->first();
            }

            if (is_null($guardian)) {
                $guardian = self::create([
                    'username' => str_replace('-', '', $openemisNo),
                    'openemis_no' => $openemisNo,
                    'first_name' => $row[$prefix . 's_full_name'], // here we save full name in the column of first name. re reduce breaks of the system.
                    'last_name' => genNameWithInitials($row[$prefix . 's_full_name']),
                    'gender_id' => 1,
                    'date_of_birth' => $row[$prefix . 's_date_of_birth_yyyy_mm_dd'],
                    'address' => $row[$prefix . 's_address'],
                    'address_area_id' => $AddressArea ? $AddressArea->id : null,
                    'nationality_id' => $nationalityId,
                    'identity_type_id' => $identityType,
                    'identity_number' => $row[$prefix . 's_identity_number'],
                    'is_guardian' => 1,
                    'created_user_id' => $file['security_user_id']
                ]);


                $guardian['guardian_relation_id'] = 1;
                if (array_key_exists($prefix . 's_phone', $row)) {
                    $father['contact'] = $row[$prefix . 's_phone'];
                    User_contact::createOrUpdate($father, $file['security_user_id']);
                }
            } else {
                Security_user::where('id', $guardian->id)->update(['address_area_id' => $AddressArea ? $AddressArea->id : null,]);
            }
            return $guardian;
        } catch (\Exception $e) {
            Log::error($e->getMessage(), [$e]);
        }
    }
}
