<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class Institution_class_subject extends Base_Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_class_subjects';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['status', 'institution_class_id', 'institution_subject_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public function institutionMandatorySubject(){
        return $this->belongsTo('App\Models\Institution_subject','institution_subject_id','id')
        ->with('institutionGradeSubject')
        ->whereHas('institutionGradeSubject', function ($query) {
                $query->where('auto_allocation',1);
        });
    }

    public function institutionOptionalSubject(){
        return $this->belongsTo('App\Models\Institution_subject','institution_subject_id','id')
        ->with('institutionGradeSubject')
        ->whereHas('institutionGradeSubject', function ($query) {
                $query->where('auto_allocation',0);
        });

    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->id = (string) Uuid::generate(4);
        });
    }

    public function institutionSubject(){
        return $this->belongsTo('App\Models\Institution_subject','institution_subject_id','id')
        ->with('institutionGradeSubject');
    }


    public static function getMandatorySubjects($institutionClass){
        $institutionGrade = Institution_class_grade::where('institution_class_id', '=', $institutionClass)->first();
        $mandatorySubject = Institution_class_subject::with(['institutionSubject'])
            ->whereHas('institutionSubject', function ($query) use ($institutionGrade) {
                $query->whereHas('institutionGradeSubject',function($query){
                    $query->where('auto_allocation',1);
                })->where('education_grade_id', $institutionGrade->education_grade_id);
            })
            ->where('institution_class_id', '=', $institutionClass)
            ->get()->toArray();
        return $mandatorySubject;
    }

    public static function getAllSubjects($institutionClass){
        $allSubjects = Institution_class_subject::with(['institutionSubject'])
        ->whereHas('institutionSubject', function ($query) use ($institutionClass) {
            $query->whereHas('institutionGradeSubject')->where('education_grade_id', $institutionClass['education_grade_id']);
        })
        ->where('institution_class_id', '=', $institutionClass['id'])
        ->get()->toArray();
        return $allSubjects;
    }

    public static function getStudentOptionalSubject($subjects, $student, $row, $institution) {
        $data = [];
        foreach ($subjects as $subject) {
            $subjectId = Institution_class_subject::with(['institutionSubject'])
                ->whereHas('institutionSubject', function ($query) use ($row, $subject, $student) {
                    $query->whereHas('institutionGradeSubject',function($query){
                        $query->where('auto_allocation',0);
                    })
                        ->where('name', '=', $row[$subject])
                        ->where('education_grade_id', '=', $student->education_grade_id);
                })
                ->where('institution_class_id', '=', $student->institution_class_id)
                ->get()->toArray();
            if (!empty($subjectId))
                $data[] = $subjectId[0];
        }
        return $data;
    }

    public function getInstitutionClassSubjects($academicPeriodId,$classIds){
        return self::query()
            ->whereIn('institution_class_id',$classIds)
            ->get()
            ->toArray();
    }

    public function isDuplicated($subject){
        return self::query()->where('institution_subject_id',$subject['institution_subject_id'])
            ->where('institution_class_id',$subject['institution_class_id'])->get()->toArray();
    }
}
