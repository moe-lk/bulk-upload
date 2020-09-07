<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Examination_student;

class nsidFormatCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nsid:formatcheck {limit}';

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
        DB::table('examination_students')
        ->select('nsid','st_no')
        ->whereRaw('LENGTH(nsid) > 11')
        ->groupBy('nsid')
        ->orderBy('nsid')
        ->chunk($this->argument('limit'),function($Students){
            foreach ($Students as $ExamStudent) {
                $nsid = substr($ExamStudent->nsid,0,-1);
                Security_user::where('openemis_no',$ExamStudent->nsid)->update(['openemis_no'=> $nsid]);
                Examination_student::where('st_no',$ExamStudent->st_no)->update(['nsid'=> $nsid]);
                $this->output->writeln($ExamStudent->nsid .'same ID');
            }
        });
    }
}
