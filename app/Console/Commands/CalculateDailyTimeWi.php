<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SendTotalTimeController;

class CalculateDailyTimeWi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wi:calculate-daily-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store daily total WI time per NIK for H-1';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Daily Time Calculation...');

        try {
            $controller = app(SendTotalTimeController::class);
            $response = $controller->calculateAndStoreDailyTime();
            
            // Controller returns JsonResponse, so we extract data
            $data = $response->getData(true);

            if ($data['success']) {
                $this->info($data['message']);
                $this->info("Records Processed: " . $data['records_processed']);
            } else {
                $this->error('Failed: ' . $data['message']);
            }

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
