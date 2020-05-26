<?php

namespace App\Http\Controllers;

\Session::get('panier');

use Session;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Security_user;
use App\Models\Academic_period;
use App\Models\Education_grade;
use App\Models\Institution_class;
use App\Models\Examination_student;
use App\Models\Institution_student;
use App\Imports\ExaminationStudents;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\Institution_class_student;
use App\Exports\ExmainationStudentsExport;
use App\Imports\ExaminationStudentsImport;
use App\Models\Institution_student_admission;
use FuzzyWuzzy\Fuzz;
use FuzzyWuzzy\Process;

class ExaminationStudentsController extends Controller
{
    public function __construct($year = 2019, $grade = 'G5')
    {
        $this->year = $year;
        $this->grade = $grade;
        $this->student = new Security_user();
        $this->examination_student = new Examination_student();
        $this->fuzzy = new Fuzz();
        $this->process = new Process($this->fuzzy);
        $this->academic_period =  Academic_period::where('code', '=', $this->year)->first();
        $this->education_grade = Education_grade::where('code', '=', $this->grade)->first();
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
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Valid File Extensions
            $valid_extension = array("csv");

            // 20MB in Bytes
            $maxFileSize = 20971520;

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
                    // Artisan::call('examination:migration');
                    // Artisan::call('examination:migrate');
                } else {
                    Session::flash('message', 'File too large. File must be less than 20MB.');
                }
            } else {
                Session::flash('message', 'Invalid File Extension.');
            }
        }
        return redirect()->action('ExaminationStudentsController@index');
    }

    public static function callOnClick()
    {
        // Import CSV to Database
        $excelFile = "/examination/exams_students.csv"; // public_path("examination/exams_students.csv");

        $import = new ExaminationStudentsImport();
        $import->import($excelFile, 'local', \Maatwebsite\Excel\Excel::CSV);
    }

    public  function doMatch()
    {
        $students = Examination_student::get()->toArray();
        //    array_walk($students,array($this,'clone'));
        array_walk($students, array($this, 'clone'));
    }

    public function clone($student)
    {
        //get student matching with same dob and gender
        $isMatching = $this->isMatching($student);

        // if the first match missing do complete insertion
        $institution = Institution::where('code', '=', $student['schoolid'])->first();
        $institutionClass = Institution_class::where(
            [
                'institution_id' => $institution->id,
                'academic_period_id' => $this->academic_period->id,
                'education_grade_id' => $this->education_grade->id
            ]
        )->join('institution_class_grades', 'institution_classes.id', 'institution_class_grades.institution_class_id')->get()->toArray();

        $admissionInfo = [
            'instituion_class' => $institutionClass,
            'instituion' => $institution,
            'education_grade' =>  $this->education_grade,
            'academic_period' => $this->academic_period
        ];
        if (empty($isMatching)) {
            $sis_student = $this->student->insertExaminationStudent($student);
            $this->updateNSID($student, $sis_student);

            //TODO imlement insert student to admission table
            $student['id'] = $sis_student['id'];
            if (count($institutionClass) == 1) {
                $admissionInfo['instituion_class'] = $institutionClass[0];
                Institution_student::createExaminationData($student, $admissionInfo);
                Institution_student_admission::createExaminationData($student, $admissionInfo);
                Institution_class_student::createExaminationData($student, $admissionInfo);
            }else{
                Institution_student_admission::createExaminationData($student, $admissionInfo);
                Institution_student::createExaminationData($student, $admissionInfo);
            }
        } else{
            $this->student->updateExaminationStudent($student, $isMatching);
            $isMatching = array_merge($student,$isMatching);
            Institution_student::updateExaminationData($isMatching,$admissionInfo);
            $this->updateNSID($student, $isMatching);
        }
    }

    /**
     * This funciton is implemented fuzzy search algorythem 
     * to get the most matching name with the exisitng students
     * data set
     *
     * @param [type] $student
     * @return array
     */
    public function isMatching($student)
    {
        $sis_users = $this->student->getMatches($student);
        $studentData = [];
        if (is_null($sis_users)) {
            $studentData = [];
        } elseif (count($sis_users) > 0) {
            //Extract highest one   
            $matchingStudentName =  $this->process->extractOne($student['f_name'], array_column($sis_users, 'first_name'), null, [$this->fuzzy, 'ratio']);
            $matchingStudentId = (array_search($matchingStudentName[0], array_column($sis_users, 'first_name')));
            $matchingStudent = $sis_users[$matchingStudentId];
            $studentData = $matchingStudent;
        }
        return $studentData;
    }



    /**
     * Generate new NSID for studets
     *
     * @param [type] $student
     * @param [type] $sis_student
     * @return void
     */
    public function updateNSID($student, $sis_student)
    {
        $student['nsid'] = $sis_student['openemis_no'];
        $this->examination_student->where(['st_no' => $student['st_no']])->update($student);
    }

    /**
     * export the all data with NSID
     *
     * @return void
     */
    public function export()
    {
        return Excel::download(new ExmainationStudentsExport, 'Students_data_with_nsid.csv');
    }
}
