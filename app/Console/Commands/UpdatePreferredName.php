<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePreferredName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:preferred_name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing full name into preferred name';

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
        Security_user::where('is_student',true)
        ->whereRaw('CHAR_LENGTH(first_name) <= 90')
        ->update(['preferred_name' =>  DB::raw('first_name')]);
    }
}
