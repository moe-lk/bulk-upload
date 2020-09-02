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
    protected $signature = 'examination:removedDuplicated {limit}';

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
        $count = DB::table('examination_students')->select('nsid')->distinct()->count();
        $studentsIdsWithDuplication =   DB::table('examination_students as es')
        ->select(DB::raw('count(*) as total'),'es.*')
        ->whereNotNull('es.nsid')
        ->orWhereNot('es.nsid','<>','')
        ->having('total','>',1)
        ->groupBy('es.nsid')
        ->orderBy('es.nsid')
        ->chunk($this->argument('limit'),function($Students){
            foreach ($Students as $Student) {
                Examination_student::where('nsid',$Student->nsid)->update(['nsid'=>'']);
            }
        }); 
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
