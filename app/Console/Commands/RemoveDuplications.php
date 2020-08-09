<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is to clean students data';

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
        try {
            DB::statement('DELETE t1 FROM institution_students t1
        INNER JOIN institution_students t2 
        WHERE 
            t1.id < t2.id AND 
            t1.student_id = t2.student_id AND 
            t1.academic_period_id = t2.academic_period_id AND 
            t1.institution_id = t2.institution_id');
        } catch (\Throwable $th) {
            dd($th);
        }
    }
}
