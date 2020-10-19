<?php

namespace App\Console\Commands;

use App\Models\Institution;
use Illuminate\Console\Command;
use App\Models\Institution_class;
use Illuminate\Support\Facades\DB;
use App\Models\Institution_student;
use App\Models\Institution_class_student;
use App\Models\Institution_student_admission;
use Carbon\Carbon;

class UpdateClassEntriyWithZeroId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:zero_id_class';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update student class reference';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $students = Institution_student::withTrashed()->where('updated_from','doe')
        ->get()->toArray();
        if(count($students)>0){
            processParallel(array($this,'process'),$students,15);
        }else{
            echo "all are updated \r\n";
        }
    }

    public function process($student){
        $institution_class = Institution_class::select('id')->where('institution_id',$student['institution_id'])->get->toArray();
        $wrongStudentsClass = Institution_class_student::whereNotIn('institution_class_id',$institution_class)
        ->where('student_id',$student['student_id'])
        ->get()->toArray();
        
        if(count($wrongStudentsClass)>0){
            
            Institution_class_student::where('student_id',$student['student_id'])->delete();
            Institution_student_admission::where('student_id',$student['student_id'])->delete();
            
            echo "deleted wrong class reference:" .$student['student_id']; 

            $institutionClass =  Institution_class::getGradeClasses($student['education_grade_id'],$student['institution_id']);

            if(count($institutionClass) == 1){
                $start_date = new Carbon($student['start_date']);
                $end_date = new Carbon($student['end_date']);
                Institution_student_admission::create(
                    [
                        'student_id'=>$student['student_id'],
                        'institution_class_id'=>  $institutionClass[0]['id'],
                        'start_date' => $student['start_date'],
                        'start_year' => $start_date->format('Y'),
                        'end_date' => $student['start_date'],
                        'end_year' => $end_date->format('Y'),
                        'education_grade_id' => $student['education_grade_id'],
                        'institution_id' => $student['institution_id'],
                        'status_id' => 124,
                        'academic_period_id' => $student['academic_period_id'],
                        'created_user_id' => $student['created_user_id']
                    ]
                );
                Institution_class_student::create([
                    'student_id'=>$student['student_id'],
                    'institution_class_id'=>  $institutionClass[0]['id'],
                    'education_grade_id' => $student['education_grade_id'],
                    'institution_id' => $student['institution_id'],
                    'status_id' => 124,
                    'academic_period_id' => $student['academic_period_id'],
                    'student_status_id' => 1,
                    'created_user_id' => $student['created_user_id']
                    ]);
                echo "updated:" .$student['student_id']; 
            }
        }else{
            
        }
    }
}
