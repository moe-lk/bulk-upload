<?php

namespace App\Console\Commands;

use App\Imports\UsersImport;
use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
    protected $description = 'Command description';

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

        try{
            $files = Upload::where('is_processed','=',0)->get()->toArray();
            foreach ($files as $file){
                $import = new UsersImport($file);
                $excelFile = storage_path().'/app/sis-bulk-data-files/'.$file['filename'];

                Excel::import($import,$excelFile);
                $file['is_processed'] = 1;
                Upload::updating($file);


            }

        }catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            dd($e);
            $failures = $e->failures();

            foreach ($failures as $failure) {
                $failure->row(); // row that went wrong
                $failure->attribute(); // either heading key (if using heading row concern) or column index
                $failure->errors(); // Actual error messages from Laravel validator
                $failure->values(); // The values of the row that has failed.
            }
        }

    }
}
