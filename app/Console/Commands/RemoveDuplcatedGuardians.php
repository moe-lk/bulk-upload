<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student_guardian;
use Illuminate\Support\Facades\DB;

class RemoveDuplcatedGuardians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:guardian';

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
        try {
            $this->start_time = microtime(TRUE);
            $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $this->output->writeln('############### Starting delete Duplication ################');
            $duplicatedStudents = Student_guardian::select(DB::raw('count(*) as total'),'student_id','guardian_id')
            ->groupBy('student_id')
            ->groupBy('guardian_id')
            ->having('total','>',1)
            ->orderBy('student_id')
            ->get()
            ->toArray();
            if(count($duplicatedStudents)>0){
                processParallel(array($this,'process'),$duplicatedStudents,10);
            }else{
                $this->output->writeln('Nothing to Process, all are clean');
            }
        } catch (\Throwable $th) {
        }
    }

    public function process($Student){
        Student_guardian::where('student_guardians.id','>',$Student['id'])
        ->where('student_guardians.student_id',$Student['student_id'])
        ->where('student_guardians.guardian_id',$Student['guardian_id'])
        ->delete();
        $this->end_time = microtime(TRUE);    
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
    }
}
