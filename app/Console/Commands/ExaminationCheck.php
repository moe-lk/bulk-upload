<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Examination_student;

class ExaminationCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:removedDuplicated';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check duplications';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
        $studentsIdsWithDuplication =   DB::table('examination_students as es')
        ->select(DB::raw('count(*) as total'),'es.*')
        ->whereNotNull('es.nsid')
        ->orWhereNotNull('es.nsid','!=','')
        ->having('total','>',1)
        ->groupBy('es.nsid')
        ->orderBy('es.nsid')
        ->chunk(10000,function($Students){
            foreach ($Students as $Student) {
                $this->output->writeln($Student->nsid,'Deleted 100 starting with' .$Students->st_no);
                // Examination_student::where('st_no',$Student->st_no)->update(['nsid'=>'']);
            }
            $this->end_time = microtime(TRUE);    
            $this->output->writeln('Deleted 100 starting with' .$Students[0]->st_no);
            $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        });
        // $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
        // $this->start_time = microtime(TRUE);
        // $students = Examination_student::whereNotNull('nsid')
        //     ->orWhere('nsid', '!=', '')
        //     ->get()->toArray();
        // $students = array_chunk($students, 10000);
        // $this->output->writeln(count($students) . 'entries found');
        // array_walk($students, array($this, 'process'));
        // $this->output->writeln('All are cleaned');
        // $count = DB::table('examination_students')->count(DB::raw('DISTINCT nsid'));
        // $this->output->writeln($count .' unique NSIs');
        // $this->end_time = microtime(TRUE);


        // $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
        // $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        // $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');

    }

    public function process($array)
    {
        array_walk($array, array($this, 'deleteDuplication'));
        $this->end_time = microtime(TRUE);
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        $this->output->writeln(count($array).'entries cleaned');
    }

    public function deleteDuplication($students)
    {
        $count =  Examination_student::where('nsid', $students['nsid'])->count();
        if ($count > 1) {
            $count = Examination_student::where('nsid', $students['nsid'])->update(['nsid' => '']);
            $this->output->writeln($students['nsid'] .'same ID' . $count . ' records removed');
        }
    }
}
