<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Institution_class_student;
use App\Models\Institution_class_subject;
use App\Models\Institution_student_admission;
use App\Models\Institution_student;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunAddStudentsToInstitutions extends Command
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
    protected $description = 'Add approved students data to indtitution_student table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->count = 0;
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
            DB::enableQueryLog();
            try {
                $this->info('adding missing students to the institution ' . $institution->name);
                $approvedstudent = Institution_student_admission::select('institution_student_admission.*')
                                    ->leftJoin('institution_students', 'institution_student_admission.student_id', '=', 'institution_students.student_id')
                                    ->whereIn('status_id',[121,122,123,124])
                                    ->where('institution_student_admission.institution_id',$institution->id)->get()->toArray();
    
                $approvedstudent = array_chunk($approvedstudent, 50);
                
                array_walk($approvedstudent, array($this, 'addStudents'));

            }catch (\Exception $e) {
                Log::error($e);
            }       
        }
    }

    protected function addStudents($approvedstudent){
        array_walk($approvedstudent,array($this,'addStudent')); 
    }

    protected function addStudent($approvedstudent){
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        Log::info($approvedstudent);
//         sleep(1);
        if((Institution_student::isDuplicated($approvedstudent) == 0)){
            dd($approvedstudent);
            $this->count += 1;
            $this->student = $approvedstudent ;
            try{
                $output->writeln($approvedstudent['student_id']."Updated");
                Institution_student::create([
                   'student_status_id' => 1,
                   'student_id' => $approvedstudent['student_id'],
                   'education_grade_id' => $approvedstudent['education_grade_id'],
                   'academic_period_id' => $approvedstudent['academic_period_id'],
                   'start_date' => $approvedstudent['start_date'],
                   'start_year' => \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $approvedstudent['start_date'])->year , // $approvedstudent['start_date']->format('Y'),
                   'end_date' => $approvedstudent['end_date'],
                   'end_year' =>  \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $approvedstudent['end_date'])->year , //$approvedstudent['end_date']->format('Y'),
                   'institution_id' => $approvedstudent['institution_id'],
                   'admission_id' => $approvedstudent['admission_id'],
                   'created_user_id' => $approvedstudent['created_user_id'],
               ]);
               if(!is_null($approvedstudent['institution_class_id'])){
                   Institution_class_student::create([
                       'student_id' => $approvedstudent['student_id'],
                       'institution_class_id' => $approvedstudent['institution_class_id'],
                       'education_grade_id' =>  $approvedstudent['education_grade_id'],
                       'academic_period_id' => $approvedstudent['academic_period_id'],
                       'institution_id' =>$approvedstudent['institution_id'],
                       'student_status_id' => 1,
                       'created_user_id' => $approvedstudent['created_user_id'],
                   ]);
               }
           }catch (\Exception $e){
              echo $e->getMessage();
           }
        }
        $output->writeln('
        ####################################################
           Total number of students updated : '.$this->count.'
        #                                                  #
        ####################################################' );
    }
}

