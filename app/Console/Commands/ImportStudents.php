<?php

namespace App\Console\Commands;

use App\Http\Controllers\StudentImportSuccessMailController;
use App\Imports\UsersImport;
use App\Mail\StudentImportFailure;
use App\Mail\StudentImportSuccess;
use App\Models\Upload;
use App\Models\User;
use Cake\Log\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Facades\Excel;

class ImportStudents extends Command
{
    use Importable;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk students data upload';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        $file = Upload::where('is_processed', '=', 0)
            ->orWhere(function ($query){
                $query->where('is_processed','=',3)
                    ->where('updated_at','>=', \Carbon\Carbon::now()->subHour());
            })
            ->get()->first();
        if (!is_null($file)) {
            try {
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>3]);

                $import = new UsersImport($file);
                $user = User::find($file['security_user_id']);
                $excelFile = '/sis-bulk-data-files/'.$file['filename'];
                Excel::import($import,$excelFile,'local');
                Mail::to($user->email)->send(new StudentImportSuccess($file));
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>1]);
            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                self::writeErrors($e,$file);
                Mail::to($user->email)->send(new StudentImportFailure($file));
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>2]);

            }


        }

    }

    protected function writeErrors($e,$file){
        $failures = $e->failures();
        $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        $reader->setActiveSheetIndex(1);
        $errors = array();

        foreach ($failures as $key => $failure) {
            $error_mesg = implode(',',$failure->errors());
            $error = [
                'row'=> $failure->row(),
                'errors' => [ $error_mesg]
            ];
            $search = array_filter($errors,function ($data) use ($failure){
                return $data['row'] = $failure->row();
            });

            if($search && (!in_array($error_mesg,$search[0]['errors']))){
                array_push($search[0]['errors'],$error_mesg);
                $errors = $search;
            }
            array_push($errors,$error);

        }

        $errors = unique_multidim_array($errors,'row');
        array_walk($errors , 'append_errors_to_excel',$reader);

        $objWriter = new \PHPExcel_Writer_Excel2007($reader);
        Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
        $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);


    }
}
