<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal untuk sinkronisasi COHV
// ->twiceDaily(3, 15) akan berjalan pada jam 03:00 (pagi) dan 15:00 (sore).
Schedule::command('app:sync_cohv')
        //  ->twiceDaily(3, 15)
        ->everyMinute()
         ->withoutOverlapping();

// Jadwal untuk sinkronisasi GR
// ->dailyAt('02:00') akan berjalan setiap hari tepat pada jam 02:00 (dini hari).
Schedule::command('app:sync_gr')
        //  ->dailyAt('02:00')
        ->everyMinute()
         ->withoutOverlapping();