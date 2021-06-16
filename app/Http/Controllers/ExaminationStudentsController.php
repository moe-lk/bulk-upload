<?php

namespace App\Http\Controllers;

use Session;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Security_user;
use App\Models\Academic_period;
use App\Models\Education_grade;
use App\Models\Institution_class;
use App\Models\Institution_shift;
use App\Notifications\ExportReady;
use Illuminate\Support\Facades\DB;
use App\Models\Examination_student;
use App\Models\Institution_student;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\Institution_class_student;
use App\Exports\ExaminationStudentsExport;
use App\Imports\ExaminationStudentsImport;
use App\Models\Institution_student_admission;

class ExaminationStudentsController extends Controller
{
    public function __construct($year = 2019, $grade = 'G5')
    {
        $this->year = $year;
        $this->grade = $grade;
        $this->student = new Security_user();
        $this->examination_student = new Examination_student();
        $this->academic_period =  Academic_period::where('code', '=', $this->year)->first();
        $this->education_grade = Education_grade::where('code', '=', $this->grade)->first();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    public function index()
    {
        return view('uploadcsv');
    }

    public function uploadFile(Request $request)
    {
        if ($request->input('submit') != null) {

            $file = $request->file('file');

            // File Details
            $filename = 'exams_students.csv';
            $extension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();

            // Valid File Extensions
            $valid_extension = array("csv");

            // 40MB in Bytes
            $maxFileSize = 40971520;

            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {

                // Check file size
                if ($fileSize <= $maxFileSize) {

                    // File upload location
                    Storage::disk('local')->putFileAs(
                        'examination/',
                        $file,
                        $filename
                    );
                    Session::flash('message', 'File upload successfully!');
                    // Redirect to index
                } else {
                    Session::flash('message', 'File too large. File must be less than 20MB.');
                }
            } else {
                Session::flash('message', 'Invalid File Extension.');
            }
        }
        return redirect()->action('ExaminationStudentsController@index');
    }

    /**
     * Import students data to the Examinations table 
     *
     * @return void
     */
    public static function callOnClick($year, $grade)
    {
        // Import CSV to Database
        $excelFile = "/examination/exams_students.csv";

        $import = new ExaminationStudentsImport($year, $grade);
        try {
            $import->import($excelFile, 'local', \Maatwebsite\Excel\Excel::CSV);
            if ($import->failures()->count() > 0) {
                $errors = $import->failures();
                $columns =  [
                    'remarks',
                    'st_no',
                    'stu_no',
                    "f_name",
                    "medium",
                    "gender",
                    "b_date",
                    "a_income",
                    "schoolid",
                    "spl_need",
                    "pvt_address",
                    "disability_type",
                    "disability",
                    "sp_center"
                ];

                $file = 'examination/errors.csv';
                Storage::put($file, implode(',', $columns));

                foreach ($errors as $error) {
                    Storage::append($file, implode(':', $error->errors()) . ',' . implode(',', $error->values()));
                }
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        }
    }

    /**
     * updated wrong census
     *
     * @param [type] $data
     * @return void
     */
    public function updateCensusNo($data)
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $student = Security_user::where('openemis_no', $data['nsid'])
            ->select('security_users.id')
            ->first();
        if (!is_null($student)) {
            $student = Institution_student::where('student_id', $student['id'])->get()->toArray();
            $Institution = Institution::where('code', $data['schoolid'])->get()->toArray();
            if (!empty($Institution)) {
                $Institution = $Institution[0];
                if (count($student) == 1) {
                    $student = $student[0];
                    if (((int)$Institution['id']) !=  ((int)$student['institution_id'])) {
                        $studentClass = Institution_class_student::where('student_id', $student['student_id'])
                            ->first();
                        Institution_class_student::where('student_id', $student['student_id'])->delete();
                        Institution_student::where('student_id', $student['student_id'])
                            ->update(['institution_id' =>  $Institution['id']]);
                        $class = new Institution_class();
                        if (!is_null($studentClass)) {
                            $class->updateClassCount($studentClass->toArray());
                        }
                        $output->writeln('updated student info:' . $data['nsid']);
                    }
                } else {
                    Institution_student::where('institution_id', '<>', $Institution['id'])->where('student_id', $student[0]['student_id'])
                        ->delete();
                    $output->writeln('updated student info:' . $Institution['id'] . '==' . $Institution['id']);
                }
            }
        }
    }

    /**
     * Iterate over existing student's data
     *
     * @return void
     */
    public  function doMatch($offset, $limit, $mode)
    {
        $students = [];
        switch ($mode) {
            case 'duplicate':
                $students =  DB::table('examination_students as es')
                    ->select(DB::raw('count(*) as total'), 'e2.*')
                    ->where('grade', $this->grade)
                    ->where('year', $this->year)
                    ->join('examination_students as e2', 'es.nsid', 'e2.nsid')
                    ->having('total', '>', 1)
                    ->groupBy('e2.st_no')
                    ->orderBy('e2.st_no')
                    ->offset($offset)
                    ->limit($limit)
                    ->get()->toArray();
                $students = (array) json_decode(json_encode($students));
                $this->output->writeln(count($students) . 'students remaining duplicate');
                array_walk($students, array($this, 'clone'));
                $this->output->writeln('All are generated');
                break;
            case 'empty';
                $students = Examination_student::whereNull('nsid')
                    ->orWhere('nsid', '<>', '')
                    ->where('grade', $this->grade)
                    ->where('year', $this->year)
                    ->offset($offset)
                    ->limit($limit)
                    ->get()->toArray();
                $students = (array) json_decode(json_encode($students));
                $this->output->writeln(count($students) . 'students remaining empty');
                array_walk($students, array($this, 'clone'));
                $this->output->writeln('All are generated');
                break;
            case 'invalid';
                $students = Examination_student::whereRaw('CHAR_LENGTH(nsid) > 11')
                    ->where('grade', $this->grade)
                    ->where('year', $this->year)
                    ->get()->toArray();
                $students = (array) json_decode(json_encode($students));
                $this->output->writeln(count($students) . 'students remaining with wrong NSID');
                array_walk($students, array($this, 'clone'));
                $this->output->writeln('All are generated');
                break;
            case 'count':
                $count = Examination_student::distinct('nsid')
                    ->where('grade', $this->grade)
                    ->where('year', $this->year)
                    ->count();
                $all = Examination_student::select('nsid')
                    ->count();
                $this->output->writeln($all . 'Total Unique nsid are: ' . $count);
                break;
            default:
                $students = Examination_student::offset($offset)
                    ->where('grade', $this->grade)
                    ->where('year', $this->year)
                    ->limit($limit)
                    ->get()->toArray();
                $students = (array) json_decode(json_encode($students));
                $this->output->writeln(count($students) . 'students remaining empty');
                array_walk($students, array($this, 'clone'));
                $this->output->writeln('All are generated');
        }
    }

    /**
     * Set Examination values
     *
     * @param array $students
     * @return array
     */
    public function setIsTakingExam($students)
    {
        $students['taking_g5_exam'] = false;
        $students['taking_ol_exam'] = false;
        $students['taking_al_exam'] = false;
        switch ($this->education_grade->code) {
            case 'G5':
                $students['taking_g5_exam'] = true;
                break;
            case 'G4':
                $students['taking_g5_exam'] = true;
                break;
            case 'G10':
                $students['taking_ol_exam'] = true;
                break;
            case 'G11':
                $students['taking_ol_exam'] = true;
                break;
                // case preg_match('13', $this->education_grade->code):
                //     $students['taking_al_exam'] = true;
                //     break;
        }
        return $students;
    }


    /**
     * Main function to merge the student's to SIS
     *
     * @param array $student
     * @return void
     */
    public function clone($student)
    {
        $student = (array)json_decode(json_encode($student));
        //get student matching with same dob and gender

        $matchedStudent = $this->getMatchingStudents($student);

        //add 0 to school id 
        $student['schoolid'] = str_pad($student['schoolid'], 5, '0', STR_PAD_LEFT);
        
        // if the first match missing do complete insertion 
        $institution = Institution::where('code', '=', $student['schoolid'])->first();
        if (!is_null($institution)) {

            // ge the class lists to belong the school
            $institutionClass = Institution_class::where(
                [
                    'institution_id' => $institution->id,
                    'academic_period_id' => $this->academic_period->id,
                    'education_grade_id' => $this->education_grade->id
                ]
            )->join('institution_class_grades', 'institution_classes.id', 'institution_class_grades.institution_class_id')->get()->toArray();

            // set search variables 
            $admissionInfo = [
                'instituion_class' => $institutionClass,
                'instituion' => $institution,
                'education_grade' =>  $this->education_grade,
                'academic_period' => $this->academic_period
            ];

            // if no matching found
            if (empty($matchedStudent)) {
                $sis_student = $this->student->insertExaminationStudent($student);

                //TODO implement insert student to admission table
                $student['id'] = $sis_student['id'];
                $sis_student['student_id'] =  $student['id'];

                $student = $this->setIsTakingExam($student);
                if (count($institutionClass) == 1) {
                    $admissionInfo['instituion_class'] = $institutionClass[0];
                    Institution_student::createExaminationData($student, $admissionInfo);
                    Institution_student_admission::createExaminationData($student, $admissionInfo);
                    Institution_class_student::createExaminationData($student, $admissionInfo);
                } else {
                    Institution_student_admission::createExaminationData($student, $admissionInfo);
                    Institution_student::createExaminationData($student, $admissionInfo);
                }
                $this->updateStudentId($student, $sis_student);
                // update the matched student's data    
            } else {
                $student = $this->setIsTakingExam($student);
                $studentData = $this->student->updateExaminationStudent($student, $matchedStudent);
                $matchedStudent = array_merge((array) $student, $matchedStudent);
                $studentData = array_merge((array) $matchedStudent, $studentData);
                Institution_student::updateExaminationData($studentData, $admissionInfo);
                $this->updateStudentId($student, $studentData);
            }
        } else {

            $this->output->writeln('Student ' . $student['st_no'] . ' not imorted' . $student['f_name']);
        }
    }

    /**
     * This function is implemented similar_text search algorithm 
     * to get the most matching name with the existing students
     * data set
     *
     * @param array $student
     * @return array
     */
    public function getMatchingStudents($student)
    {
        /**
         */
        $sis_student = $this->student->getMatches($student);
        $doe_students =  Examination_student::where('gender', $student['gender'])
            ->where('b_date', $student['b_date'])
            ->where('schoolid', $student['schoolid'])
            ->where('year',$this->year)
            ->where('grade',$this->grade)
            ->count();
        $count = $this->student->getStudentCount($student);

        $studentData = [];
        $sis_users  = (array) json_decode(json_encode($sis_student), true);
        // if the same gender same DOE has more than one 
        if (($doe_students > 1) || ($count > 1)) {
            $studentData = $this->searchSimilarName($student, $sis_users, false);
        } else {
            $studentData = $this->searchSimilarName($student, $sis_users);
        }
        return $studentData;
    }

    /**
     * Search most matching name
     *
     * @param array $student
     * @param array $sis_students
     * @return array
     */
    public function searchSimilarName($student, $sis_students, $surname_search = true)
    {
        $highest = [];
        $minDistance = 0;
        $matches = [];
        $data = [];
        foreach ($sis_students as $key => $value) {
            similar_text(strtoupper($value['first_name']), (strtoupper($student['f_name'])), $percentage);
            $distance = levenshtein(strtoupper($student['f_name']), strtoupper($value['first_name']));
            $value['rate'] = $percentage;
            switch (true) {
                case $value['rate'] == 100;
                    $highest = $value;
                    break;
                case (($distance <= 2) && ($distance < $minDistance));
                    $highest = $value;
                    $minDistance = $distance;
            }
        }

        if ($surname_search) {
            if (empty($highest)) {
                foreach ($sis_students as $key => $value) {
                    //search name with last name
                    similar_text(strtoupper(get_l_name($student['f_name'])), strtoupper(get_l_name($value['first_name'])), $percentage);
                    $value['rate'] = $percentage;
                    switch (true) {
                        case ($value['rate'] == 100);
                            $highest = $value;
                            $matches[] = $value;
                            break;
                    }
                }
            }
        }

        if (count($matches) > 1) {
            $highest =  $this->searchSimilarName($student, $sis_students, false);
        }

        return $highest;
    }

    /**
     * Generate new NSID for students
     *
     * @param array $student
     * @param array $sis_student
     * @return void
     */
    public function updateStudentId($student, $sis_student)
    {
        try {
            $student['nsid'] =  $sis_student['openemis_no'];
            // add new NSID to the examinations data set
            unset($student['id']);
            unset($student['taking_g5_exam']);
            unset($student['taking_al_exam']);
            unset($student['taking_ol_exam']);
            unset($student['total']);
            $students['updated_at'] =  now();
            $this->examination_student->where('st_no', $student['st_no'])->update($student);
            unset($student['st_no']);
            $this->output->writeln('Updated  to NSID' . $sis_student['openemis_no']);
        } catch (\Exception $th) {
            dd($th);
            $this->output->writeln('error');
            Log::error($th);
        }
    }

    /**
     * export the all data with NSID
     *
     * @return void
     */
    public function export()
    {
        $adminUser = Security_user::where('username', 'admin')->first();
        try {
            (new ExaminationStudentsExport($this->year,$this->grade))->store('examination/student_data_with_nsid.' . time() . '.csv');
            (new ExportReady($adminUser));
        } catch (\Throwable $th) {
            //throw $th;
            dd($th);
        }
        return back()->withSuccess('Export started!');
    }

    public function downloadErrors()
    {

        $file_path = storage_path() . '/app/examination/errors.csv';
        return Response::download($file_path);
    }

    public function downloadProcessedFile()
    {
        $file_path = storage_path() . '/app/examination/student_data_with_nsid.8606052465.csv';
        return Response::download($file_path);
    }
}
