<?php

namespace App\Console\Commands;

use App\Models\Institution_grade;
use Illuminate\Console\Command;

class CallPromotionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promote:run {year}';

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
        $this->instituion_grade = new Institution_grade();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $year = $this->argument('year');
        $institution = $this->instituion_grade->getInstitutionGradeList($year);
        $this->call('promote:students', ['year' => $year, 'institution' => $institution->code]);
    }
}
