<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// ===================================================
// CRON JOB DI cPANEL HOSTINGER CUKUP SATU:
// * * * * * /usr/local/bin/php /path/to/artisan schedule:run >> /dev/null 2>&1
//
// Laravel Scheduler akan otomatis menjalankan semua tugas di bawah ini.
// ===================================================

// 1. Proses antrian email (queue) setiap menit — --stop-when-empty agar aman di shared hosting
Schedule::command('queue:work database --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/worker.log'))
    ->runInBackground();

// 2. Jalankan otomatis setiap tanggal 25 jam 00:00 (Generate tagihan SPP bulanan)
Schedule::command('spp:auto-generate')->monthlyOn(25, '00:00');

