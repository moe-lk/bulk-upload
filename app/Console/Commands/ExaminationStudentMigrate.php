<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ExaminationStudentsController;

class ExaminationStudentMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:migrate {year} {grade}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command desinged to map and produce new students id for examination';

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
        $this->examinationController = new ExaminationStudentsController($this->argument('year'),$this->argument('grade'));
        $this->examinationController->doMatch();
        $output->writeln('###########################################------Finished inserting file records------###########################################');
    }

    //TODO implement the main algorythem

    //TODO implement the seed fucntions
}
