<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Examination_student;
use App\Models\Institution_class_student;
use App\Models\Institution_student;
use App\Models\Institution_student_admission;
use App\Models\Security_user;
use Illuminate\Support\Facades\Artisan;

class CleanExamData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:clean {chunk} {max}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean SIS data duplication after Exam import';

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
        $this->output->writeln('###########################################------Start cleanning exam records------###########################################');
        $students = DB::table('institution_students as is')
            ->join('security_users as su', 'su.id', 'is.student_id')
            ->where('is.updated_from', 'doe')
            ->orWhere('su.updated_from', 'doe')
            ->groupBy('is.student_id')
            ->orderBy('is.student_id')
            ->get()
            ->toArray();
        $this->output->writeln('Total students to clean: '.  count($students));
        $students = array_chunk($students, $this->argument('chunk'));
        $this->processParallel($students, $this->argument('max'));
        $this->output->writeln('###########################################------Finished cleaning exam records------###########################################');
    }


    public function processParallel(array $arr, $procs = 4)
    {
        // Break array up into $procs chunks.
        $chunks   = array_chunk($arr, ceil((count($arr) / $procs)));
        $pid      = -1;
        $children = array();
        foreach ($chunks as $items) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                die('could not fork');
            } else if ($pid === 0) {
                $this->output->writeln('started processes: ' . count($children));
                // We are the child process. Pass a chunk of items to process.
                array_walk($items, array($this, 'process'));
                exit(0);
            } else {
                // We are the parent.
                $children[] = $pid;
            }
        }
        // Wait for children to finish.
        foreach ($children as $pid) {
            // We are still the parent.
            pcntl_waitpid($pid, $status);
        }
    }

    public function process($students){
        array_walk($students,array($this,'cleanData'));
    }


    public function cleanData($Student)
    {
        $exist = Examination_student::where('nsid','=',  $Student->openemis_no)->count();

        if (!$exist) {
            Institution_student::where('student_id', $Student->student_id)->delete();
            Institution_class_student::where('student_id', $Student->student_id)->delete();
            Institution_student_admission::where('student_id', $Student->student_id)->delete();
            Security_user::where('id', $Student->student_id)->delete();
            $this->output->writeln($Student->openemis_no.': deleted from SIS:'.$Student->institution_id);
        }
    }
}
