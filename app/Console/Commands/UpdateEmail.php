<?php

namespace App\Console\Commands;

use App\Models\Security_user;
use Illuminate\Console\Command;

class UpdateEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:email {username} {email}';

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
        Security_user::where(['username'=>$this->argument('username')])
        ->update(['email' => $this->argument('email')]);
    }
}
