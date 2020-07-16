<?php

namespace App\Http\Controllers;

use Session;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Security_user;
use App\Models\Academic_period;
use App\Models\Education_grade;
use App\Models\Institution_class;
use App\Models\Examination_student;
use App\Models\Institution_student;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\Institution_class_student;
use App\Exports\ExaminationStudentsExport;
use App\Imports\ExaminationStudentsImport;
use App\Models\Institution_student_admission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

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

            // 20MB in Bytes
            $maxFileSize = 30971520;

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
    public static function callOnClick()
    {
        // Import CSV to Database
        $excelFile = "/examination/exams_students.csv";

        $import = new ExaminationStudentsImport();
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
     * Iterate over existing student's data
     *
     * @return void
     */
    public  function doMatch()
    {
        $students = Examination_student::get()->toArray();
        //    array_walk($students,array($this,'clone'));
        array_walk($students, array($this, 'clone'));
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
            case preg_match('13', $this->education_grade->code):
                $students['taking_al_exam'] = true;
                break;
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
        //get student matching with same dob and gender
        $matchedStudent = $this->getMatchingStudents($student);

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
                $this->updateStudentId($student, $sis_student);

                //TODO implement insert student to admission table
                $student['id'] = $sis_student['id'];

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
                // update the matched student's data    
            } else {
                $studentData = $this->student->updateExaminationStudent($student, $matchedStudent);
                $matchedStudent = array_merge((array) $student, $matchedStudent);
                $studentData = array_merge((array) $matchedStudent, $studentData);
                Institution_student::updateExaminationData($studentData, $admissionInfo);
                $this->updateStudentId($student, $studentData);
            }
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
        $sis_users = $this->student->getMatches($student);
        $studentData = [];
        if (!is_null($sis_users) && (count($sis_users) > 0)) {
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
    public function searchSimilarName($student, $sis_students)
    {
        $highest = [];
        $matchedData = [];

        //search name with first name
        foreach ($sis_students as $key => $value) {
            similar_text((strtoupper($student['f_name'])), (strtoupper($value['first_name'])), $percentage);
            $value['rate'] = $percentage;
            if ($value['rate'] == 100) {
                $matchedData[] = $value;
                $highest = $value;
            }
        }


        //search the name with full name
        // foreach ($matchedData as $key => $value) {
        //     similar_text((strtoupper($student['f_name'])), (strtoupper($value['first_name'])), $percentage);
        //     $value['rate'] = $percentage;
        //     if ($value['rate'] == 100) {
        //         $matchedData[] = $value;
        //         $highest = $value;
        //     }
        // }

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
            $this->examination_student->where(['st_no' => $student['st_no']])->update($student);
            $this->output->writeln('Updated ' . $sis_student['student_id'] . ' to NSID' . $sis_student['openemis_no']);
        } catch (\Exception $th) {
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
       (new ExaminationStudentsExport)->queue('/examination/Students_data_with_nsid.csv');
       return back()->withSuccess('Export started!');
    }

    public function downloadErrors()
    {

        $file_path = storage_path() . '/app/examination/errors.csv';
        return Response::download($file_path);
    }
}
