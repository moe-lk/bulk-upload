<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use Illuminate\Console\Command;
use App\Models\Institution_class;
use App\Models\Institution_shift;
use App\Models\Institution_class_subject;

class cleanClone extends Command
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
        $this->institution_classes = new Institution_class();
        $this->institution_class_subjects = new Institution_class_subject();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->end_time = microtime(TRUE);

        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
    
        $this->start_time = microtime(TRUE);
        $year = $this->argument('year');
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        if($year <= 2019) {
            $this->output->writeln('2019 or previous data not allowed to delete from this script');
            exit();
        }
        $shift = $this->shifts->getShiftsToDelete($year,$academicPeriod->id);
        array_walk($shift,array($this,'process'));

        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
 
       
    }

    public function process($shift){
       
    try {
        $institutionClasses = $this->institution_classes->select('id')->where('id',$shift['id']);
        $this->institution_classes->where('id',$shift['id'])->delete();
 
        $this->institution_class_subjects->whereIn('id',$institutionClasses)->delete();
 
        $this->shifts->where('id',$shift['id'])->delete();
 
        $this->output->writeln('Deleted '. $shift['id'] . ($this->end_time - $this->start_time) . ' seconds to complete');
 
    } catch (\Throwable $th) {
        dd($th);
    }
      
    }
}
