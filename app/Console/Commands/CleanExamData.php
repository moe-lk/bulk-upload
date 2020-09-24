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
            $students = DB::table('security_users')
            ->where('updated_from', 'doe')
            ->get()
            ->toArray();
            
        }elseif($type == 'all'){
            $students = DB::table('examination_students')
            ->where('nsid','<>','')
            ->whereNotNull('nsid')
            ->get()
            ->toArray();
        }elseif($type == 'lock'){
            $students = DB::table('examination_students')
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

    public function process($students,$count,$type){
       if($type === 'duplicate'){
        array_walk($students,array($this,'cleanData'));
       }elseif($type === 'lock'){
        array_walk($students,array($this,'lockData'));
       }
    }

    public function lockData($Student){
        $Student = json_decode(json_encode($Student),true);
        $student = Security_user::where('openemis_no',(string)$Student['nsid'])->first();
        if(!empty($student)){
            Institution_student::where('student_id', $student->id)->update(['updated_from' => 'doe']);
            Security_user::where('id', $student->id)->update(['updated_from' => 'doe']);
            $this->output->writeln('Locked:'. (string)$Student['nsid'] .':' . $student['openemis_no']);
        }
    }


    public function cleanData($Student)
    {
        $exist = Examination_student::where('nsid','=',  (string)$Student->openemis_no)->count();
        if (!$exist) {
            Institution_student::where('student_id', $Student->id)->delete();
            Institution_class_student::where('student_id', $Student->id)->delete();
            Institution_student_admission::where('student_id', $Student->id)->delete();
            Security_user::where('id', $Student->id)->delete();
            $this->output->writeln('cleaned:'.  (string)$Student->openemis_no);
        }else{
            
            Institution_student::where('student_id', $Student->id)->update(['updated_from' => 'doe']);
            Security_user::where('id', $Student->id)->update(['updated_from' => 'doe']);
            // if(!is_null($Student->deleted_at)){
            //     try{
            //         Institution_student::withTrashed()->where('student_id',$Student->id)->restore();
            //         Institution_class_student::withTrashed()->where('student_id',$Student->id)->restore();
            //         Institution_student_admission::withTrashed()->where('student_id',$Student->id)->restore();
            //         Security_user::withTrashed()->find($Student->id)->restore();
            //         $this->output->writeln('restored:'.  (string)$Student->openemis_no);
            //     }catch(\Exception $e){
            //     }    
            // }
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
