<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use Illuminate\Console\Command;
use App\Models\Institution_shift;
use App\Http\Controllers\CloneController;

class cleanConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clone:clean {year}';

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
        $this->shifts = new Institution_shift();
        $this->academic_period = new Academic_period();
        $this->clone = new CloneController();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start_time = microtime(TRUE);
        $year = $this->argument('year');
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);

        $params = [
            'academic_period' => $academicPeriod
        ];

        if($year <= 2019){
            die('Academic Year 2019 or earlier can`t be deleted');
        }else{
            $this->clone->cleanConfig($params);
        }
    }
}
