<?php

namespace App\Console\Commands;

use App\Institution_grade;
use App\Models\Academic_period;
use App\Models\Education_grade;
use App\Models\Institution;
use App\Models\Institution_class;
use App\Models\Institution_class_student;
use App\Models\Institution_student;
use App\Models\Institution_student_admission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class PromoteStudents
 * @package App\Console\Commands
 */
class PromoteStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:students  {institution} {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote students';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->instituion_grade = new \App\Models\Institution_grade();
        $this->education_grades = new Education_grade();
        $this->academic_period = new Academic_period();
        $this->institution_students = new Institution_student();
        $this->institutions = new Institution();
        $this->institution_class_students = new Institution_class_student();
        $this->institution_classes = new Institution_class();
        $this->institution_student_admission = new Institution_student_admission();
    }



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $year = $this->argument('year');
        $institution = $this->argument('institution');
        $institutionGrade = $this->instituion_grade->getInstitutionGradeToPromoted($year,$institution);
//        dd($institutionGrade);
        array_walk($institutionGrade,array($this,'processGrades'),$year);
    }

    public function processGrades($institutionGrade,$count,$year){
        if(!empty($institutionGrade) && $this->institutions->isActive($institutionGrade['institution_id'])) {
            $this->instituion_grade->updatePromoted($year,$institutionGrade['id']);
            $isAvailableforPromotion = 0;
            $nextGrade = $this->education_grades->getNextGrade($institutionGrade['education_grade_id']);

            if (!empty($nextGrade)) {
                $isAvailableforPromotion = $this->instituion_grade->getInstitutionGrade($institutionGrade['institution_id'], $nextGrade->id);
            }

            if (!empty($isAvailableforPromotion)) {
                $this->process($institutionGrade,$nextGrade,$year,1);
            }else{
                $this->process($institutionGrade,$nextGrade,$year,3);
            }
        }
    }


    /**
     * Promote students of grate to next grade
     *
     * @param $institutionGrade
     * @param $nextGrade
     * @param $academicPeriod
     * @param $nextAcademicPeriod
     * @param array $parallelClasses
     * @param $status
     */
        public function promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,$parallelClasses = [],$status){
            $institution = Institution::where( 'id',$institutionGrade['institution_id'])->get()->first();
            $studentListToPromote = $this->institution_students->getStudentListToPromote($institutionGrade,$academicPeriod);

            $params = [
                $nextAcademicPeriod,
                $institutionGrade,
                $nextGrade,
                $status
            ];

            try{
                array_walk($studentListToPromote,array($this,'promote'),$params);

                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln('##########################################################################################################################');
                $output->writeln('Promoting from '. $institutionGrade['name'] .' IN '.$institution->name.' No of Students: '. count($studentListToPromote));

                if(!empty($parallelClasses)){
                    $params = [
                        $nextAcademicPeriod,
                        $institutionGrade,
                        $nextGrade,
                        $parallelClasses,
                        $status
                    ];
                    array_walk($studentListToPromote,array($this,'assingeToClasses'),$params);

                }
            }catch (\Exception $e){
                Log::error($e->getMessage());
            }
        }

    /**
     * Process institution grade in to the define promotion senarios
     *
     * @param $institutionGrade
     * @param $nextGrade
     * @param $year
     * @return int
     */
        public function process($institutionGrade,$nextGrade,$year){
            $academicPeriod = Academic_period::query()->where('code',$year -1)->get()->first();
            $nextAcademicPeriod = Academic_period::query()->where('code',$year)->get()->first();

                    if($nextGrade !== []  && !is_null($nextGrade) ){
                        $currentGradeObj = $this->instituion_grade->getParallelClasses($institutionGrade['id'],$institutionGrade['institution_id'],$nextGrade->id,$academicPeriod->id);
                        $nextGradeObj = $this->instituion_grade->getParallelClasses($institutionGrade['id'],$institutionGrade['institution_id'],$nextGrade->id,$nextAcademicPeriod->id);

                    }elseif (is_null($nextGrade)){
                        // default pool promotion
                        $this->promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,[],3);
                        return 3;
                    }


            if($nextGradeObj){
                switch ($nextGradeObj){
                    case $nextGradeObj->count() == 1:
                        // promote parallel classes
                        $this->promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,$nextGradeObj->toArray(),1);
                        return 1;
                        break;
                    case(($nextGradeObj->count() > 1) && ($nextGradeObj->count() !==  $currentGradeObj->count()));
                        // promote pool promotion
                        $this->promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,[],2);
                        return 2;
                        break;

                    case(($nextGradeObj->count() > 1) && $currentGradeObj->count() == $nextGradeObj->count());
                        // Promote matching class name with previous class
                        $this->promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,$nextGradeObj->toArray(),1);
                        return 1;
                        break;

                    default:
                        // default pool promotion
                        $this->promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,[],2);
                        return 3;
                        break;

                }
            }



        }


    /**
     * update promoted student's data in to the DB
     *
     * @param $student
     * @param $count
     * @param $params
     */
        public function promote($student,$count,$params){

            $academicPeriod = $params[0];
            $nextGrade = $params[2];
            $status = $params[3];
            $studentData = [
                'student_status_id' => $status,
                'education_grade_id' => $nextGrade !== null ? $nextGrade->id : $student['education_grade_id'],
                'academic_period_id' => $academicPeriod->id,
                'start_date' => $academicPeriod->start_date,
                'start_year' =>$academicPeriod->start_year ,
                'end_date' => $academicPeriod->end_date,
                'end_year' =>   $academicPeriod->end_year ,
                'institution_id' => $student['institution_id'],
                'admission_id' => $student['admission_id'],
                'created_user_id' => $student['created_user_id']
            ];
            try{
               Institution_student::where('id',(string)$student['id'])->update($studentData);
            }catch (\Exception $e){
                Log::error($e->getMessage());
            }
    }


    /**
     * get promoted new class of the students
     *
     * @param $student
     * @param $educationGrade
     * @param $nextGrade
     * @param $classes
     * @return false|int|string|null
     */
    public function getStudentClass($student,$educationGrade,$nextGrade,$classes){
        $studentClass = $this->institution_class_students->getStudentNewClass($student);
        if(!is_null($studentClass)){
            return  array_search(str_replace($educationGrade->name,$nextGrade->name,$studentClass->name),array_column($classes,'name'));
        }else{
            return null;
        }

    }

    /**
     * Create class entry for promoted students
     *
     * @param $student
     * @param $count
     * @param $params
     */
    public function assingeToClasses($student,$count,$params){
        $academicPeriod = $params[0];
        $educationGrade = $params[1];
        $nextGrade = $params[2];
        $classes = $params[3];
        $status = $params[4];

        $class = $this->getStudentClass($student,$educationGrade,$nextGrade,$classes);
        $class = $classes[$class];

        if($count($classes) == 1){
            $class = $classes[0];
        }

        if(!is_null($class)){

            $studentObj = [
                'student_id' => $student['student_id'],
                'institution_class_id' =>  $class['id'],
                'education_grade_id' =>  $nextGrade->id,
                'academic_period_id' => $academicPeriod->id,
                'institution_id' =>$student['institution_id'],
                'student_status_id' => $status,
                'created_user_id' => $student['created_user_id']
            ];
            if(!$this->institution_class_students->isDuplicated($studentObj)){
                $this->institution_class_students->create($studentObj);
//                $this->institution_classes->where('id',$class['id'])->update([
//
//                ]);
            }
        }
    }
}
