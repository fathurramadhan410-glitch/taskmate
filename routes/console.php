<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Penjadwal Tugas (Laravel Scheduler)
|--------------------------------------------------------------------------
| FR-021: sistem memeriksa deadline setiap hari pukul 07.00 WITA.
| Zona waktu aplikasi = Asia/Makassar (WITA) sehingga dailyAt 07:00
| sudah berjalan pada pukul 07.00 WITA.
|
| Untuk menjalankan pada lingkungan lokal (XAMPP):
|   php artisan schedule:work
|
| Untuk server Linux (cron):
|   * * * * * cd /path/ke/taskmate && php artisan schedule:run >> /dev/null 2>&1
*/

Schedule::command('taskmate:check-deadlines')->dailyAt('07:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Tampilkan kutipan inspiratif');
