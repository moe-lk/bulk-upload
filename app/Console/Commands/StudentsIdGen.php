<?php

namespace App\Console\Commands;

use Lsf\UniqueUid\UniqueUid;
use App\Models\Security_user;
use App\Models\Unique_user_id;
use Exception;
use Illuminate\Console\Command;
use Mohamednizar\MoeUuid\MoeUuid;

class StudentsIdGen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:idgen {chunk} {max}';

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
        $this->students = new Security_user();
        $this->uniqueUId = new UniqueUid();
        $this->child_pids = array();
        $this->max  = 0;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->max = $this->argument('max');
        $this->start_time_all = microtime(TRUE);
        $students = $this->students->query()
            ->where('is_student', 1)
            ->limit(500000)
            ->get()->toArray();
        $students = array_chunk($students, $this->argument('chunk'));
        $this->output->writeln('Total students' . count($students));
        array_walk($students, array($this, 'process'));
        $this->end_time = microtime(TRUE);
        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
        $this->output->writeln('The cook took ' . ($this->end_time - $this->start_time_all) . ' seconds to complete');
        $this->output->writeln('$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$');
        exit();
    }



    public function process($students)
    {

        if (count($this->child_pids) >= $this->max) {
            $pid = pcntl_waitpid(-1, $pid);
            $this->output->writeln('Processing PID:' . $pid.'put for waiting');
            unset($this->child_pids[$pid]);
        }else{
            $pid = pcntl_fork();
            if ($pid) {
                if ($pid ==  -1) {
                    die('Coundt fork:'.$pid);
                } elseif(($pid > 0)) {
                    $this->child_pids[] = $pid;
                    $this->output->writeln('Processing PID:' . $pid);
                    $this->output->writeln('Total child processes' . count($this->child_pids));
                    $this->start_time = microtime(TRUE);
                    $this->executeProcess($students);
                    exit(0);
                }
            }
        }
       
        foreach ($this->child_pids as $pid) {
            pcntl_waitpid($pid, $status);
            unset($this->child_pids[$pid]);
        }
    }

    public function executeProcess($students)
    {
        $this->end_time = microtime(TRUE);
        $this->output->writeln('The thread took ' . ($this->end_time - $this->start_time) . ' seconds to complete - '.time());
        array_walk($students, array($this, 'updateNewUUID'));
    }


    /**
     * over right the students id with uuid
     * @param $student
     * @throws \Exception
     */
    public function updateNewUUID($student)
    {
        $this->uniqueUserId = new Unique_user_id();
        if (!$this->uniqueUId::isValidUniqueId($student['openemis_no'])) {
            try {

                $newId = $this->uniqueUId::getUniqueAlphanumeric();
                $student['openemis_no'] = $newId;
                $student =  $this->uniqueUserId->updateOrInsertRecord($student);
                $this->output->writeln('New NSID generated for :' . $student['id']);
                Security_user::query()->where('id', $student['id'])
                    ->update(['openemis_no' => $newId, 'username' => str_replace('-', '', $newId)]);
            } catch (\Exception $e) {
            }
        } else {
            try {
                // $this->output->writeln('Updating student:' . $student['id']);
                $this->uniqueUserId->updateOrInsertRecord($student);
            } catch (\Exception $e) {
                dd($e);
            }
        }
    }
}
