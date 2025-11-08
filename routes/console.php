<?php
// SUPAYA TIDAK VERBOSE
//stdbuf -oL -eL php artisan --no-ansi schedule:work 2>&1 | grep -v -E 'No scheduled commands are ready to run|^\s*$|^stdout is not a tty$'
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// --- Jadwal untuk YPPR079 (Sudah Ada & Synchronous) ---
Schedule::command('syncronize:cohv-data')
    ->dailyAt('02.00')
    ->timezone('Asia/Jakarta')
    ->before(function () {
        echo now()->format('Y-m-d H:i:s') . ' Running ["artisan" yppr:sync]' . PHP_EOL;
    });



// 🌟 --- Jadwal YSDR048 (Diubah menjadi Synchronous) ---
Schedule::command('syncronize:gr-data')
    ->dailyAt('02.00')
    ->timezone('Asia/Jakarta')
    ->before(function () {
        echo now()->format('Y-m-d H:i:s') . ' Running ["artisan" stock:sync]' . PHP_EOL;
    })
    ->withoutOverlapping();

// 🌟 --- Jadwal YSDR048 (Diubah menjadi Synchronous) ---
Schedule::command('syncronize:cogi-data')
    ->cron('0 */2 * * *')
    ->timezone('Asia/Jakarta')
    ->before(function () {
        echo now()->format('Y-m-d H:i:s') . ' Running ["artisan" stock:sync]' . PHP_EOL;
    })
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
