<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Examination_student extends Model
{


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'examination_students';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['st_no', 'stu_no', 'nsid', 'school_id', 'full_name', 'dob', 'gender', 'address', 'annual_income', 'has_special_need', 'disable_type', 'disable_details', 'special_education_centre'];

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
    protected $casts = ['has_special_need' => 'boolean'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['dob'];

    protected $primaryKey = 'st_no';

    public $timestamps = true;

    public static function insertData($data)
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $value = self::where('st_no', $data['st_no'])->get();
        if (count($value) > 0) {
            self::where('st_no', $data['st_no'])->update($data);
        } else {
            self::insert($data);
        }
        $output->writeln('Student name: ' . ($data['f_name']) . ' has been inserted to the database');
    }
}
