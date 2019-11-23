<?php

namespace App\Console\Commands;

use App\Mail\IncorrectTemplate;
use App\Mail\TerminatedReport;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessTerminatedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:terminated';

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
        $files = $this->getTerminated();
            try {
                if(!empty($files)){
                    $this->process($files);
                    unset($files);
                    exit();

                }else{
                    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                    $output->writeln('No files found,Waiting for files');
                    exit();

                }

            }catch (Exception $e){
                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln($e);
                sleep(300);
                $this->handle();

            }

    }

    protected function getTerminated() {
        $files = Upload::where('is_processed', '=', 3)
            ->limit(40)
            ->get()->toArray();
        return $files;
    }

    protected function processSheet($file){
        $this->startTime = Carbon::now()->tz('Asia/Colombo');
        $user = User::find($file['security_user_id']);
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln('##########################################################################################################################');
        $output->writeln('Processing the file: '.$file['filename']);
        Mail::to($user->email)->send(new TerminatedReport($file));
    }



}
