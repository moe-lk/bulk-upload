<?php

namespace App\Console\Commands;

use App\Models\Academic_period;
use App\Models\Institution_class;
use App\Models\Institution_shift;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloneConfigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clone:config {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone configuration data for new year';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->shifts = new Institution_shift();
        $this->academic_period = new Academic_period();
        $this->institution_classes = new Institution_class();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $year = $this->argument('year');
        $shift = $this->shifts->getShiftsToClone($year - 1);
        $previousAcademicPeriod = $this->academic_period->getAcademicPeriod($year - 1);
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);

        $shift = array_chunk($shift,1000);
        $params = [
            'year' => $year,
            'academic_period' => $academicPeriod,
            'previous_academic_period' => $previousAcademicPeriod
        ];
        array_walk($shift,array($this,'array_walk'),$params);

    }

    public function array_walk($shift,$count,$params){
        array_walk($shift,array($this,'process'),$params);
    }

    public function process($shift,$count,$params){
        $year = $params['year'];
        $academicPeriod = $params['academic_period'];
        $previousAcademicPeriod = $params['previous_academic_period'];
        DB::beginTransaction();
        try{
            $shiftId = $this->updateShifts($year, $shift);
            $institutionClasses = $this->institution_classes->getShiftClasses($previousAcademicPeriod->id, $shift['id']);
            if (!empty($institutionClasses) && !is_null($shiftId) && !is_null($academicPeriod) ) {
                $params = ['institution_shift_id' => $shiftId,
                    'academic_period_id' => $academicPeriod->id];
                $institutionClasses = $this->generateNewClass($institutionClasses,$shiftId,$academicPeriod->id);
                $this->institution_classes->insert($institutionClasses);
                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln('##########################################################################################################################');
                $output->writeln('updating from '. $shiftId);
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
        }
    }


    /**
     * generate new class object for new academic year
     *
     * @param $classes
     * @param $shiftId
     * @param $academicPeriod
     * @return array
     */
    public function generateNewClass($classes,$shiftId,$academicPeriod){
        $newClasses = [];
        foreach ( $classes as $class) {
            unset($class['id']);
            unset($class['staff_id']);
            unset($class['total_male_students']);
            unset($class['total_female_students']);
            unset($class['total_students']);
            $noOfStudents = $class['no_of_students'] == 0 ? 40 : $class['no_of_students'];
            $class['academic_period_id'] = $academicPeriod;
            $class['no_of_students'] = $noOfStudents;
            $class['created'] = now();
            $class['institution_shift_id'] = $shiftId;
            array_push($newClasses,$class);
        }
        return $newClasses;
    }

    /**
     * update shifts
     * @param $year
     * @param $shift
     * @return mixed
     */
    public function updateShifts($year,$shift){
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $this->shifts->where('id',$shift['id'])->update(['cloned' => '2020']);
        $shift['academic_period_id'] = $academicPeriod->id;
        $exist = $this->shifts->shiftExists($shift);
        if(!$exist){
            $shift['cloned'] = '2020';
            return $this->shifts->create((array)$shift)->id;
        }
    }
}
