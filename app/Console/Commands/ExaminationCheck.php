<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Examination_student;
use App\Http\Controllers\ExaminationStudentsController;

class ExaminationCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:removedDuplicated {year} {grade} {limit}';

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
        $this->examinationController = new ExaminationStudentsController($this->argument('year'), $this->argument('grade'));
        $studentsIdsWithDuplication =   DB::table('examination_students as es')
        ->select(DB::raw('count(*) as total'),'es.*')
        ->whereNotNull('es.nsid')
        ->having('total','>',1)
        ->groupBy('es.nsid')
        ->orderBy('es.nsid')
        ->chunk($this->argument('limit'),function($Students){
            foreach ($Students as $Student) {
                $count = Examination_student::where('nsid',$Student->nsid)->count();
                if($count> 1){
                    Examination_student::where('nsid',$Student->nsid)->update(['nsid'=>'']);
                    $students = (array) json_decode(json_encode($Students), true);
                    array_walk($students, array($this->examinationController, 'clone'));
                }
                $this->output->writeln($Student->nsid .'same ID' . $count . ' records removed');
            }
        });  
    }
}
