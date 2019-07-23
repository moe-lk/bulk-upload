<?php

namespace App\Console\Commands;

use App\Imports\UsersImport;
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
                $excelFile = '/sis-bulk-data-files/'.$file['filename'];
                Excel::import($import,$excelFile,'local');
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
                $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);
            }


            DB::table('uploads')
                ->where('id',  $file['id'])
                ->update(['is_processed' =>1]);
            \Illuminate\Support\Facades\Log::info("Updated file". $file['filename']);

            $user = User::find($file['security_user_id']);
            switch ($success){
                case false:
                    $to_name = $user->first_name;
                    $to_email = $user->email;
                    $data = array('name'=>$user->first_name, "body" => "We found some errors on your data file ". $file['filename']. ' Pleas fix the errors and re upload it');

                    try{
                        Mail::send('emails.mail', $data, function($message) use ($to_name, $to_email) {
                            $message->to($to_email, $to_name)
                                ->subject('SIS Bulk upload: Errors found '. date('Y:m:d H:i:s'));
                            $message->from('nsis.moe@gmail.com','NEMIS-SIS Bulk upload Service');
                        });
                        \Illuminate\Support\Facades\Log::info('email-sent',[$this->file]);

                    }catch (\Exception $e){
                        \Illuminate\Support\Facades\Log::error('Mail sending error to: '.'',[$e]);
                    }
                    break;
                default:
                    $to_name = $user->first_name;
                    $to_email = $user->email;
                    $data = array('name'=>$user->first_name, "body" => "Data upload success". $file['filename']. ' You can now find the data in particular  Class Room');

                    try{
                        Mail::send('emails.mail', $data, function($message) use ($to_name, $to_email) {
                            $message->to($to_email, $to_name)
                                ->subject('SIS Bulk upload: Errors found '. date('Y:m:d H:i:s'));
                            $message->from('nsis.moe@gmail.com','NEMIS-SIS Bulk upload Service');
                        });
                        \Illuminate\Support\Facades\Log::info('email-sent',[$this->file]);

                    }catch (\Exception $e){
                        \Illuminate\Support\Facades\Log::error('Mail sending error to: '.'',[$e]);
                    }
                    break;
            }

        }

    }

}
