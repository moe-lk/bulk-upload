<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Exports\ExaminationStudentsExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\ExportReady as NotificationsExportReady;

class NotifyUserCompleteExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            (new ExaminationStudentsExport)->queue('/examination/student_data_with_nsid.csv')->chain([
                $this->user->notify(new NotificationsExportReady($this->user))
            ]);
            
        }catch(\Exception $e){
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln($e->getMessage());
        }
    }
}
