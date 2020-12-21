<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Aws\Ses\SesClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Institution_class;


class FileController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth');
        $this->ses = new SesClient(
            [
                'version' => '2010-12-01',
                'region' => 'us-east-2',

            ]
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request){



        $validator = Validator::make(
            [
                'import_file'      => $request->import_file,
                'extension' => strtolower($request->import_file->getClientOriginalExtension()),
                'class' => $request->class,
                'email' => auth()->user()->email
            ],
            [
                'import_file'          => 'required',
                'extension'      => 'required|in:xlsx,xls,ods|max:2048',
                'class' => 'required',
                'email' => 'required'

            ],
            ['email.required' => 'You dont have email  in your account, pleas contact your Zonal/Provincial Coordinator and update the email to get notification']
        );
//        try {
//            $result = $this->ses->verifyEmailIdentity([
//                'EmailAddress' => auth()->user()->email,
//            ]);
//            var_dump($result);
//        } catch (AwsException $e) {
//            // output error message if fails
//            echo $e->getMessage();
//            echo "\n";
//        }
//        if ($validator->fails()) {
//            return back()
//                ->withErrors($validator);
//        }


        $uploadFile = $validator->validated()['import_file'];
        $class = Institution_class::find($validator->validated()['class']);
//        dd(auth()->user()->principal[0]->institution_group[0]->institution);
        $institution = auth()->user()->permissions->isEmpty() ? auth()->user()->principal[0]->institution_group[0]->institution->code : auth()->user()->permissions[0]->institution_staff->institution->code;


        $fileName = time().'_'.$institution.'_'.str_replace(' ','_', clean($class->name)).'_'.auth()->user()->openemis_no.'_student_bulk_data.xlsx';
        Storage::disk('local')->putFileAs(
            'sis-bulk-data-files/',
            $uploadFile,
            $fileName
        );

        $upload = new Upload;
        $upload->fileName =$fileName;
        $upload->model = 'Student';
        $upload->node = 'None';
        $upload->institution_class_id = $class->id;
        $upload->user()->associate(auth()->user());
        $upload->save();


        return redirect('/')->withSuccess('The file is uploaded, we will process and let you know by your email');
    }

    public function updateQueueWithUnprocessedFiles($id, $action){
        if($action == 100){
            DB::table('uploads')
                ->where('id', $id)
                ->update(['is_processed' => 0]);
        }elseif ($action == 200) {
            DB::table('uploads')
                ->where('id', $id)
                ->update(['is_processed' => 4]);
        }
    }


    public function downloadTemplate(){
        $filename = 'censusNo_className_sis_students_bulk_upload';
        $version = '2007_V2.0.2_20201211.xlsx';
        $file_path = storage_path() .'/app/public/'. $filename.'_'.$version;;
        if (file_exists($file_path))
        {
            return Response::download($file_path, Auth::user()->openemis_no.'_'.$filename.$version, [
                'Content-Length: '. filesize($file_path)
            ]);
        }
        else
        {
            return response()->view('errors.404');
        }
    }


    /**
     * @param $filename
     * @return Processed excel file with error
     */
    public function downloadErrorFile($filename){

        $file_path = storage_path().'/app/sis-bulk-data-files/processed/'. $filename;
        if (file_exists($file_path))
        {
            return Response::download($file_path, $filename, [
                'Content-Length: '. filesize($file_path)
            ]);
        }
        else
        {
            abort(404, 'We did not found an error file.');
        }
    }


    public function downloadFile($filename){
        $file_path = storage_path().'/app/sis-bulk-data-files/'. $filename;
        if (file_exists($file_path))
        {
            return Response::download($file_path, $filename, [
                'Content-Length: '. filesize($file_path)
            ]);
        }
        else
        {

            abort(404, 'We did not found an error file.');
        }
    }
}
