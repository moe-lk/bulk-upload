<?php

namespace App\Console\Commands;

use App\Http\Controllers\DashboardViewsController;
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
       $views = new DashboardViewsController();
       $views->callback();
    }
}
