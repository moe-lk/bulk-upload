<?php

namespace App\Console\Commands;

use App\Models\Institution_grade;
use App\Models\Institution_student;
use Illuminate\Console\Command;

class UpdateDoeDataGrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:doe_grade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the student wrong grade from DoE import';

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
        Institution_student::where('updated_from', 'doe')
            ->where('education_grade_id','<>', 4)
            ->update(['education_grade_id' => 4]);
    }
}
