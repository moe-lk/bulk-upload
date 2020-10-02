<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Institution_student;

class RemoveDuplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:clean {chunk} {max}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is to clean students data';

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
            $duplicatedStudents = Institution_student::select(DB::raw('count(*) as total'),'student_id','id','academic_period_id','education_grade_id')
            ->groupBy('student_id')
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
            dd($th);
        }
    }
  
    public function process($Student){
        Institution_student::where('institution_students.id','>',$Student['id'])
        ->where('institution_students.student_id',$Student['student_id'])
        ->where('institution_students.academic_period_id',$Student['academic_period_id'])
        ->where('institution_students.education_grade_id',$Student['education_grade_id'])
        ->delete();
        $this->end_time = microtime(TRUE);    
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
    }
}
