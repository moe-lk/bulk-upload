<?php

namespace App\Console\Commands;

use App\Http\Controllers\StudentImportSuccessMailController;
use App\Imports\UsersImport;
use App\Imports\StudentUpdate;
use App\Mail\StudentImportFailure;
use App\Mail\StudentImportSuccess;
use App\Mail\IncorrectTemplate;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Log;
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
        if(count($files) == 0){
            $files = $this->getTerminated();
        }
        $files = array_chunk($files, 10);
        array_walk($files, array($this,'process'));
        unset($files);
        if((count($this->getFiles()) > 0) && $this->checkTime()){
//            $this->handle();
        }else{
            exit();
        }
    }
    
    
    protected function  process($files){
        array_walk($files, array($this,'processSheet'));
    }
    
    protected function getTerminated() {
        $files = Upload::where('is_processed', '=', 3)
                        ->get()->toArray();
        return $files;
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
    
    public function processSuccessEmail($file,$user,$subject) {
        $file['subject'] = $subject;
        try {
            Mail::to($user->email)->send(new StudentImportSuccess($file));
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 1, 'is_email_sent' => 1]);
        } catch (Exception $ex) {
            $this->handle();
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 1, 'is_email_sent' => 2]);
        }
    }
    
    public function processFailedEmail($file,$user,$subject) {
        $file['subject'] = $subject;
        try {
            Mail::to($user->email)->send(new StudentImportFailure($file));
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 2, 'is_email_sent' => 1]);
        } catch (Exception $ex) {
            $this->handle();
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 2, 'is_email_sent' => 2]);
        }
    }

    protected function processSheet($file){
        $user = User::find($file['security_user_id']);
        if ($this->checkTime()) {
            try {
                DB::beginTransaction();
                DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 3]);
                DB::commit();
                
                $this->import($file,1,'B');
                $this->import($file,2,'B');
                
                DB::beginTransaction();
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>1]);
                DB::commit();
               
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                self::writeErrors($e,$file,$sheet);
                try {
                    Mail::to($user->email)->send(new IncorrectTemplate($file));
                    DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['is_processed' => 2, 'is_email_sent' => 1]);
                } catch (Exception $ex) {
                    $this->handle();
                    DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['is_processed' => 2, 'is_email_sent' => 2]);
                }
            }
        } else {
            exit();
        }
    }
    
    protected function getSheetCount($file){
       $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        return $reader->getSheetCount();
    }


    protected function import($file,$sheet,$column){
            ini_set('memory_limit', '2048M');
            sleep(3);
          //process the import if the time range is between morening and evening
             try {
                $user = User::find($file['security_user_id']);
                $excelFile = '/sis-bulk-data-files/' . $file['filename'];

               
                switch ($sheet) {
                    case 1:
                        if (($this->getHigestRow($file, $sheet,$column) > 2) && ($this->getSheetCount($file) > 3)) {

                            $import = new UsersImport($file);
                            Excel::import($import, $excelFile, 'local');
                            // $this->processSuccessEmail($file,$user,'Fresh Student Data Upload');
                            
                        }
                        break;

                    case 2:
                        if (($this->getHigestRow($file, $sheet,$column) > 2) && ($this->getSheetCount($file) > 3)) {
                            $import = new StudentUpdate($file);
                            Excel::import($import, $excelFile, 'local');
                            // $this->processSuccessEmail($file,$user, 'Existing Student Data Update');
                        }
                        break;
                    default:
                        break;
                }
                

            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                 self::writeErrors($e,$file,$sheet);
                 switch ($sheet) {
                    case 1:
                            // $this->processFailedEmail($file,$user,'Fresh Student Data Upload');
                        break;
                    case 2:
                            // $this->processFailedEmail($file,$user, 'Existing Student Data Update');
                        break;
                    default:
                        break;
                }
                
                DB::table('uploads')
                    ->where('id',  $file['id'])
                    ->update(['is_processed' =>2]);
               
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

    protected function getHigestRow($file,$sheet,$column){
        $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        $reader->setActiveSheetIndex($sheet);
        return  $reader->getActiveSheet()->getHighestDataRow($column);
    }

    
    protected function writeErrors($e,$file,$sheet){
        ini_set('memory_limit', '2048M');
        $failures = $e->failures();
        $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        $reader->setActiveSheetIndex($sheet);
        $failures = array_map( array($this,'processErrors'),$failures );
        array_walk($failures , 'append_errors_to_excel',$reader);
        $objWriter = new \PHPExcel_Writer_Excel2007($reader);
        Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
        $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);
        unset($objWriter);
    }
}
