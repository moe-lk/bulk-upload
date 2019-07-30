<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Institution_class;


class FileController extends Controller
{


    public function __construct()
    {
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
                'class' => $request->class
            ],
            [
                'import_file'          => 'required',
                'extension'      => 'required|in:xlsx,xls,ods|max:2048',
                'class' => 'required'

            ]
        );
        if ($validator->fails()) {
            return back()
                ->withErrors($validator);
        }

        $uploadFile = $validator->validated()['import_file'];
        $class = Institution_class::find($validator->validated()['class']);
//        dd(auth()->user()->principal[0]->institution_group[0]->institution);
        $institution = auth()->user()->permissions->isEmpty() ? auth()->user()->principal[0]->institution_group[0]->institution->code : auth()->user()->permissions[0]->institution_staff->institution->code;


        $fileName = time().'_'.$institution.'_'.str_replace(' ','_',$class->name).'_'.auth()->user()->openemis_no.'_student_bulk_data.xlsx';
        Storage::disk('local')->putFileAs(
            'sis-bulk-data-files/',
            $uploadFile,
            $fileName
        );

        $upload = new Upload;
        $upload->fileName =$fileName;
        $upload->model = 'Student';
        $upload->institution_class_id = $class->id;
        $upload->user()->associate(auth()->user());
        $upload->save();

        return redirect('/')->withSuccess('The file is uploaded, we will process and let you know by your email');
    }


    public function downloadTemplate(){
        $filename = 'SIS Students Bulk Upload Template.xlsx';
        $file_path = storage_path() .'/app/public/'. $filename;;
        if (file_exists($file_path))
        {
            return Response::download($file_path, Auth::user()->openemis_no.'_'.$filename, [
                'Content-Length: '. filesize($file_path)
            ]);
        }
        else
        {
            return View::make('errors.404');
        }
    }


    /**
     * @param $filename
     * @return Processed excel file with error
     */
    public function downloadErrorFile($filename){

        $file_path = storage_path().'/app/sis-bulk-data-files/processed/'. $filename;

//        dd($file_path);
        if (file_exists($file_path))
        {
//            dd('================'.file_exists($file_path));
            return Response::download($file_path, $filename, [
                'Content-Length: '. filesize($file_path)
            ]);
        }
        else
        {
            return View::make('errors.404');
        }
    }
}
