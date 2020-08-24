<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Academic_period;
use App\Models\Institution_class;
use App\Models\Institution_grade;
use App\Models\Institution_shift;
use App\Models\Institution_subject;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Institution_class_grade;
use App\Models\Education_grades_subject;
use App\Models\Institution_class_subject;

class CloneController extends Controller
{

    public function __construct()
    {
        $this->shifts = new Institution_shift();
        $this->academic_period = new Academic_period();
        $this->instituion_grade = new Institution_grade();
        $this->institution_classes = new Institution_class();
        $this->institution_class_subjects = new Institution_class_subject();
        $this->institution_subjects =  new Institution_subject();
        $this->education_grade_subjects =  new Education_grades_subject();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function array_walk($shift, $count, $params)
    {
        array_walk($shift, array($this, 'process'), $params);
    }

    public function process($shift, $count, $params)
    {
        $this->start_time = microtime(TRUE);
        $year = $params['year'];
        $academicPeriod = $params['academic_period'];
        $previousAcademicPeriod = $params['previous_academic_period'];
        try {
            $shiftId = $this->updateShifts($year-1, $shift);
            $institutionClasses = $this->institution_classes->getShiftClasses($shift['id']);
            $institutionSubjects = $this->institution_subjects->getInstitutionSubjects($shift['institution_id'], $academicPeriod->id);
            array_walk($institutionSubjects, array($this, 'insertInstitutionSubjects'), $academicPeriod);
            if (!empty($institutionClasses) && !is_null($shiftId) && !is_null($academicPeriod)) {

                $newInstitutionClasses = $this->generateNewClass($institutionClasses, $shiftId, $academicPeriod->id);

                $params = [
                    'previous_academic_period_id' => $previousAcademicPeriod->id,
                    'academic_period_id' => $academicPeriod->id,
                    'shift_id' => $shiftId
                ];

                try {
                    array_walk($newInstitutionClasses, array($this, 'insertInstitutionClasses'), $params);
                    $newInstitutionClasses = $this->institution_classes->getShiftClasses($shiftId);
                    // $this->output->writeln('##########################################################################################################################');
                    $this->output->writeln('updating classes from ' . $shiftId);
                } catch (\Exception $e) {
                    dd($e);
                    Log::error($e->getMessage(), [$e]);
                }
            }
            //            DB::commit();
        } catch (\Exception $e) {
            dd($e);
            Log::error($e->getMessage(), [$e]);
        }
        $this->end_time = microtime(TRUE);
        $this->output->writeln('Cloned:' . $shift['id'] . 'time taken to cook' . ( $this->end_time - $this->start_time));
        $this->output->writeln('##########################################################################################################################');
    }


    /**
     * @param $subjects
     * @param $count
     * @param $academicPeriod
     */
    public function insertInstitutionSubjects($subjects, $count, $academicPeriod)
    {
        try {
            $subjects['academic_period_id'] = $academicPeriod->id;
            $subjects['created'] = now();
            unset($subjects['total_male_students']);
            unset($subjects['total_female_students']);
            unset($subjects['id']);
            $classSubject = Institution_subject::create($subjects);
        } catch (\Exception $e) {
            dd($e);
            Log::error($e->getMessage(), [$e]);
        }
    }


    public function  insertInstitutionClasses($class, $count, $param)
    {
        try {

            $academicPeriod = $param['academic_period_id'];
            $educationGrdae = $class['education_grade_id'];

            unset($class['education_grade_id']);
            $noOfStudents = $class['no_of_students'] == 0 ? 40 : $class['no_of_students'];
            $class['academic_period_id'] = $academicPeriod;
            $class['no_of_students'] = $noOfStudents;
            $class['created'] = now();
            $class['institution_shift_id'] = $param['shift_id'];
            $this->output->writeln('Create class:' . $class['name']);

            $class = Institution_class::create($class);
            $institutionClassGrdaeObj['institution_class_id'] = $class->id;
            $institutionClassGrdaeObj['education_grade_id'] = $educationGrdae;

            Institution_class_grade::create($institutionClassGrdaeObj);
    
            $institutionSubjects = Institution_subject::query()->where('education_grade_id', $educationGrdae)
                ->where('institution_id', $class->institution_id)
                ->where('academic_period_id', $academicPeriod)
                ->groupBy('education_subject_id')
                ->get()
                ->toArray();

            $params['class'] = $class;


            //add grade subject if not exist in the system
            if (count($institutionSubjects)) {
                $institutionSubjects = Education_grades_subject::query()->where('education_grade_id', $class['education_grade_id'])
                    ->get()->toArray();
                array_walk($institutionSubjects, array($this, 'institutionSubject'), $class->institution_id);
            }
            $this->insertInstitutionClassSubjects($institutionSubjects, $class);
            //                array_walk($classSubjects,array($this,'insertInstitutionClassSubjects'),$params);
        } catch (\Exception $e) {
            dd($e);
            Log::error($e->getMessage(), [$e]);
        }
    }


    public function institutionSubject($subject, $count, $institution_id)
    {
        $subject['institution_id'] = $institution_id;
        Institution_subject::create($subject);
    }

    public function insertInstitutionClassSubjects($subjects, $class)
    {
        if (!empty($subjects)) {
            try {
                array_walk($subjects, array($this, 'insertClassSubjects'), $class);
                $this->output->writeln('updating subjects ' . $class->name);
            } catch (\Exception $e) {
                Log::error($e->getMessage(), [$e]);
            }
        };
    }

    public function insertClassSubjects($subject, $count, $newClassId)
    {
        try {
            $subjectobj['status'] = 1;
            $subjectobj['created_user_id'] = 1;
            $subjectobj['created'] = now();

            $subjectobj['institution_class_id'] = $newClassId->id;
            $subjectobj['institution_subject_id'] = $subject['id'];

            if (count($this->institution_class_subjects->isDuplicated($subjectobj))==0) {
                $this->institution_class_subjects->insert($subjectobj);
            }
        } catch (\Exception $e) {
            dd($e);
            Log::error($e->getMessage(), [$e]);
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
    public function generateNewClass($classes, $shiftId, $academicPeriod)
    {
        $newClasses = [];
        foreach ($classes as $class) {
            $noOfStudents = $class['no_of_students'] == 0 ? 40 : $class['no_of_students'];
            $class['academic_period_id'] = $academicPeriod;
            $class['no_of_students'] = $noOfStudents;
            $class['created'] = now();
            $class['institution_shift_id'] = $shiftId;
            array_push($newClasses, $class);
        }
        return $newClasses;
    }

    /**
     * update shifts
     * @param $year
     * @param $shift
     * @return mixed
     */
    public function updateShifts($year, $shift)
    {
        $academicPeriod = $this->academic_period->getAcademicPeriod($year);
        $this->shifts->where('id', $shift['id'])->update(['cloned' => '2020']);
        $shift['academic_period_id'] = $academicPeriod->id;
        $exist = $this->shifts->shiftExists($shift);
        return $this->shifts->create((array)$shift)->id;
    }
}
