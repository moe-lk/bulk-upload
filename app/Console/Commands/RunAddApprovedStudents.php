<?php

namespace App\Console\Commands;

use App\Models\Unprocessed_students;
use Illuminate\Console\Command;
use App\Models\Institution_class_student;
use App\Models\Institution_class_subject;
use App\Models\Institution_student_admission;
use App\Models\Institution_student;
use App\Models\Institution;
use Illuminate\Support\Facades\Log;
use Webpatser\Uuid\Uuid;


class RunAddApprovedStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admission:students {institution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provide the institution cencus id for process add mission students';

    public $count = 0;
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
        $institution = Institution::where([
            'id' => $this->argument('institution')
        ])->first();

        if(!is_null($institution)){
            try {
                $this->info('adding missing students to the admission ' . $institution->name);
                $allApprovedStudents = Institution_student_admission::where([
                    'status_id' => 124,
                    'institution_id' => $institution->id
                ])->get()->toArray();
                $allApprovedStudents = array_chunk($allApprovedStudents, 50);
                array_walk($allApprovedStudents, array($this, 'addStudents'));
            } catch (\Exception $e) {
                Log::error($e);
            }
        }
    }

    protected function addStudents($students){
        array_walk($students,array($this,'addStudent'));
    }

    protected function addStudent($student){
//        dd(Institution_class_student::isDuplicated($student));
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        sleep(1);
        if(!(Institution_class_student::isDuplicated($student) > 0)){
            $this->count += 1;
            $this->student = $student ;
            try{
               Institution_student::create([
                   'student_status_id' => 1,
                   'student_id' => $student['student_id'],
                   'education_grade_id' => $student['education_grade_id'],
                   'academic_period_id' => $student['academic_period_id'],
                   'start_date' => $student['start_date'],
                   'start_year' => \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $student['start_date'])->year , // $student['start_date']->format('Y'),
                   'end_date' => $student['end_date'],
                   'end_year' =>  \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $student['end_date'])->year , //$student['end_date']->format('Y'),
                   'institution_id' => $student['institution_id'],
                   'admission_id' => $student['admission_id'],
                   'created_user_id' => $student['created_user_id'],
               ]);

               if(!is_null($student['institution_class_id'])){
                   Institution_class_student::create([
                       'student_id' => $student['student_id'],
                       'institution_class_id' => $student['institution_class_id'],
                       'education_grade_id' =>  $student['education_grade_id'],
                       'academic_period_id' => $student['academic_period_id'],
                       'institution_id' =>$student['institution_id'],
                       'student_status_id' => 1,
                       'created_user_id' => $student['created_user_id'],
                   ]);
               }
                $output->writeln('
        ####################################################
           Total number of students updated : '.$this->count.'
        #                                                  #
        ####################################################' );
//        $output->writeln();
           }catch (\Exception $e){
//               echo $e->getMessage();
               $output->writeln( $e->getMessage());
           }
        }
    }


    protected  function  setSubjects($student){
        $allSubjects = Institution_class_subject::getMandetorySubjects($student['institution_class_id']);

        if (!empty($allSubjects)) {
            $allSubjects = unique_multidim_array($allSubjects, 'institution_subject_id');
            $this->student = $student;
            $allSubjects = array_map(array($this,'setStudentSubjects'),$allSubjects);
            $allSubjects = unique_multidim_array($allSubjects, 'education_subject_id');
            array_walk($allSubjects,array($this,'insertSubject'));
        }

        unset($allSubjects);

    }


    protected function setStudentSubjects($subject){
        return [
            'id' => (string) Uuid::generate(4),
            'student_id' => $this->student->student_id,
            'institution_class_id' => $this->student->institution_class_id,
            'institution_subject_id' => $subject['institution_subject_id'],
            'institution_id' => $this->student->institution_id,
            'academic_period_id' => $this->student->academic_period_id,
            'education_subject_id' => $subject['institution_subject']['education_subject_id'],
            'education_grade_id' => $this->student->education_grade_id,
            'student_status_id' => 1,
            'created_user_id' => $this->file['security_user_id'],
            'created' => now()
        ];
    }

    protected function insertSubject($subject){
        if(!Institution_subject_student::isDuplicated($subject)){
            Institution_subject_student::updateOrInsert($subject);
        }
    }

}
