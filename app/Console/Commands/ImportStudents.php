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

                $isMailSent = Mail::to($user->email)->send(new StudentImportSuccess($file));
                if($isMailSent){
                    DB::table('uploads')
                        ->where('id',  $file['id'])
                        ->update(['is_processed' =>1,'is_email_sent' => 1]);
                }else{
                    DB::table('uploads')
                        ->where('id',  $file['id'])
                        ->update(['is_processed' =>1]);
                }

            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                self::writeErrors($e,$file);
                $isMailSent = Mail::to($user->email)->send(new StudentImportFailure($file));
                if($isMailSent){
                    DB::table('uploads')
                        ->where('id',  $file['id'])
                        ->update(['is_processed' =>1,'is_email_sent' => 1]);
                }else{
                    DB::table('uploads')
                        ->where('id',  $file['id'])
                        ->update(['is_processed' =>1]);
                }

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

            array_push($errors,$error);

        }

        array_walk($errors , 'append_errors_to_excel',$reader);

        $objWriter = new \PHPExcel_Writer_Excel2007($reader);
        Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
        $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);


    }
}
