<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Institution_grade;
use PhpParser\Node\Stmt\TryCatch;

class RollbackStudentFromInstitutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admission:rollback {institution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback promoted flag in institution_grades table to 2019';

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
        /*
         * Set back the promoted flag in institution_grades table to 2019.
         * 1. Get all the values of promoted field by each record
         * 2. Assign all the records to an array
         * 3. Loop through all the indexes of the array and check whether the promoted values are equal to 2019
         * 4. If not, set them to 2019
        */

        try {
            /*
             * Getting all the records by corresponding institute_id.
            */
            $this->info('Fetching all the records...');
            $institution_grades = DB::select('select * from institution_grades where institution_id = :id', ['id' => $this->argument('institution')]);

            /*
             * First check whether the array is not empty
             * Then loop though all the records and check the promoted value
             * If the promoted value is equal to 2019 keep it as it is otherwise set it back to 2019
            */
            if (!empty($institution_grades)) {

                $this->info('Fetched ' . count($institution_grades) . ' records');

                foreach ($institution_grades as $institute_grade) {
                    if ($institute_grade->promoted == "2019") {
                        $this->info('Promoted year have already set to 2019');
                    } else {
                        $this->info('Updating record =======================================');
                        DB::update("update institution_grades set promoted ='2019' where institution_id = ?", [$this->argument('institution')]);
                        $this->info('Record updated ========================================');
                    }
                }
            } else {
                $this->info('No results!');
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
