<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ExaminationStudentsController;
use App\Models\Examination_student;

class ExaminationsUpdateCensusNo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:updateCensus';

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
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln('###########################################------Inserting file records------###########################################');
        $this->examinationController = new ExaminationStudentsController(2019, 'G4');
        $students =  Examination_student::all()->toArray();
        $func = $this->examinationController;
        array_walk($students,array($func,'updateCensusNo'));

                
    }
}
