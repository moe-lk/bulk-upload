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
            // $this->start_time = microtime(TRUE);
            // $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
            // $this->output->writeln('############### Starting delete Duplication ################');
            // DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
            // $duplicatedStudents = Student_guardian::select(DB::raw('count(*) as total'),'id','student_id','guardian_id')
            // ->groupBy('student_id')
            // ->groupBy('guardian_id')
            // ->having('total','>',1)
            // ->orderBy('student_id')
            // ->get()
            // ->toArray();
            // if(count($duplicatedStudents)>0){
            //     processParallel(array($this,'process'),$duplicatedStudents,10);
            //     $this->end_time  = microtime(TRUE);
            //     $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
            // }else{
            //     $this->output->writeln('Nothing to Process, all are clean');
            // }
            Student_guardian::whereNotNull('deleted_at')->update(['deleted_at' => null]);
            $this->delete();
        } catch (\Throwable $th) {
        }
    }

    public function delete(){
       try{
            DB::table('student_guardians as t1')
            ->join('student_guardians as t2','t1.id','t2.id')
            ->where('t1.created','>','t2.created')
            ->where('t1.student_id','t2.student_id')
            ->where('t1..guardian_id','t2.guardian_id')
            ->delete();
       }catch(\Exception $e){
           dd($e);
       }
    }
}
