<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use App\Models\Student_guardian;
use Illuminate\Console\Command;

class MapStudentArea extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:student_area';

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
        $students = Security_user::where('is_student',true)->get()->toArray();
        array_walk($students,array($this,'process'));
    }

    public function process($student){
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $father = Student_guardian::where('student_id',$student['id'])
        ->join('security_users as sg','guardian_id', 'sg.id')
        ->where('guardian_relation_id',1)
        ->get()->first();

        $mother = Student_guardian::where('student_id',$student['id'])
        ->join('security_users as sg','guardian_id', 'sg.id')
        ->where('guardian_relation_id',2)
        ->get()->first();

        $guardian = Student_guardian::where('student_id',$student['id'])
        ->join('security_users as sg','guardian_id', 'sg.id')
        ->where('guardian_relation_id',3)
        ->get()->first();

        if(!is_null($father) && is_null($mother) && is_null($guardian)){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $father->address_area_id]);
            $output->writeln('Updated father area to:'. $student['openemis_no']);
        }elseif(!is_null($mother)  && (is_null($father) && is_null($guardian))){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $mother->address_area_id]);
            $output->writeln('Updated mother area to:'. $student['openemis_no']);
        }elseif(!is_null($guardian) && is_null($father) && is_null($mother)){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $guardian->address_area_id]);
            $output->writeln('Updated guardian area to:'. $student['openemis_no']);
        }elseif(!is_null($mother)  && !is_null($father) && ($father->address_area_id ==  $mother->address_area_id)){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $mother->address_area_id]);
            $output->writeln('Updated father & mother area to:'. $student['openemis_no']);
        }elseif(!is_null($mother)  && !is_null($father) && ($father->address_area_id !==  $mother->address_area_id) && !is_null($guardian)){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $guardian->address_area_id]);
            $output->writeln('Updated guardian area to:'. $student['openemis_no']);
        }elseif(!is_null($father) && $father->address == $student['address']){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $guardian->address_area_id]);
            $output->writeln('Updated mother area to:'. $student['openemis_no']);
        }elseif(!is_null($mother) && $mother->address == $student['address']){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $mother->address_area_id]);
            $output->writeln('Updated mother area to:'. $student['openemis_no']);
        }elseif(!is_null($guardian) && $guardian->address == $student['address']){
            Security_user::where('id',$student['id'])
            ->update(['address_area_id' => $guardian->address_area_id]);
            $output->writeln('Updated mother area to:'. $student['openemis_no']);
        }
    }
}
