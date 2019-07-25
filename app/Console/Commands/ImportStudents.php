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


        $file = Upload::where('is_processed', '=', 0)->get()->first();
        $success = true;
        if (!is_null($file)) {
            try {

                $import = new UsersImport($file);
                $user = User::find($file['security_user_id']);
                $excelFile = '/sis-bulk-data-files/'.$file['filename'];
                Excel::import($import,$excelFile,'local');

                //send success message
                Mail::to($user->email)->send(new StudentImportSuccess($file));
            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $success = false;
                $failures = $e->failures();
                $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
                $objPHPExcel->setReadDataOnly(true);

                $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
                $reader->setActiveSheetIndex(1);
                $errors = [];

                //TODO : implement send email with failures
                foreach ($failures as $key => $failure) {
                    $error = [
                        'row'=> $failure->row(),
                         'errors' => [  implode(',',$failure->errors())]
                    ];

                    $search = array_filter($errors,function ($data) use ($failure){
                       return $data['row'] = $failure->row();
                    });

                    if($search){
                        array_push($search[0]['errors'],implode(',',$failure->errors()));
                        $errors = $search;
                    }

                    array_push($errors,$error);
                    $failure->row(); // row that went wrong
                    $failure->attribute(); // either heading key (if using heading row concern) or column index
                    $failure->errors(); // Actual error messages from Laravel validator
                    $failure->values(); // The values of the row that has failed.
                }

                $errors = unique_multidim_array($errors,'row');
                foreach ($errors as $key => $error){
                    $reader->getActiveSheet()->setCellValue('A'. ($error['row']) ,  'Errors: '. implode(',',$error['errors']));
                    $reader->getActiveSheet()->getStyle('A'. ($error['row']))->getAlignment()->setWrapText(true);
                }

                $objWriter = new \PHPExcel_Writer_Excel2007($reader);
                Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
                $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);

                //send email with errors
                Mail::to($user->email)->send(new StudentImportFailure($file));

            }
            DB::table('uploads')
                ->where('id',  $file['id'])
                ->update(['is_processed' =>1]);

        }

    }

}
