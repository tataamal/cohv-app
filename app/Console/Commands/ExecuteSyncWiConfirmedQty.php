<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExecuteSyncWiConfirmedQty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:wi-confirmed-qty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SyncWiConfirmedQtyJob...');

        \App\Jobs\SyncWiConfirmedQtyJob::dispatchSync();

        $this->info('Job completed!');
    }
}
