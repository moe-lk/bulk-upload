<?php

namespace App\Console\Commands;

use App\Http\Controllers\ExaminationStudentsController;
use Illuminate\Console\Command;

class ExaminationStudentSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:migration {year} {grade}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command to seed from  the examination data (csv) to sis deticated examination table';

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
       
        ExaminationStudentsController::callOnClick($this->argument('year'),$this->argument('grade'));
        $output->writeln('###########################################------Finished inserting file records------###########################################');
    }
}
