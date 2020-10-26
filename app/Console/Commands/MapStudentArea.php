<?php

namespace App\Console\Commands;

use App\Models\Institution_student;
use App\Models\Security_user;
use App\Models\Student_guardian;
use Illuminate\Console\Command;

class MapStudentArea extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:student_area';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->output =  new \Symfony\Component\Console\Output\ConsoleOutput();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $students = Security_user::where('is_student',true)->get()->toArray();
        array_walk($students,array($this,'process'));
    }

    public function process($student){
        $student['student_id'] = $student['id'];
        Institution_student::updateStudentArea($student);
        $this->output->writeln('area updated for student:'. $student['openemis_no']) ;
    }
}
