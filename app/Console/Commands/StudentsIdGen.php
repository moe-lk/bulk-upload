<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;
use Webpatser\Uuid\Uuid;

class StudentsIdGen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:idgen';

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
        $this->count = 0;
        $this->students = new Security_user();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start_time = microtime(TRUE);
        ini_set('memory_limit', '2048M');
        $students = $this->students->query()
            ->where('is_student',1)
            ->limit(100000)
            ->get()->toArray();
        $this->output->writeln('Update started');
        array_walk($students,array($this,'updateNewUUID'));
        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time) . ' seconds to complete');
        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
    }

    /**
     * over right the students id with uuid
     * @param $student
     * @throws \Exception
     */
    public function updateNewUUID($student){
        if(!Uuid::validate($student['openemis_no'])){
            $this->output->writeln('Updating student:'.$student['id']);
            Security_user::query()->where('id',$student['id'])
                ->update(['openemis_no' => Uuid::generate(4)]);
        }
        $this->end_time = microtime(TRUE);
    }
}
