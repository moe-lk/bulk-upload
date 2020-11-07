<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education_grade extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'education_grades';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'admission_age', 'order', 'visible', 'education_stage_id', 'education_programme_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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

    public function getNextGrade($gradeId,$getNextProgrammeGrades = false, $firstGradeOnly = false){
        if (!empty($gradeId)) {
            $gradeObj = $this->find($gradeId);
            $programmeId = $gradeObj->education_programme_id;
            $order = $gradeObj->order;
            $gradeOptions = self::where( 'education_programme_id',$programmeId
            )->where('order',$order+1)->get()->first();
            $nextProgramme = self::getNextProgrammeList($programmeId);
            if(is_null($gradeOptions) && !is_null($nextProgramme)){
                $programmeId =  $nextProgramme->next_programme_id;
                $gradeOptions = self::where( 'education_programme_id',$programmeId
                )
                ->orderBy('order')
                ->get()->first();
            }
            // Default is to get the list of grades with the next programme grades
//            if ($getNextProgrammeGrades) {
//                if ($firstGradeOnly) {
//                    $nextProgrammesGradesOptions = $this->getNextProgrammeFirstGradeList($programmeId);
//                } else {
//                    $nextProgrammesGradesOptions = $this->getNextGradeList($programmeId);
//                }
//                $results =  array_merge($gradeOptions,$nextProgrammesGradesOptions);
//            } else {
//                $results = $gradeOptions;
//            }
            return $gradeOptions;
        } else {
            return null;
        }
    }

    public function getNextProgrammeFirstGradeList($id) {
        $nextProgrammeList = self::getNextProgrammeList($id);
        if (!empty($nextProgrammeList)) {
            $results = [];

            foreach ($nextProgrammeList as $nextProgrammeId) {
                $nextProgrammeGradeResults = self::
                    where('education_programme_id',$nextProgrammeId->next_programme_id)->get()->toArray();

                $results = $results + [key($nextProgrammeGradeResults) => current($nextProgrammeGradeResults)];
            }
        } else {
            $results = [];
        }

        return (object)$results;
    }


    /**
     * Function to get the list of the next education grade base on a given education programme id
     *
     * @param $id Education programme id
     * @return array List of next education grades id
     */
    public function getNextGradeList($id) {

        $nextProgrammeList = $this->getNextProgrammeList($id);
        if (!empty($nextProgrammeList)) {
            $results = self::whereIn('education_programme_id',$nextProgrammeList)
                ->get()->toArray();
        } else {
            $results = [];
        }

        return $results;
    }

    /**
     * Function to get the list of the next programme base on a given programme id
     *
     * @param $id Education programme id
     * @return array List of next education programmes id
     */
    public function getNextProgrammeList($id) {
        return Education_programmes_next_programme::where('education_programme_id',$id)
            ->get()->first();
    }


}
