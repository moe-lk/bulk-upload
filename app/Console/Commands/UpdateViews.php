<?php

namespace App\Console\Commands;

use App\Models\DashboardViews;
use Illuminate\Console\Command;

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
        /** In Grafana query to get total students count 
         * `select total from students_count  where institution_id = $id`
         * `select male from students_count  where institution_id = $id`
         * `select female from students_count  where institution_id = $id`
        **/
        DashboardViews::createOrUpdateStudentCount();
        DashboardViews::createOrUpdateStudentList();
        DashboardViews::createOrUpdateUploadList();
        DashboardViews::createOrUpdateUploadCount();
        DashboardViews::createOrUpdateInstitutionInfo();
    }
}
