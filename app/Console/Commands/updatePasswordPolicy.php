<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class updatePasswordPolicy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update password policy';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->security_users = new Security_user();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        $output->writeln('##############################################------Updating file records------###########################################');

        DB::table('security_users')
            ->where('is_student', '=', '0')
            ->update(['security_timeout' => '2019-12-01']);

        $output->writeln('################################################------Records Updated------###############################################');
    }
}
