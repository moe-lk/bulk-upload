<?php

namespace App\Console\Commands;

use App\Institution_shift;
use Illuminate\Console\Command;

class CloneConfigData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clone:config {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone configuration data for new year';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->shifts = new Institution_shift();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $d = $this->shifts->query()->find()->first();
        dd($d);
    }
}
