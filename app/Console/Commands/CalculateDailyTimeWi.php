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
    protected $signature = 'wi:calculate-daily-time {date? : The date to process (YYYY-MM-DD)} {--end= : Optional end date for range (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store daily total WI time per NIK for H-1 or custom range';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Run Expiration Check (Point 4)
        // Update status for documents that have expired and are not completed
        $now = \Carbon\Carbon::now();
        $affected = \Illuminate\Support\Facades\DB::table('history_wi')
            ->whereNull('deleted_at')
            ->where('expired_at', '<', $now)
            ->where('status', '!=', 'EXPIRED')
            ->where('status', 'NOT LIKE', '%COMPLETED%')
            ->update(['status' => 'EXPIRED', 'updated_at' => $now]);
            
        if ($affected > 0) {
            $this->info("Updated {$affected} documents to EXPIRED status.");
        }

        $date = $this->argument('date');
        $endDate = $this->option('end');

        // Normalize d-m-Y to Y-m-d if detected
        if ($date && preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
             try {
                 $date = \Carbon\Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
             } catch (\Exception $e) { /* fallback */ }
        }
        if ($endDate && preg_match('/^\d{2}-\d{2}-\d{4}$/', $endDate)) {
             try {
                 $endDate = \Carbon\Carbon::createFromFormat('d-m-Y', $endDate)->format('Y-m-d');
             } catch (\Exception $e) { /* fallback */ }
        }

        $this->info("Starting Daily Time Calculation...");
        if ($date) {
            $this->info("Target Date: $date" . ($endDate ? " to $endDate" : ""));
        } else {
             $this->info("Target Date: Yesterday (Default)");
             $date = \Carbon\Carbon::yesterday()->format('Y-m-d');
        }

        try {
            $controller = app(SendTotalTimeController::class);
            $response = $controller->calculateAndStoreDailyTime($date, $endDate);
            
            // Controller returns JsonResponse, so we extract data
            $data = $response->getData(true);

            if ($data['success']) {
                $this->info($data['message']);
                $this->info("Records Processed: " . $data['records_processed']);
                if (!empty($data['dates_processed'])) {
                    $this->info("Dates Processed: " . implode(', ', $data['dates_processed']));
                }
            } else {
                $this->error('Failed: ' . $data['message']);
            }

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }
}
