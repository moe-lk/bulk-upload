<?php

namespace App\Console\Commands;

use App\Models\Institution;
use Illuminate\Console\Command;

class callAddApprovedStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admission:run';

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
        $this->instituions = new Institution();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit','2048');
        $institutions = $this->instituions->all()->chunk(50)->toArray();
        array_walk($institutions,array($this,'addInstitutionStudents'));
    }

    protected function addInstitutionStudents($chunk){
        array_walk($chunk,array($this,'callFunction'));

    }

    protected function callFunction($institution){
        $this->call('admission:students',['institution' => $institution['code']]);
    }
}
