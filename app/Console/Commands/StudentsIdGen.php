<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;
use Mohamednizar\MoeUuid\MoeUuid;

class StudentsIdGen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:idgen {offset}';

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
            ->offset($this->argument('offset'))
            ->get()->toArray();
        $this->output->writeln('no of students'.count($students));
        $this->output->writeln('Update started');
        array_walk($students,array($this,'updateNewUUID'));
        $this->end_time = microtime(TRUE);
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
        if(!MoeUuid::isValidMoeUuid(3)){
            $newId = MoeUuid::getUniqueAlphanumeric(3);
            $this->output->writeln('Updating student:'.$student['id']);
            Security_user::query()->where('id',$student['id'])
                ->update(['openemis_no' => $newId]);
        }
    }
}
