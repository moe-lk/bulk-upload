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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

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
        $files = $this->getFiles();
        $files = array_chunk($files, 10);
        array_walk($files, array($this,'process'));
        unset($files);
        if((count($this->getFiles()) > 0) && $this->checkTime()){
            $this->handle();
        }else{
            exit();
        }
    }
    
    
    protected function  process($files){
        array_walk($files, array($this,'import'));
    }

    protected function getFiles(){
         $files = Upload::where('is_processed', '=', 0)
            ->orWhere(function ($query){
                $query->where('is_processed','=',3)
                    ->where('updated_at','>=', \Carbon\Carbon::now()->subHour());
            })
            ->get()->toArray();
         return $files;   
    }

    protected function checkTime(){
        $time = Carbon::now()->tz('Asia/Colombo');
        $morning = Carbon::create($time->year, $time->month, $time->day, env('CRON_START_TIME',0), 29, 0)->tz('Asia/Colombo')->setHour(0); //set time to 05:59
        
        $evening = Carbon::create($time->year, $time->month, $time->day, env('CRON_END_TIME',0), 30, 0)->tz('Asia/Colombo')->setHour(23); //set time to 18:00
         
        $check = $time->between($morning,$evening, true);
        return $check;
    }


    protected function import($file){
            sleep(3);
            if($this->checkTime()) {
          //process the import if the time range is between morening and evening
             try {
                DB::beginTransaction();
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>3]);
                DB::commit();

                $import = new UsersImport($file);
                $user = User::find($file['security_user_id']);
                $excelFile = '/sis-bulk-data-files/'.$file['filename'];
                Excel::import($import,$excelFile,'local');
                
               

                DB::beginTransaction();
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>1]);
                DB::commit();
                try{
                   
                     Mail::to($user->email)->send(new StudentImportSuccess($file));
                     DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>1,'is_email_sent' => 1]);
                } catch (Exception $ex) {
                    $this->handle();
                     DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>1,'is_email_sent' => 2]);
                }
               

            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                self::writeErrors($e,$file);
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>2]);
                try{
                    Mail::to($user->email)->send(new StudentImportFailure($file));
                       DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>2,'is_email_sent' => 1]);
                } catch (Exception $ex) {
                      $this->handle();
                      DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>2,'is_email_sent' => 2]);
                }
            }
        } else {
            exit();
        }
    }
           

            

    protected function processErrors($failure){
            $error_mesg = implode(',',$failure->errors());
            $failure = [
                'row'=> $failure->row(),
                'errors' => [ $error_mesg],
                'attribute' => $failure->attribute()
            ];
            return $failure;
        
    }




    protected function writeErrors($e,$file){
        ini_set('memory_limit', '1024M');
        $failures = $e->failures();
        $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        $reader->setActiveSheetIndex(1);
        $failures = array_map( array($this,'processErrors'),$failures );
        array_walk($failures , 'append_errors_to_excel',$reader);
        $objWriter = new \PHPExcel_Writer_Excel2007($reader);
        Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
        $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);
        unset($objWriter);
    }
}
