<?php

namespace App\Console\Commands;

use App\Institution_grade;
use App\Models\Academic_period;
use App\Models\Education_grade;
use App\Models\Institution;
use App\Models\Institution_student;
use Illuminate\Console\Command;

class PromoteStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:students {year}';

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
    }



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $year = $this->argument('year');
        $institutionGrade = $this->instituion_grade->query()
            ->where('promoted','=',$year-1)
            ->orderBy('institution_id')->first();
        if(!empty($institutionGrade)) {

            $this->instituion_grade->updatePromoted($year,$institutionGrade->id,$institutionGrade->id);



            $isAvailableforPromotion = 0;
            $nextGrade = $this->education_grades->getNextGrade($institutionGrade->education_grade_id);



            if (!empty($nextGrade)) {
                $isAvailableforPromotion = $this->instituion_grade->getInstitutionGrade($institutionGrade->institution_id, $nextGrade[0]['id']);
            }

            if (!empty($isAvailableforPromotion)) {
                $this->process($institutionGrade,$nextGrade,$year);
            }


            }
        }

        public function promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod,$parallelClasses = []){
            $studentListToPromote = $this->institution_students->query()->where('institution_id', $institutionGrade->institution_id)
                ->where('education_grade_id', $institutionGrade->education_grade_id)
                ->where('academic_period_id', $academicPeriod->id)->get()->toArray();
            $params = [
                $nextAcademicPeriod,
                $nextGrade
            ];

            array_walk($studentListToPromote,array($this,'promote'),$params);
            if(!empty($parallelClasses)){
                $params = [
                    $nextAcademicPeriod,
                    $nextGrade,
                    $parallelClasses
                ];
                array_walk($studentListToPromote,array($this,'assingeToClasses'),$params);
            }


            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('##########################################################################################################################');
            $output->writeln('Promoting from '. $institutionGrade->name .' IN'.$institutionGrade->id.' No of Students: '. count($studentListToPromote));
        }

        public function process($institutionGrade,$nextGrade,$year){
            $academicPeriod = Academic_period::query()->where('code',$year -1)->get()->first();
            $nextAcademicPeriod = Academic_period::query()->where('code',$year)->get()->first();

            $nextGrade = $nextGrade[0];

            if($nextGrade !== []){
                $check = $this->instituion_grade->getParallelClasses($institutionGrade['id'],$institutionGrade->institution_id,$nextGrade['id'],$academicPeriod->id);

            }


            //TODO: check if the parallel class no is equals to one
            switch ($check->count()){
                case $check->count() == 1:
                    $this->promotion($institutionGrade,$nextGrade,$academicPeriod,$nextAcademicPeriod);

            }


            //TODO: check the parallel class numbers aren't equal

            //TODO: check if the parallel class numbers are equal and same name - super parallel

            //TODO: check if the parallel class numbers are equal and no same name - partial parallel

        }

        public function promote($student,$count,$params){
            $academicPeriod = $params[0];
            $nextGrade = $params[1][0];
            $studentData = [
                'student_status_id' => 1,
                'education_grade_id' => $nextGrade['id'],
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
            }
    }

    public function assingeToClasses($student,$count,$params){
        $academicPeriod = $params[0];
        $nextGrade = $params[1][0];
        $class = $params[2];
        Institution_class_student::create([
            'student_id' => $student['student_id'],
            'institution_class_id' =>  $class['id'],
            'education_grade_id' =>  $nextGrade['education_grade_id'],
            'academic_period_id' => $academicPeriod->id,
            'institution_id' =>$student['institution_id'],
            'student_status_id' => 1
        ]);
    }
}
