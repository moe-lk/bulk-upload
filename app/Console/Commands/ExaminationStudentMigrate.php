<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;
use App\Http\Controllers\ExaminationStudentsController;

class ExaminationStudentMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:migrate {year} {grade} {offset} {limit} {mode}';

    /**
     * This will migrate set of examination student's from DoE to SIS.
     *
     * @var string
     */
    protected $description = 'This command designed to map and produce new students id for examination';

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
        $this->examinationController = new ExaminationStudentsController($this->argument('year'), $this->argument('grade'));
        if ($this->argument('mode') == 'export') {
            $output->writeln('###########################################------starting export------###########################################');
            $this->examinationController->export();
            $output->writeln('###########################################------Finished inserting file records------###########################################');
        } else {
            $this->examinationController->doMatch($this->argument('offset'), $this->argument('limit'), $this->argument(('mode')));
            $this->examinationController->export();
        }
        $output->writeln('###########################################------Finished inserting file records------###########################################');
    }
}
