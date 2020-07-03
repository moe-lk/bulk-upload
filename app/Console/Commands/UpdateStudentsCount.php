<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\Academic_period;
use App\Models\Education_grade;
use Illuminate\Console\Command;
use App\Models\Institution_class;
use App\Models\Institution_student;
use App\Models\Institution_class_student;
use App\Models\Institution_student_admission;

class UpdateStudentsCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:updateCount';

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
        $classes = Institution_class::get()->toArray();
        $this->output->writeln('start updating:'. count($classes));
        $this->output->writeln('#########################################');
        array_walk($classes , array($this,'updateCount'));
        $this->output->writeln('start finish:'. count($classes));
        $this->output->writeln('#########################################');
    }

    public function updateCount($class){
        $this->output->writeln('updating:'. $class['id']);
        $totalStudents =  Institution_class_student::getStudentsCount($class['id']);
        Institution_class::where('id', '=', $class['id'])
        ->update([
            'total_male_students' => $totalStudents['total_male_students'],
            'total_female_students' => $totalStudents['total_female_students']
        ]);
    }
}
