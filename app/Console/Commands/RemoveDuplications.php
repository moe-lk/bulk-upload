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
            $studentsIdsWithDuplication =   DB::table('institution_students as ins')
            ->select(DB::raw('count(*) as total'),'student_id','id','academic_period_id','education_grade_id')
            ->having('total','>',1)
            ->where('updated_from','sis')
            ->groupBy('ins.student_id')
            ->orderBy('ins.student_id')
            ->get();
            if(count($studentsIdsWithDuplication) > 0){
                $this->output->writeln('to clean:'. count($studentsIdsWithDuplication));
                $studentsIdsWithDuplication = (array)json_decode($studentsIdsWithDuplication,true);
                $studentsIdsWithDuplication = array_chunk($studentsIdsWithDuplication,$this->argument('chunk'));
                processParallel(array($this,'process'),$studentsIdsWithDuplication,$this->argument('max'));
                $this->end_time = microtime(TRUE);
                $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
                $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
                $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
            }else{
                $this->output->writeln('Nothing to clean');
            }
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function process($Students){
        array_walk($Students,array($this,'clean'));
        $this->end_time = microtime(TRUE);    
        $this->output->writeln('Deleted 100 starting with' .$Students[0]['id']);
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
    }


    public function clean($Student){
        Institution_student::where('institution_students.id','>',$Student['id'])
            ->where('institution_students.student_id',$Student['student_id'])
            ->where('institution_students.academic_period_id',$Student['academic_period_id'])
            ->where('institution_students.education_grade_id',$Student['education_grade_id'])
            ->delete();
    }
}
