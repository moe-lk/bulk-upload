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
        $this->max  = 0;
        $this->child = 0;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $students = $this->students->query()
            ->where('is_student', 1)
            ->limit(500000)
            ->get()->toArray();

        $students = array_chunk($students, $this->argument('chunk'));
        $this->processParallel($students, $this->argument('max'));
    }

    public function processParallel(array $arr, $procs = 4)
    {
        // Break array up into $procs chunks.
        $chunks   = array_chunk($arr, ceil((count($arr) / $procs)));
        $pid      = -1;
        $children = array();
        foreach ($chunks as $items) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                die('could not fork');
            } else if ($pid === 0) {
                $this->output->writeln('started processes: ' . count($children));
                // We are the child process. Pass a chunk of items to process.
                array_walk($items, array($this, 'process'));
                exit(0);
            } else {
                // We are the parent.
                $children[] = $pid;
            }
        }
        // Wait for children to finish.
        foreach ($children as $pid) {
            // We are still the parent.
            pcntl_waitpid($pid, $status);
        }
    }



    public function process($students)
    {
        array_walk($students, array($this, 'updateNewUUID'));
    }


    /*
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
                // $this->output->writeln('New NSID generated for :' . $student['id']);
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
