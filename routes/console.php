<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalankan pengecekan alfa otomatis setiap menit
Schedule::command('presensi:sync-alfa-event')->everyMinute();

// ─── Data Retention Policy ──────────────────────────────────────────
// Hapus data presensi & izin yang sudah lebih dari 90 hari.
// Dijalankan setiap hari jam 01:00 dini hari (jam sepi) agar tidak
// mengganggu performa aplikasi saat digunakan Santri/Asatidz.
// ─────────────────────────────────────────────────────────────────────
Schedule::command('app:clean-old-data')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/data-retention.log'));
