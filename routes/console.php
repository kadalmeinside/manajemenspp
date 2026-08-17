<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Jalankan otomatis setiap tanggal 25 jam 00:00 (Tengah Malam)
Schedule::command('spp:auto-generate')->monthlyOn(25, '00:00');

