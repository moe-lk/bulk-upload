<?php

namespace App\Console\Commands;

use App\Http\Controllers\StudentImportSuccessMailController;
use App\Imports\UsersImport;
use App\Imports\StudentUpdate;
use App\Mail\StudentImportFailure;
use App\Mail\StudentImportSuccess;
use App\Mail\EmptyFile;
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
        while ($this->checkTime()){
            if($this->checkTime()){
                try {
                    if(!empty($files)){
                        array_walk($files, array($this,'process'));
                        unset($files);
                    }else{
                        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                        $output->writeln('No files found,Waiting for files');
                        $this->handle();
                    }

                }catch (Exception $e){
                    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                    $output->writeln($e);
                    $this->handle();
                }
            }else{
                exit();
            }
        }
    }


    protected function  process($files){
        array_walk($files, array($this,'processSheet'));
    }

    protected function getTerminated() {
        $files = Upload::where('is_processed', '=', 3)
            ->limit(10)
            ->get()->toArray();
        return $files;
    }

    protected function getFiles(){
         $files = Upload::where('is_processed', '=', 0)
            ->orWhere(function ($query){
                $query->where('is_processed','=',3)
                    ->where('updated_at','>=', \Carbon\Carbon::now()->subHour());
            })->limit(10)
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
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln('Processing the file: '.$file['filename']);
        try {
            Mail::to($user->email)->send(new StudentImportSuccess($file));
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 1, 'is_email_sent' => 1,'updated_at' => now()]);
        } catch (\Exception $ex) {
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 1, 'is_email_sent' => 2,'updated_at' => now()]);
        }
    }

    public function processFailedEmail($file,$user,$subject) {
        $file['subject'] = $subject;
        try {
            Mail::to($user->email)->send(new StudentImportFailure($file));
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 2, 'is_email_sent' => 1,'updated_at' => now()]);
        } catch (\Exception $ex) {
            DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['is_processed' => 2, 'is_email_sent' => 2,'updated_at' => now()]);
        }
    }

    public function processEmptyEmail($file,$user,$subject) {
        $file['subject'] = $subject;
        try {
            Mail::to($user->email)->send(new EmptyFile($file));
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'is_email_sent' => 1,'updated_at' => now()]);
        } catch (\Exception $ex) {
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'is_email_sent' => 2,'updated_at' => now()]);
        }
    }

    protected function removeEmptyRows(){
        $highestColumn = $this->worksheet->getHighestDataColumn(3);
        $higestRow = 1;
        for ($row = $this->startRow(); $row <= $this->highestRow; $row++) {
            $rowData = $this->worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
            if (isEmptyRow(reset($rowData))) {
                reset($rowData);
                continue;
            } else {
                $higestRow += 1;
            }
        }
    }

    protected function processSheet($file){
        $this->startTime = Carbon::now()->tz('Asia/Colombo');
        $user = User::find($file['security_user_id']);
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln('##########################################################################################################################');
        $output->writeln('Processing the file: '.$file['filename']);
        if ($this->checkTime()) {
            try {
                DB::beginTransaction();
                DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 3,'updated_at' => now()]);
                DB::commit();

                $this->import($file,1,'C');
                $this->import($file,2,'B');

            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                try {
                    Mail::to($user->email)->send(new IncorrectTemplate($file));
                    DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['is_processed' => 2, 'is_email_sent' => 1,'updated_at' => now()]);
                } catch (\Exception $ex) {
                    $this->handle();
                    DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['is_processed' => 2, 'is_email_sent' => 2 ,'updated_at' => now()]);
                }
            }
        } else {
            exit();
        }
    }



    protected function getSheetCount($file){
       $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        // $objPHPExcel->setReadDataOnly(false);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        return $reader->getSheetCount();
    }


    protected function import($file,$sheet,$column){
            set_time_limit(300);
             try {
                $user = User::find($file['security_user_id']);
                $excelFile = '/sis-bulk-data-files/' . $file['filename'];
//                dd($this->getHigestRow($file, $sheet,$column));
                if (($this->getSheetName($file,'Insert Students')) && ($this->getHigestRow($file, $sheet,$column) > 0))  { //

                    $import = new UsersImport($file);
                    Excel::import($import, $excelFile, 'local');
                    DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['insert' => 1,'is_processed' => 1,'updated_at' => now()]);
                    $this->processSuccessEmail($file,$user,'Fresh Student Data Upload');
                    $this->stdOut('Insert Students',$this->getHigestRow($file, $sheet,$column));
                }else  if (($this->getSheetName($file,'Update Students')) && ($this->getHigestRow($file, $sheet,$column) > 0)) {
                    $import = new StudentUpdate($file);
                    Excel::import($import, $excelFile, 'local');
                    DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['update' => 1,'is_processed' => 1,'updated_at' => now()]);
                    $this->processSuccessEmail($file,$user, 'Existing Student Data Update');
                    $this->stdOut('Update Students',$this->getHigestRow($file, $sheet,$column));
                }else if(($this->getSheetName($file,'Insert Students')) && ($this->getHigestRow($file, $sheet,$column) == 0)) {
                    DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 2]);
                    $this->processEmptyEmail($file,$user, 'Fresh Student Data Upload');
                }else if(($this->getSheetName($file,'Update Students')) && ($this->getHigestRow($file, $sheet,$column) == 0)) {
                    DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 2,'updated_at' => now()]);
                    $this->processEmptyEmail($file,$user, 'Existing Student Data Update');
                }else{
                    DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 2,'updated_at' => now()]);
                    $this->processEmptyEmail($file,$user, 'No valid data found');
                }

            }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                 self::writeErrors($e,$file,$sheet);
                 if($sheet == 1){
                     DB::table('uploads')
                         ->where('id', $file['id'])
                         ->update(['insert' => 2,'updated_at' => now()]);
                    $this->processFailedEmail($file,$user,'Fresh Student Data Upload');
                 }else if($sheet == 2){
                     DB::table('uploads')
                         ->where('id', $file['id'])
                         ->update(['update' => 2,'updated_at' => now()]);
                    $this->processFailedEmail($file,$user, 'Existing Student Data Update');
                 }
                 DB::table('uploads')
                     ->where('id',  $file['id'])
                     ->update(['is_processed' =>2 , 'updated_at' => now()]);

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

    }//1571384344_14124_Grade-1_1553427268_student_bulk_data.xlsx

    protected function  getSheetName($file,$sheet){
        $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
        return $reader->getSheetByName($sheet)  !== null;
    }

    protected function getHigestRow($file,$sheet,$column){
        $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() . '/app' . $excelFile);
        $objPHPExcel->setReadDataOnly(true);

        try{$reader = $objPHPExcel->load(storage_path() . '/app' . $excelFile);
            $reader->setActiveSheetIndex($sheet);
        }catch(\Exception $e){
            exit();
        }
        $higestRow = 0;
        $this->highestRow =  $reader->getActiveSheet()->getHighestRow($column);
        for ($row = 3; $row <= $this->highestRow; $row++) {
            $rowData = $reader->getActiveSheet()->getCell($column.$row)->getValue();
            if (empty($rowData) || $rowData == null) {
                continue;
            } else {
                $higestRow += 1;
            }
        }
        return $higestRow;

    }

    protected function stdOut($title,$rows){
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln(   $title. ' Process completed at . '.' '. now());
        $now = Carbon::now()->tz('Asia/Colombo');
        $output->writeln('Total Processed lines: ' . $rows);
        $output->writeln( 'Time taken to process           : '.   $now->diffInSeconds($this->startTime) .' Seconds');
        $output->writeln('--------------------------------------------------------------------------------------------------------------------------');
    }


    protected function writeErrors($e,$file,$sheet){
        ini_set('memory_limit', '4096M');
        $baseMemory = memory_get_usage();
        gc_enable();
        gc_collect_cycles();
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
        $cacheSettings = array( ' memoryCacheSize ' => '256MB');
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        ini_set('memory_limit', -1);
        $failures = $e->failures();
        $excelFile = '/sis-bulk-data-files/processed/'.$file['filename'];
        $exists = Storage::disk('local')->exists($excelFile);
        if(!$exists){
            $excelFile = '/sis-bulk-data-files/'.$file['filename'];
        }
        $objPHPExcel = \PHPExcel_IOFactory::createReaderForFile(storage_path() .'/app'. $excelFile);
//        $objPHPExcel->setReadDataOnly(true);
        $reader = $objPHPExcel->load(storage_path().'/app' . $excelFile);
        $reader->setActiveSheetIndex($sheet);
        if(gettype($failures) == 'array'){
            $failures = array_map(array($this,'processErrors'),$failures );
            array_walk($failures , 'append_errors_to_excel',$reader);
            $objWriter = new \PHPExcel_Writer_Excel2007($reader);
            Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
            $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);
            $now = Carbon::now()->tz('Asia/Colombo');
            $output->writeln(  $reader->getActiveSheet()->getTitle() . ' Process completed at . '.' '. now());
            $output->writeln('memory usage for the processes : '.(memory_get_usage() - $baseMemory));
            $output->writeln( 'Time taken to process           : '.   $now->diffInSeconds($this->startTime) .' Seconds');
            $output->writeln(' errors reported               : '.count($failures));
            $output->writeln('--------------------------------------------------------------------------------------------------------------------------');
            unset($objWriter);
        }

    }
}
