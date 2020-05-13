<?php

namespace App\Http\Controllers;
\Session::get('panier');

use App\Models\Examination_student;
use Illuminate\Http\Request;
use Session;

class ExaminationStudentsController extends Controller
{
    public function index()
    {
        return view('uploadcsv');
    }

    public function uploadFile(Request $request)
    {

        if ($request->input('submit') != null) {

            $file = $request->file('file');

            // File Details
            $filename = $file->getClientOriginalName();
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
                    $location = 'uploads';

                    // Upload file
                    $file->move($location, $filename);

                    Session::flash('message', 'File upload successfully!');
                } else {
                    Session::flash('message', 'File too large. File must be less than 20MB.');
                }
            } else {
                Session::flash('message', 'Invalid File Extension.');
            }

        }

        // Redirect to index
        return redirect()->action('ExaminationStudentsController@index');
    }

    public static function callOnClick()
    {
        // Import CSV to Database
        $filepath = public_path("uploads/exams_students.csv");

        // Reading file
        $file = fopen($filepath, "r");
        $importData_arr = array();
        $i = 0;

        while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
            $num = count($filedata);

            // Skip first row (The csv file must have a title)
            if ($i == 0) {
                $i++;
                continue;
            }
            for ($c = 0; $c < $num; $c++) {
                $importData_arr[$i][] = $filedata [$c];
            }
            $i++;
        }
        fclose($file);
        // Insert to MySQL database
        foreach ($importData_arr as $importData) {

            $insertData = array(
                "nsid" => $importData[0],
                "school_id" => $importData[1],
                "full_name" => $importData[2],
                "dob" => $importData[3],
                "gender" => $importData[4],
                "address" => $importData[5],
                "annual_income" => $importData[6],
                "has_special_need" => $importData[7],
                "disable_type" => $importData[8],
                "disable_details" => $importData[9],
                "special_education_centre" => $importData[10]);
            Examination_student::insertData($insertData);
        }
    }
}
