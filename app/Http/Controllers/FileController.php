<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;


class FileController extends Controller
{


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request){
        $uploadFile = $request->file('import_file');
        $fileName = time().'_'.$uploadFile->getClientOriginalName();
        Storage::disk('local')->putFileAs(
            'sis-bulk-data-files/',
            $uploadFile,
            $fileName
        );

        $upload = new Upload;
        $upload->fileName =$fileName;
        $upload->model = 'Student';
        $upload->institution_class_id = $request->input('class');
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
        $file_path = storage_path() .'/app/sis-bulk-data-files/processed/'. $filename;;
        if (file_exists($file_path))
        {
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
