<?php

namespace App\Console\Commands;

use App\Models\Institution;
use Illuminate\Console\Command;
use App\Models\Institution_class;
use Illuminate\Support\Facades\DB;
use App\Models\Institution_student;
use App\Models\Institution_class_student;
use App\Models\Institution_student_admission;
use App\Models\Security_user;
use Carbon\Carbon;

class UpdateClassEntriyWithZeroId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:zero_id_class {from} {max} {code}';

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
        $this->class = new Institution_class;
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
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

        if ($this->argument('code') !== 'All') {
            $institutions = Institution::where('code', $this->argument('code'))->get()->toArray();
            processParallel(array($this,'processInstitution'),$institutions,$this->argument('max'));
        } else {
            $institutions = Institution::where('institution_status_id', 1)->get()->toArray();
            processParallel(array($this,'processInstitution'),$institutions,$this->argument('max'));
        }
    }

    public function processInstitution($institution)
    {
        $students = Institution_student::withTrashed()->where('updated_from', $this->argument('from'))
            ->join('institutions', 'institutions.id', 'institution_students.institution_id')
            ->where('institutions.id', $institution['id'])
            ->get()->toArray();
        if (count($students) > 0) {
            array_walk($students,array($this, 'process'));
            $this->output->writeln("institution :" .$institution['code']. ' cleaned');
        } else {
            $this->output->writeln("all records are cleaned at  :".$institution['code'] );
        }
    }

    public function process($student)
    {
       try{
        $wrongStudentsClass = Institution_class_student::where('institution_id', $student['institution_id'])
            ->whereRaw('institution_class_id not in (select id from institution_classes where institution_id ='.$student['institution_id'].' )')
            ->orWhere('institution_class_id', 0)
            ->where('student_id', $student['student_id'])
            ->get()->toArray();

        if (count($wrongStudentsClass) > 0) {
            Institution_class_student::where('student_id', $student['student_id'])->forceDelete();
            Institution_student_admission::where('student_id', $student['student_id'])->forceDelete();
            Institution_student::where('student_id', $student['student_id'])->forceDelete();

            array_walk($wrongStudentsClass, array($this->class, 'updateClassCount'));

            $institutionClass =  Institution_class::getGradeClasses($student['education_grade_id'], $student['institution_id']);

            if (count($institutionClass) == 1) {
                $start_date = new Carbon($student['start_date']);
                $end_date = new Carbon($student['end_date']);
                Institution_student_admission::create(
                    [
                        'student_id' => $student['student_id'],
                        'institution_class_id' =>  $institutionClass[0]['id'],
                        'start_date' => $student['start_date'],
                        'start_year' => $start_date->format('Y'),
                        'end_date' => $student['start_date'],
                        'end_year' => $end_date->format('Y'),
                        'education_grade_id' => $student['education_grade_id'],
                        'institution_id' => $student['institution_id'],
                        'status_id' => 124,
                        'academic_period_id' => $student['academic_period_id'],
                        'created_user_id' => $student['created_user_id'],
                        'updated_from' => $student['updated_from']
                    ]
                );
                $institutionClassStudent = [
                    'student_id' => $student['student_id'],
                    'institution_class_id' =>  $institutionClass[0]['id'],
                    'education_grade_id' => $student['education_grade_id'],
                    'institution_id' => $student['institution_id'],
                    'status_id' => 124,
                    'academic_period_id' => $student['academic_period_id'],
                    'student_status_id' => 1,
                    'created_user_id' => $student['created_user_id'],
                    'updated_from' => $student['updated_from']
                ];
                Institution_class_student::create($institutionClassStudent);
                Institution_student::create([
                    'student_id' => $student['student_id'],
                    'student_status_id' => 1,
                    'education_grade_id' => $student['education_grade_id'],
                    'institution_id' => $student['institution_id'],
                    'academic_period_id' => $student['academic_period_id'],
                    'created_user_id' => $student['created_user_id'],
                    'start_date' => $student['start_date'],
                    'start_year' => $start_date->format('Y'),
                    'end_date' => $student['start_date'],
                    'end_year' => $end_date->format('Y'),
                    'taking_g5_exam' => $student['updated_from'] == 'doe' ? true : false,
                    'income_at_g5' =>  $student['income_at_g5'],
                    'updated_from' => $student['updated_from'],
                    'exam_center_for_special_education_g5' =>  $student['exam_center_for_special_education_g5'],
                    'modified_user_id' =>  $student['modified_user_id'],
                ]);
                $institutionClassStudent = [$institutionClassStudent];
                array_walk($institutionClassStudent, array($this->class, 'updateClassCount'));
                $this->output->writeln("student record  :".$student['student_id'] );
            }
        }
       }catch(\Exception $e){
            dd($e);
       }
    }
}
