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
        $institution = Institution::where('id',$institutionGrade->institution_id)->get()->first();
        $educationGrade = Education_grade::where('id',$institutionGrade->education_grade_id)->get()->first();
        $academicPeriod = $this->academic_period->query()->where('code',$year-1)->get()->first();
        $nextAcademicPeriod = $this->academic_period->query()->where('code',$year)->get()->first();
        if(!empty($institutionGrade)) {
            $this->instituion_grade->updatePromoted($year,$institutionGrade->id,$institutionGrade->id);

            $isAvailableforPromotion = 0;
            $nextGrade = $this->education_grades->getNextGrade($institutionGrade->education_grade_id);

            if (!empty($nextGrade)) {
                $isAvailableforPromotion = $this->instituion_grade->getInstitutionGrade($institutionGrade->institution_id, $nextGrade[0]['id']);
            }


            if (!empty($isAvailableforPromotion)) {
                $studentListToPromote = $this->institution_students->query()->where('institution_id', $institutionGrade->institution_id)
                    ->where('education_grade_id', $institutionGrade->education_grade_id)
                    ->where('academic_period_id', $academicPeriod->id)->get()->toArray();
                $params = [
                    $nextAcademicPeriod,
                    $nextGrade
                ];
                array_walk($studentListToPromote,array($this,'promote'),$params);

                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln('##########################################################################################################################');
                $output->writeln('Promoting from '. $educationGrade->name .' IN'.$institution->name.' No of Students: '. count($studentListToPromote));
            }
        }
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
}
