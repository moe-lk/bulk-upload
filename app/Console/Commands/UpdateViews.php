<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;

class UpdateViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:views';

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
        //Total number of students by institutions
        //In Grafana query to get total students count `select total from students_count  where institution_id = $id`
        $query = DB::table('institution_students')
        ->select('institution_id', DB::raw('count(*) as total'))
        ->distinct(['institution_id,student_id,academic_period_id'])
        ->groupBy('institution_students.institution_id'); 
        Schema::createOrReplaceView('students_count', $query);

        //Todo implement other views for school dashboards
    }
}
