<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use Illuminate\Console\Command;
use App\Models\Institution_grade;

class CallPromotionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:run {year} {limit}';

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
        $year = $this->argument('year');
        $limit = $this->argument('limit');
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $previousAcademicPeriodYear = $academicPeriod->order;
        $previousAcademicPeriod = Academic_period::where('order',$previousAcademicPeriodYear+1)->first();
        $institutions = $this->instituion_grade->getInstitutionGradeList($previousAcademicPeriod->code,$limit);
        array_walk($institutions,array($this,'callPromotion'),$year);
    }

    protected function callPromotion($institution,$count,$year){
        $this->call('promote:students',['year' => $year,'institution' => $institution['code']]);
    }
}
