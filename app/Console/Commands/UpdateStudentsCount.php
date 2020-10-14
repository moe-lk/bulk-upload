<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\Academic_period;
use App\Models\Education_grade;
use Illuminate\Console\Command;
use App\Models\Institution_class;
use App\Models\Institution_student;
use App\Models\Institution_subject;
use App\Models\Institution_class_student;
use App\Models\Institution_subject_student;
use App\Models\Institution_student_admission;

class UpdateStudentsCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:updateCount {entity} {max}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the students count male/female';

      /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->output->writeln('#########################################');
        if($this->argument('entity') == 'class'){
            $classes = Institution_class::get()->toArray();
            $func = array($this,'updateCount');
            array_walk($subjects,array($this,'process'));
            $this->output->writeln('start updating:'. count($classes));
        }elseif($this->argument('entity') == 'subject'){
            $subjects = Institution_subject::get()->toArray(); 
            $subjects = array_chunk($subjects,10000);
            $this->output->writeln('start updating:'. count($subjects));
            array_walk($subjects,array($this,'process'));
            $this->output->writeln('#########################################');
        }
       
    }

    public function process($data){
        if($this->argument('entity') == 'class'){
            $func = array($this,'updateCount');
            processParallel($func,$data,$this->argument('max'));
            $this->output->writeln('start updating calss count:'. count($data));
        }elseif($this->argument('entity') == 'subject'){
            $this->output->writeln('start updating subject count:'. count($data));
            $func_subject = array($this,'updateSubjectCount');
            processParallel($func_subject,$data,$this->argument('max'));
        }
    }

    public function updateCount($class){
        $this->output->writeln('updating class:'. $class['id']);
        $totalStudents =  Institution_class_student::getStudentsCount($class['id']);
        Institution_class::where('id', '=', $class['id'])
        ->update([
            'total_male_students' => $totalStudents['total_male_students'],
            'total_female_students' => $totalStudents['total_female_students']
        ]);
    }

    public function updateSubjectCount($subject){
        $this->output->writeln('updating subject:'. $subject['id']);
        $totalStudents = Institution_subject_student::getStudentsCount($subject['id']);
        Institution_subject::where(['id' => $subject['id']])
            ->update([
                'total_male_students' => $totalStudents['total_male_students'],
                'total_female_students' => $totalStudents['total_female_students']
            ]);
    }
}
