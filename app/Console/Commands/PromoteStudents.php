<?php

namespace App\Console\Commands;

use App\Http\Controllers\BulkPromotion;
use Webpatser\Uuid\Uuid;
use App\Institution_grade;
use App\Models\Institution;
use App\Models\Academic_period;
use App\Models\Education_grade;
use Illuminate\Console\Command;
use App\Models\Institution_class;
use Illuminate\Support\Facades\DB;
use App\Models\Institution_student;
use App\Models\Institution_subject;
use Illuminate\Support\Facades\Log;
use App\Models\Institution_class_student;
use App\Models\Institution_class_subject;
use App\Models\Institution_subject_student;
use App\Models\Institution_student_admission;

/**
 * Class PromoteStudents
 * @package App\Console\Commands
 */
class PromoteStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:students  {institution} {year} {mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote students';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->instituion_grade = new \App\Models\Institution_grade();
        $this->academic_period = new Academic_period();
    }



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $year = $this->argument('year');
        $institution = $this->argument('institution');
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $previousAcademicPeriodYear = $academicPeriod->order;
        $previousAcademicPeriod = Academic_period::where('order',$previousAcademicPeriodYear+1)->first();
        $mode = $this->argument('mode');
        $institutionGrade = $this->instituion_grade->getInstitutionGradeToPromoted($previousAcademicPeriod->code,$institution,$mode);
        $output->writeln('Start promoting:'.$institution);
        $params = [
            'academicPeriod' => $academicPeriod,
            'previousAcademicPeriod' => $previousAcademicPeriod
        ];
        (new BulkPromotion())->callback($institutionGrade,$params);
        $output->writeln('Finished promoting:'.$institution);
    }
}
