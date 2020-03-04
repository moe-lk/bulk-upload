<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use App\Models\Institution;
use App\Models\Institution_class;
use App\Models\Institution_class_grade;
use App\Models\Institution_class_subject;
use App\Models\Institution_shift;
use App\Models\Institution_subject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloneConfigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clone:config {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone configuration data for new year';

    protected $start_time;
    protected  $end_time;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()

    {
        parent::__construct();
        $this->shifts = new Institution_shift();
        $this->academic_period = new Academic_period();
        $this->institution_classes = new Institution_class();
        $this->institution_class_subjects = new Institution_class_subject();
        $this->institution_subjects =  new Institution_subject();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start_time = microtime(TRUE);
        $year = $this->argument('year');
        $shift = $this->shifts->getShiftsToClone($year - 1);
        $previousAcademicPeriod = $this->academic_period->getAcademicPeriod($year - 1);
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);

        $params = [
            'year' => $year,
            'academic_period' => $academicPeriod,
            'previous_academic_period' => $previousAcademicPeriod
        ];
        array_walk($shift,array($this,'process'),$params);
        $this->end_time = microtime(TRUE);


       $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
       $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
       $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');

    }

    public function array_walk($shift,$count,$params){
        array_walk($shift,array($this,'process'),$params);
    }

    public function process($shift,$count,$params){
        $year = $params['year'];
        $academicPeriod = $params['academic_period'];
        $previousAcademicPeriod = $params['previous_academic_period'];
//        DB::beginTransaction();
        try{
            $shiftId = $this->updateShifts($year, $shift);
            $institutionClasses = $this->institution_classes->getShiftClasses($shift['id']);
            $institutionSubjects = $this->institution_subjects->getInstitutionSubjects($shift['institution_id'],$previousAcademicPeriod);
            $classIds = array_value_recursive('id',$institutionClasses);


            if (!empty($institutionClasses) && !is_null($shiftId) && !is_null($academicPeriod) ) {


                $newInstitutionClasses = $this->generateNewClass($institutionClasses,$shiftId,$academicPeriod->id);



                $params = [
                    'previous_academic_period_id' => $previousAcademicPeriod->id,
                    'academic_period_id' => $academicPeriod->id,
                    'shift_id' =>$shiftId
                ];


                try{
                    //TODO rewrite to a funciton insert institution class
                    array_walk($newInstitutionClasses,array($this,'insertInstitutionClasses'),$params);
                }catch (\Exception $e){
                    dd($e);
                }

                $newInstitutionClasses = $this->institution_classes->getShiftClasses($shiftId);
                $params['institution_classes'] = $newInstitutionClasses;

                array_walk($institutionClasses,array($this,'setNextClass'),$params);
                array_walk($institutionSubjects , array($this,'insertInstitutionSubjects'),$academicPeriod);


               $this->output->writeln('##########################################################################################################################');
               $this->output->writeln('updating from '. $shiftId);
            }
//            DB::commit();
        }catch (\Exception $e){
//            DB::rollBack();
            dd($e);
        }
    }


    /**
     * @param $subjects
     * @param $count
     * @param $academicPeriod
     */
    public function insertInstitutionSubjects($subjects, $count,$academicPeriod){
        $subjects['academic_period_id'] = $academicPeriod;
        $subjects['created'] = now();
        unset($subjects['total_male_students']);
        unset($subjects['total_female_students']);
        unset($subjects['id']);
        Institution_subject::create($subjects);
    }


    public function  insertInstitutionClasses($class,$count,$param){

        $academicPeriod = $param['academic_period_id'];
        $classSubjects = Institution_class_subject::query()->where('institution_class_id',$class['id'])->get()->toArray();

        //TODO implement insert class subjects function
        $classId = $class['id'];
        unset($class['id']);
        $institutionSubjects = Institution_subject::query()->where('education_grade_id',$class['education_grade_id'])
            ->where('academic_period_id',$academicPeriod)->get()->toArray();
        $params = [
            'class'=>$class,
            'subjects'=>$institutionSubjects,
            'academic_period_id'=> $academicPeriod,
            'classId' => $classId
        ];
        unset($class['education_grade_id']);
        $noOfStudents = $class['no_of_students'] == 0 ? 40 : $class['no_of_students'];
        $class['academic_period_id'] = $academicPeriod;
        $class['no_of_students'] = $noOfStudents;
        $class['created'] = now();
        $class['institution_shift_id'] = $param['shift_id'];
        $this->output->writeln('Create class:'. $class['name']);
        $class = Institution_class::create($class);
        $params['class'] = $class;
        array_walk($classSubjects,array($this,'insertInstitutionClassSubjects'),$params);
    }

    public function insertInstitutionClassSubjects($classSubject,$count,$params){

        $subject = array_search($params['classId'] ,array_column($params['subjects'],'institution_class_id'));
        if($subject){
            unset($classSubject['id']);

            $class = $params['class'];
            $subjects = Institution_subject::query()->where('institution_id',$class['institution_id'])
                ->where('education_grade_id',$class['education_grade_id'])
                ->where('academic_period_id',$params['academic_period_id'])->get()->toArray();
            if($subjects !=[]){
                dd($subjects);
            };
            $classSubject['institution_class_id'] = $params['class'];
        }

    }

    public function setNextClass($currentClass,$count,$params){
        $institutionClasses = $params['institution_classes'];
//        dd($currentClass);
        $classGrade = Institution_class_grade::query()->where('institution_class_id',$currentClass['id'])->get()->toArray();
        $class = Institution_class::find($currentClass['id']);
        $class = array_search($currentClass['name'],array_column($institutionClasses,'name'));
        if(is_numeric($class)){
            $institutionClass = $institutionClasses[$class];
            $classes = $params['institution_classes'];
            $subjects = $params['class_subject'];
            $classId =  array_search($currentClass['name'],array_column($classes,'name'));
            array_walk($subjects,array($this,'createSubjects'),$classes[$classId]);
        }

    }

    public function createSubjects($subject,$count,$newClassId){
        $subject['institution_class_id'] = $newClassId['id'];
        $this->institution_class_subjects->create($subject);
    }

    /**
     * generate new class object for new academic year
     *
     * @param $classes
     * @param $shiftId
     * @param $academicPeriod
     * @return array
     */
    public function generateNewClass($classes,$shiftId,$academicPeriod){
        $newClasses = [];
        foreach ( $classes as $class) {
            $noOfStudents = $class['no_of_students'] == 0 ? 40 : $class['no_of_students'];
            $class['academic_period_id'] = $academicPeriod;
            $class['no_of_students'] = $noOfStudents;
            $class['created'] = now();
            $class['institution_shift_id'] = $shiftId;
            array_push($newClasses,$class);
        }
        return $newClasses;
    }

    /**
     * update shifts
     * @param $year
     * @param $shift
     * @return mixed
     */
    public function updateShifts($year,$shift){
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $this->shifts->where('id',$shift['id'])->update(['cloned' => '2020']);
        $shift['academic_period_id'] = $academicPeriod->id;
        $exist = $this->shifts->shiftExists($shift);
        return $this->shifts->create((array)$shift)->id;
    }
}
