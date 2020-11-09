<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use Illuminate\Console\Command;
use App\Models\Institution_grade;
use Illuminate\Support\Facades\DB;

class CallPromotionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:run {year} {mode} {limit}';

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
        $this->instituion_grade = new Institution_grade();
        $this->academic_period = new Academic_period();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $year = $this->argument('year');
        $limit = $this->argument('limit');
        $mode = $this->argument('mode');
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $previousAcademicPeriodYear = $academicPeriod->order;
        $previousAcademicPeriod = Academic_period::where('order',$previousAcademicPeriodYear+1)->first();
        $institutions = $this->instituion_grade->getInstitutionGradeList($previousAcademicPeriod->code,$limit,$mode);
        $params = [
            'year' => $year,
            'mode'=> $mode
        ];
        if(in_array($mode,['AL','1-5','SP','6-11'])){
            array_walk($institutions,array($this,'callPromotion'),$params);
        }else{
            die('The give mode not support');
        }
       
    }

    protected function callPromotion($institution,$count,$params){
        $this->call('promote:students',['year' => $params['year'],'institution' => $institution['code'],'mode' => $params['mode'] ]);
    }
}
