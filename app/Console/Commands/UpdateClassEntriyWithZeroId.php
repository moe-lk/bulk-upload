<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution_class;
use Illuminate\Support\Facades\DB;
use App\Models\Institution_class_grade;
use App\Models\Institution_class_student;

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
        $students = Institution_class_student::where('institution_class_id',0)->get()->toArray();
        if(count($students)>0){
            processParallel(array($this,'process'),$students,15);
        }else{
            echo "all are updated \r\n";
        }
    }

    public function process($student){
        $institutionClass =  Institution_class::getGradeClasses($student['education_grade_id'],$student['institution_id']);
        
        if(count($institutionClass) == 1){
            Institution_class_student::where('student_id',$student['student_id'])
            ->update(['institution_class_id' => $institutionClass->id,'education_grade_id' => $student['education_grade_id']]);    
        echo "updated:" .$student['student_id']; 
        }else{
            Institution_class_student::where('student_id',$student['student_id'])->delete();
        }
    }
}
