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
use Webpatser\Uuid\Uuid;

class ImportStudents extends Command
{
    use Importable;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:students {node}';

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
        $this->output =  new \Symfony\Component\Console\Output\ConsoleOutput();
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
        //        if(empty($files)){
        //            $files = $this->getTerminatedFiles();
        //        }
        while ($this->checkTime()) {
            if ($this->checkTime()) {
                try {
                    if (!empty($files)) {
                        $this->process($files);
                        unset($files);
                        exit();
                    } else {
                        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                        $this->output->writeln('No files found,Waiting for files');
                        exit();
                    }
                } catch (Exception $e) {
                    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                    $this->output->writeln($e);
                    sleep(300);
                    $this->handle();
                }
            } else {
                exit();
            }
        }
    }


    protected function  process($files)
    {
        $time = Carbon::now()->tz('Asia/Colombo');
        $node = $this->argument('node');
        $files[0]['node'] = $node;
        $this->processSheet($files[0]);
        $now = Carbon::now()->tz('Asia/Colombo');
        $this->output->writeln('=============== Time taken to batch ' . $now->diffInMinutes($time));
    }

    protected function getTerminatedFiles()
    {
        $files = Upload::where('is_processed', '=', 3)
            ->where('updated_at', '<=', Carbon::now()->tz('Asia/Colombo')->subHours(3))
            ->limit(1)
            ->orderBy('updated_at', 'desc')
            ->get()->toArray();
        if (!empty($files)) {
            $this->output->writeln('******************* Processing a terminated file **********************');
            DB::beginTransaction();
            DB::table('uploads')
                ->where('id', $files[0]['id'])
                ->update(['is_processed' => 4, 'updated_at' => now()]);
            DB::commit();
        }
        return $files;
    }

    protected function getFiles()
    {
        $query = Upload::where('is_processed', '=', 0)
            ->select(
                'uploads.id',
                'uploads.security_user_id',
                'uploads.institution_class_id',
                'uploads.model',
                'uploads.filename',
                'uploads.is_processed',
                'uploads.deleted_at',
                'uploads.created_at',
                'uploads.updated_at',
                'uploads.is_email_sent',
                'uploads.update',
                'uploads.insert',
                'uploads.node'
            )
            ->join('user_contacts', 'uploads.security_user_id', '=', 'user_contacts.security_user_id')
            ->join('contact_types', 'user_contacts.contact_type_id', '=', 'contact_types.id')
       
            ;
            if(env('APP_ENV') == 'stage'){
                $query->where('contact_types.contact_option_id', '=', 5)
                ->where('contact_types.name', '=', 'TestEmail');
            }else{
                $query->where('contact_types.contact_option_id', '!=', 5);
            }
            
        $files = $query->limit(1)->get()->toArray();     
        $node = $this->argument('node');
        if (!empty($files)) {
            DB::beginTransaction();
            DB::table('uploads')
                ->where('id', $files[0]['id'])
                ->update(['is_processed' => 3, 'updated_at' => now(), 'node' => $node]);
            DB::commit();
        }
        return $files;
    }

    protected function checkTime()
    {
        $time = Carbon::now()->tz('Asia/Colombo');
        $morning = Carbon::create($time->year, $time->month, $time->day, env('CRON_START_TIME', 0), 29, 0)->tz('Asia/Colombo')->setHour(0); //set time to 05:59

        $evening = Carbon::create($time->year, $time->month, $time->day, env('CRON_END_TIME', 0), 30, 0)->tz('Asia/Colombo')->setHour(23); //set time to 18:00

        $check = $time->between($morning, $evening, true);
        return true;
    }

    public function processSuccessEmail($file, $user, $subject)
    {
        $file['subject'] = $subject;
        $this->output->writeln('Processing the file: ' . $file['filename']);
        try {
            Mail::to($user->email)->send(new StudentImportSuccess($file));
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 1, 'is_email_sent' => 1, 'updated_at' => now()]);
        } catch (\Exception $ex) {
            $this->output->writeln($ex->getMessage());
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 1, 'is_email_sent' => 2, 'updated_at' => now()]);
        }
    }

    public function processFailedEmail($file, $user, $subject)
    {
        $file['subject'] = $subject;
        try {
            Mail::to($user->email)->send(new StudentImportFailure($file));
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'is_email_sent' => 1, 'updated_at' => now()]);
        } catch (\Exception $ex) {
            $this->output->writeln($ex->getMessage());
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'is_email_sent' => 2, 'updated_at' => now()]);
        }
    }

    public function processEmptyEmail($file, $user, $subject)
    {
        $file['subject'] = $subject;
        try {
            Mail::to($user->email)->send(new EmptyFile($file));
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'is_email_sent' => 1, 'updated_at' => now()]);
        } catch (\Exception $ex) {
            $this->output->writeln($ex->getMessage());
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'is_email_sent' => 2, 'updated_at' => now()]);
        }
    }

    protected function checkNode($file)
    {
        $node = $this->argument('node');
        if ($node == $file['node']) {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $this->output->writeln('Processing from:' . $node);
            return true;
        } else {
            exit;
            return false;
        }
    }

    protected function processSheet($file)
    {
        $this->startTime = Carbon::now()->tz('Asia/Colombo');
        $user = User::find($file['security_user_id']);
        $this->checkNode($file);
        $this->output->writeln('##########################################################################################################################');
        $this->output->writeln('Processing the file: ' . $file['filename']);
        if ($this->checkTime()) {
            try {
                $this->import($file, 1, 'C');
                sleep(10);
                $this->import($file, 2, 'B');
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $this->output->writeln($e->getMessage());
                try {
                    Mail::to($user->email)->send(new IncorrectTemplate($file));
                    DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 2, 'is_email_sent' => 1, 'updated_at' => now()]);
                } catch (\Exception $ex) {
                    $this->output->writeln($e->getMessage());
                    $this->handle();
                    DB::table('uploads')
                        ->where('id', $file['id'])
                        ->update(['is_processed' => 2, 'is_email_sent' => 2, 'updated_at' => now()]);
                }
            }
        } else {
            exit();
        }
    }

    protected function getType($file)
    {
        $file =  storage_path() . '/app/sis-bulk-data-files/' . $file;
        $inputFileType =  \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        return $inputFileType;
    }


    protected function getSheetWriter($file, $reader)
    {
        switch ($this->getType($file['filename'])) {
            case 'Xlsx':
                return new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($reader);
                break;
            case 'Ods':
                return new \PhpOffice\PhpSpreadsheet\Writer\Ods($reader);
                break;
            default:
                return new \PhpOffice\PhpSpreadsheet\Writer\Xls($reader);
                break;
        }
    }

    protected function getSheetType($file)
    {
        switch ($this->getType($file)) {
            case 'Xlsx':
                return \Maatwebsite\Excel\Excel::XLSX;
                break;
            case 'Ods':
                return \Maatwebsite\Excel\Excel::ODS;
                break;
            case 'Xml':
                return \Maatwebsite\Excel\Excel::XML;
                break;
            default:
                return \Maatwebsite\Excel\Excel::XLS;
                break;
        }
    }


    protected function getSheetCount($file)
    {
        $objPHPExcel = $this->setReader($file);
        return $objPHPExcel->getSheetCount();
    }



    /**
     * @param $file
     * @param $sheet
     * @param $column
     */
    protected function import($file, $sheet, $column)
    {
        try {
            ini_set('memory_limit', '2048M');
            $this->getFileSize($file);
            $user = User::find($file['security_user_id']);
            $excelFile = '/sis-bulk-data-files/' . $file['filename'];
            $this->highestRow = $this->getHighestRow($file, $sheet, $column);
            switch ($sheet) {
                case 1;
                    $this->output->writeln('Trying to insert Students');
                    if (($this->getSheetName($file, 'Insert Students')) && $this->highestRow > 0) { //
                        $import = new UsersImport($file);
                        $import->import($excelFile, 'local', $this->getSheetType($file['filename']));
                        //                            Excel::import($import, $excelFile, 'local');
                        DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['insert' => 1, 'is_processed' => 1, 'updated_at' => now()]);
                        if ($import->failures()->count() > 0) {
                            self::writeErrors($import, $file, 'Insert Students');
                            DB::table('uploads')
                                ->where('id', $file['id'])
                                ->update(['insert' => 3, 'updated_at' => now()]);
                            $this->processFailedEmail($file, $user, 'Fresh Student Data Upload:Partial Success ');
                            $this->stdOut('Insert Students', $this->highestRow);
                        } else {
                            DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['insert' => 1, 'updated_at' => now()]);
                            $this->processSuccessEmail($file, $user, 'Fresh Student Data Upload:Success ');
                            $this->stdOut('Insert Students', $this->highestRow);
                        }
                    } else if (($this->getSheetName($file, 'Insert Students')) && $this->highestRow > 0) {
                        DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['is_processed' => 2]);
                        $this->processEmptyEmail($file, $user, 'Fresh Student Data Upload ');
                    }
                    break;
                case 2;
                    $this->output->writeln('Trying to update Students');
                    if (($this->getSheetName($file, 'Update Students')) && $this->highestRow > 0) {
                        $import = new StudentUpdate($file);
                        $import->import($excelFile, 'local', $this->getSheetType($file['filename']));
                        if ($import->failures()->count() > 0) {
                            self::writeErrors($import, $file, 'Update Students');
                            DB::table('uploads')
                                ->where('id', $file['id'])
                                ->update(['update' => 3, 'is_processed' => 1, 'updated_at' => now()]);
                            $this->processFailedEmail($file, $user, 'Existing Student Data Update:Partial Success ');
                            $this->stdOut('Update Students', $this->highestRow);
                        } else {
                            DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['update' => 1, 'is_processed' => 1, 'updated_at' => now()]);
                            $this->processSuccessEmail($file, $user, 'Existing Student Data Update:Success ');
                            $this->stdOut('Update Students', $this->highestRow);
                        }
                    } else if (($this->getSheetName($file, 'Update Students')) && $this->highestRow == 0) {
                        DB::table('uploads')
                            ->where('id', $file['id'])
                            ->update(['is_processed' => 2, 'updated_at' => now()]);
                        $this->processEmptyEmail($file, $user, 'Existing Student Data Update');
                    }
                    break;
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $this->output->writeln($e->getMessage());
            if ($sheet == 1) {
                self::writeErrors($e, $file, 'Insert Students');
                DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['insert' => 2, 'updated_at' => now()]);
                $this->processFailedEmail($file, $user, 'Fresh Student Data Upload:Failed');
            } else if ($sheet == 2) {
                self::writeErrors($e, $file, 'Update Students');
                DB::table('uploads')
                    ->where('id', $file['id'])
                    ->update(['update' => 2, 'updated_at' => now()]);
                $this->processFailedEmail($file, $user, 'Existing Student Data Update:Failed');
            }
            DB::table('uploads')
                ->where('id',  $file['id'])
                ->update(['is_processed' => 2, 'updated_at' => now()]);
        }
    }

    protected function processErrors($failure)
    {
        $error_mesg = implode(',', $failure->errors());
        $failure = [
            'row' => $failure->row(),
            'errors' => [$error_mesg],
            'attribute' => $failure->attribute()
        ];
        return $failure;
    }

    protected function  getFileSize($file)
    {
        $excelFile =  '/sis-bulk-data-files/' . $file['filename'];
        $size = Storage::disk('local')->size($excelFile);
        $user = User::find($file['security_user_id']);
        if ($size > 0) {
            return true;
        } else {
            DB::table('uploads')
                ->where('id',  $file['id'])
                ->update(['is_processed' => 2, 'updated_at' => now()]);
            $this->stdOut('No valid data found :Please re-upload the file', 0);
            $this->processEmptyEmail($file, $user, 'No valid data found :Please re-upload the file');
        }
    }

    protected function setReader($file)
    {
        try {
            $excelFile =  '/sis-bulk-data-files/processed/' . $file['filename'];
            $exists = Storage::disk('local')->exists($excelFile);
            if (!$exists) {

                $excelFile =  "/sis-bulk-data-files/" . $file['filename'];
            }
            $excelFile = storage_path() . "/app" . $excelFile;
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($this->getType($file['filename']));
            $objPHPExcel =  $reader->load($excelFile);
            return $objPHPExcel;
        } catch (Exception $e) {
            $this->output->writeln($e->getMessage());
            $user = User::find($file['security_user_id']);
            DB::table('uploads')
                ->where('id',  $file['id'])
                ->update(['is_processed' => 2, 'updated_at' => now()]);
            $this->stdOut('No valid data found :Please re-upload the file', 0);
            $this->processEmptyEmail($file, $user, 'No valid data found :Please re-upload the file');
        }
    }

    protected function  getSheetName($file, $sheet)
    {
        try {;
            $objPHPExcel = $this->setReader($file);
            return $objPHPExcel->getSheetByName($sheet)  !== null;
        } catch (Exception $e) {
            $this->output->writeln($e->getMessage());
            $user = User::find($file['security_user_id']);
            DB::table('uploads')
                ->where('id',  $file['id'])
                ->update(['is_processed' => 2, 'updated_at' => now()]);
            $this->stdOut('No valid data found :Please re-upload the file', 0);
            $this->processEmptyEmail($file, $user, 'No valid data found :Please re-upload the file');
        }
    }

    protected function getHighestRow($file, $sheet, $column)
    {
        try {
            $reader = $this->setReader($file);
            $highestRowCount = 0;
            $sheet =  $reader->getSheet($sheet);
            $highestRow = $sheet->getHighestDataRow($column); 
            for ($row = 3; $row <= $highestRow; $row++){ 
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $column . $row,NULL,TRUE,FALSE);
                if(isEmptyRow(reset($rowData))) { continue; } // skip empty row
                $highestRowCount += 1;
            }
            return  $highestRowCount;
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            $user = User::find($file['security_user_id']);
            DB::beginTransaction();
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'updated_at' => now()]);
            DB::commit();
            $this->processEmptyEmail($file, $user, 'No valid data found');
            exit();
        }
    }

    protected function stdOut($title, $rows)
    {
        $this->output->writeln($title . ' Process completed at . ' . ' ' . now());
        $now = Carbon::now()->tz('Asia/Colombo');
        $this->output->writeln('Total Processed lines: ' . $rows);
        $this->output->writeln('Time taken to process           : ' .   $now->diffInSeconds($this->startTime) . ' Seconds');
        $this->output->writeln('--------------------------------------------------------------------------------------------------------------------------');
    }



    protected function removeRows($row, $count, $params)
    {
        $reader = $params['reader'];
        $sheet = $reader->getActiveSheet();
        if (!in_array($row, $params['rows'])) {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $this->output->writeln(' removing row . ' . ' ' . $row);
            $reader->getActiveSheet()->getCellCollection()->removeRow($row);
        }
    }


    protected function writeErrors($e, $file, $sheet)
    {
        try {
            ini_set('memory_limit', '2048M');
            $baseMemory = memory_get_usage();
            gc_enable();
            gc_collect_cycles();
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            ini_set('memory_limit', -1);
            $failures = $e->failures();
            $reader = $this->setReader($file);
            $reader->setActiveSheetIndexByName($sheet);

            $failures = gettype($failures) == 'object' ? array_map(array($this, 'processErrors'), iterator_to_array($failures)) : array_map(array($this, 'processErrors'), ($failures));
            if (count($failures) > 0) {
                $rows = array_map('rows', $failures);
                $rows = array_unique($rows);
                $rowIndex =   range(3, $this->highestRow + 2);
                $params = [
                    'rows' => $rows,
                    'reader' => $reader
                ];
                array_walk($failures, 'append_errors_to_excel', $reader);
                array_walk($rowIndex, array($this, 'removeRows'), $params);
                $objWriter = $this->getSheetWriter($file, $reader);
                Storage::disk('local')->makeDirectory('sis-bulk-data-files/processed');
                $objWriter->save(storage_path() . '/app/sis-bulk-data-files/processed/' . $file['filename']);
                $now = Carbon::now()->tz('Asia/Colombo');
                $this->output->writeln($reader->getActiveSheet()->getTitle() . ' Process completed at . ' . ' ' . now());
                $this->output->writeln('memory usage for the processes : ' . (memory_get_usage() - $baseMemory));
                $this->output->writeln('Time taken to process           : ' .   $now->diffInSeconds($this->startTime) . ' Seconds');
                $this->output->writeln(' errors reported               : ' . count($failures));
                $this->output->writeln('--------------------------------------------------------------------------------------------------------------------------');
                unset($objWriter);
                unset($failures);
            }
        } catch (Eception $e) {
            $this->output->writeln($e->getMessage());
            $user = User::find($file['security_user_id']);
            DB::beginTransaction();
            DB::table('uploads')
                ->where('id', $file['id'])
                ->update(['is_processed' => 2, 'updated_at' => now()]);
            DB::commit();
            $this->processEmptyEmail($file, $user, 'No valid data found');
            exit();
        }
    }
}
