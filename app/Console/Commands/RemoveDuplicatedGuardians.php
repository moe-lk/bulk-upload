<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student_guardian;
use Illuminate\Support\Facades\DB;

class RemoveDuplicatedGuardians extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:guardian';

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
        try {
            $this->start_time = microtime(TRUE);
            $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $this->output->writeln('############### Starting delete Duplication ################');
            Student_guardian::withTrashed()->restore();
            $this->delete();
            $this->end_time  = microtime(TRUE);
            $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        } catch (\Throwable $th) {
        }
    }

    public function delete(){
       try{
           DB::statement("UPDATE  student_guardians t1
           INNER JOIN student_guardians t2 
               set t1.deleted_at=now() 
           WHERE 
               t1.id > t2.id AND
               t1.student_id = t2.student_id AND
               t1.guardian_id = t2.guardian_id");
       }catch(\Exception $e){
           dd($e);
       }
    }
}
