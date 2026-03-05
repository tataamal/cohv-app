<?php
// SUPAYA TIDAK VERBOSE
//stdbuf -oL -eL php artisan --no-ansi schedule:work 2>&1 | grep -v -E 'No scheduled commands are ready to run|^\s*$|^stdout is not a tty$'
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncWiConfirmedQtyJob;

// --- Scheduler Email WI Harian (07:00 Pagi) ---
Schedule::command('wi:send-log-email')
    ->dailyAt('07:00')
    ->timezone('Asia/Jakarta');

// --- Scheduler Email WI Weekly (Senin 07:00) ---
Schedule::command('wi:send-weekly-email')
    ->weeklyOn(1, '07:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/wi-weekly.log'));

Schedule::command('wi:calculate-daily-time')
    ->dailyAt('01:00') 
    ->timezone('Asia/Jakarta');

Schedule::job(new SyncWiConfirmedQtyJob)
    ->hourly()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
