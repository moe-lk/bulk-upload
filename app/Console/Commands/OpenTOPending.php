<?php

namespace App\Console\Commands;

use App\Models\Institution_student_admission;
use Illuminate\Console\Command;

class OpenTOPending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:status_update';

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
        Institution_student_admission::where('student_status_id',121)->update([
            'student_status_id' => 122
        ]);
    }
    
}
