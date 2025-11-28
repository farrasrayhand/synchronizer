<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Aplikasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Silahkan tunggu, sedang proses update aplikasi....');
        $this->call('optimize:clear');
        //exec("git pull origin main");
        exec("git config --global --add safe.directory C:/synchronizer/dataweb");
        exec("git stash");
        exec("git pull https://github.com/masadi/synchronizer.git");
        exec("git clean -df");
        exec("composer update");
    }
}
