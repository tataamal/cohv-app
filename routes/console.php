<?php
// SUPAYA TIDAK VERBOSE
//stdbuf -oL -eL php artisan --no-ansi schedule:work 2>&1 | grep -v -E 'No scheduled commands are ready to run|^\s*$|^stdout is not a tty$'
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// --- Scheduler Email WI Harian (07:00 Pagi) ---
Schedule::command('wi:send-log-email')
    ->dailyAt('07:00')
    ->timezone('Asia/Jakarta');

// --- Scheduler Email WI Weekly (Senin 07:00) ---
Schedule::command('wi:send-weekly-email')
    ->weeklyOn(1, '07:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(60) // cegah dobel jalan (misal schedule:work restart / overlap)
    ->runInBackground()      // biar scheduler tidak “ketahan” kalau PDF/email lama
    ->appendOutputTo(storage_path('logs/wi-weekly.log'));

// --- Scheduler Hitung Total Time WI Harian (23:45) ---
Schedule::command('wi:calculate-daily-time')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
