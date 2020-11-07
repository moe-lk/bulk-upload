<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use Illuminate\Console\Command;
use App\Models\Institution_shift;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CloneController;

class CloneConfigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clone:config {year} {mode} {max}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone configuration data for new year';

    protected $start_time;
    protected  $end_time;

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
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $this->start_time = microtime(TRUE);
        $year = $this->argument('year');
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $mode = $this->argument('mode') == 'AL' ? true : false; 
        $previousAcademicPeriodYear = $academicPeriod->order;
        $previousAcademicPeriod = Academic_period::where('order',$previousAcademicPeriodYear+1)->first();
        $shift = $this->shifts->getShiftsToClone($previousAcademicPeriod->code,$this->argument(('max')),$mode);
        $params = [
            'year' => $year,
            'academic_period' => $academicPeriod,
            'previous_academic_period' => $previousAcademicPeriod,
            'mode' => $this->argument('mode')
        ];

        $function = array($this->clone, 'process');
        if(count($shift) > 0){
            // processParallel($function,$shift, $this->argument('max'),$params);
            array_walk($shift,$function,$params);
        }else{
            $this->output->writeln('Nothing to clone');
        }
        $this->end_time = microtime(TRUE);


        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
    }  
}
