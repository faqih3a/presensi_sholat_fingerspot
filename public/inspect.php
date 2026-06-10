<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

header('Content-Type: text/plain');

echo "=== DIAGNOSTICS ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Time: " . Carbon::now()->toDateTimeString() . " (" . config('app.timezone') . ")\n";
echo "Carbon Time (Asia/Jakarta): " . Carbon::now('Asia/Jakarta')->toDateTimeString() . "\n";

echo "\n=== RECENT WEBHOOK LOGS (from Laravel log) ===\n";
$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -50);
    echo implode("", $lastLines);
} else {
    echo "No laravel.log file found.\n";
}

echo "\n=== TODAY'S ATTENDANCE RECORDS (10 Jun 2026) ===\n";
try {
    $records = DB::table('presensis')
        ->where('tanggal', '2026-06-10')
        ->get();
    echo "Total records: " . count($records) . "\n";
    foreach ($records as $r) {
        echo "ID: {$r->id} | Santri ID: {$r->santri_id} | Sholat: {$r->waktu_sholat} | Hadir: {$r->waktu_hadir} | Status: {$r->status} | Created: {$r->created_at}\n";
    }
} catch (\Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\n=== RUNNING SYNC ALFA EVENT COMMAND ===\n";
try {
    $output = '';
    Artisan::call('presensi:sync-alfa-event', [], $output);
    echo "Exit Code: " . Artisan::output() . "\n";
} catch (\Exception $e) {
    echo "Command Error: " . $e->getMessage() . "\n";
}
