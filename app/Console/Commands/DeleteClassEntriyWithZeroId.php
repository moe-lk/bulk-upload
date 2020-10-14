<?php

namespace App\Console\Commands;

use App\Models\Institution_class_student;
use Illuminate\Console\Command;

class DeleteClassEntriyWithZeroId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:zero_id_class';

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
        Institution_class_student::where('institution_class_id',0)->delete();
    }
}
