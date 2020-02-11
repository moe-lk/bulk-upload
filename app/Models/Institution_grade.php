<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Institution_grade extends Model  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'institution_grades';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['education_grade_id', 'start_date', 'start_year', 'end_date', 'end_year', 'institution_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'promoted'];

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
    protected $dates = ['start_date', 'end_date', 'modified', 'created'];

    public function isPool($id, $institutionId)
    {
    }

    public function MatchNumberOfClasses(int $id, int $institutionId, int $currentGrade, int $nextGrade)
    {
    }

    public function GetNumberOfParallelClasses(int $id, int $institutionId)
    {
    }

    public function GetNextGrade(int $id, int $institutionId, int $currentGrade)
    {
    }

}
