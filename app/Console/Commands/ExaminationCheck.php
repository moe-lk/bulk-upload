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
       $students = DB::table('examination_students as es')
       ->select(DB::raw('count(*) as total'),'e2.*')
       ->join('examination_students as e2','es.nsid','e2.nsid')
       ->having('total','>',1)
       ->groupBy('e2.st_no')
       ->orderBy('e2.st_no')
        ->get();
        $this->output->writeln(count($students).'entries found');

        foreach($students as $student){
            Examination_student::where('st_no',$student->st_no)->update(['nsid'=>'']);
            $this->output->writeln($student->st_no.'removed');
        }
    }
}
