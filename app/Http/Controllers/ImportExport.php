<?php

namespace App\Http\Controllers;

  
use Illuminate\Http\Request;
use App\Exports\UsersExport;
use App\Imports\UsersImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\Importable;



class ImportExport extends Controller
{

    use Importable;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

    }

     /**
    * @return \Illuminate\Support\Collection
    */
    public function importExportView()
    {
        if(Auth::user()->super_admin ){
            return view('uploadcsv');
        }else{
            $classes = (!Auth::user()->permissions->isEmpty())  ?  Auth::user()->permissions[0]->staff_class : Auth::user()->principal[0]->security_group_institution->institution_classes;
            return view('importExport')->with('classes',$classes);
        }
    }


   
    /**
    * @return \Illuminate\Support\Collection
    */
    public function export(Request $request) 
    {
         $request->validate([
                'class' => 'required'
            ]);
        return Excel::download(new UsersExport($request->input('class')), 'users.xlsx');
    }



    /**
    * @return \Illuminate\Support\Collection
    */
    public function import(Request $request)
    {

            $request->validate([
                'import_file' => 'required',
                'class' => 'required'
            ]);

        ini_set('max_execution_time', 600);
        // (new UsersImport)->import(request()->file('file'), null, \Maatwebsite\Excel\Excel::XLSX);


            $import = new UsersImport();
            try{
                $files = Storage::disk('sis-bulk-data-files')->allFiles();
                Excel::import($import,request()->file('import_file'));
            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();

                foreach ($failures as $failure) {
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                }
            }

           
        return back();
    }
}
