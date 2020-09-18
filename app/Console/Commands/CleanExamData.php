<?php

namespace App\Console\Commands;

use Lsf\UniqueUid\UniqueUid;
use App\Models\Security_user;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Examination_student;
use App\Models\Institution_student;
use Illuminate\Support\Facades\Artisan;
use App\Models\Institution_class_student;
use App\Models\Institution_student_admission;

class CleanExamData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'examination:clean {chunk} {max} {type}';

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
        $type = $this->argument('type');
        $students = array();
        if($type == 'invalid'){
            $students = DB::table('examination_students as es')
            ->whereRaw('CHAR_LENGTH(nsid) > 11')
            ->get()
            ->toArray();
        }elseif($type == 'duplicate'){
            $students = DB::table('institution_students as is')
            ->join('security_users as su', 'su.id', 'is.student_id')
            ->where('is.updated_from', 'doe')
            ->orWhere('su.updated_from', 'doe')
            ->groupBy('is.student_id')
            ->orderBy('is.student_id')
            ->get()
            ->toArray();
            
        }elseif($type == 'all'){
            $students = DB::table('examination_students')
            ->where('nsid','<>','')
            ->whereNotNull('nsid')
            ->get()
            ->toArray();
        }

        $this->output->writeln('###########################################------Start cleanning exam records------###########################################');    
        if(count($students) > 0){
            $this->output->writeln('Total students to clean: '.  count($students));
            $students = array_chunk($students, $this->argument('chunk'));
            $function = array($this, 'process');
            processParallel($function,$students, $this->argument('max'),$type);
        }else{
            $this->output->writeln('nothing to process, all are cleaned');
        }   
        $this->output->writeln('###########################################------Finished cleaning exam records------###########################################');
    }

    public function process($students,$type){
       if($type == 'duplication'){
        array_walk($students,array($this,'cleanData'));
       }
    }


    public function cleanData($Student)
    {
        $exist = Examination_student::where('nsid','=',  $Student->openemis_no)->count();
        if (!$exist) {
            Institution_student::where('student_id', $Student->student_id)->delete();
            Institution_class_student::where('student_id', $Student->student_id)->delete();
            Institution_student_admission::where('student_id', $Student->student_id)->delete();
            Security_user::where('id', $Student->student_id)->delete();
            $this->output->writeln('cleaned:'. $Student->openemis_no);
        }else{
            $this->output->writeln('not-removed:'. $Student->openemis_no);
        }
    }

    public function cleanInvalidData($Student)
    {
        $Student = (array) $Student;
        $exist = Examination_student::where('nsid',$Student['nsid'])->count();
        
        $this->uniqueUId = new UniqueUid();

        $nsid = ltrim(rtrim($Student['nsid'],'-'),'-');
        if(!$this->uniqueUId::isValidUniqueId('DBY-898-3J2')){
        }
    }
}
