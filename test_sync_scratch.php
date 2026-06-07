<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\Illuminate\Support\Facades\Cache::flush();

$service = app(\App\Services\FingerspotService::class);
$reflector = new \ReflectionClass($service);

$apiUrlProp = $reflector->getProperty('apiUrl');
$apiUrlProp->setAccessible(true);
$apiTokenProp = $reflector->getProperty('apiToken');
$apiTokenProp->setAccessible(true);
$cloudIdProp = $reflector->getProperty('cloudId');
$cloudIdProp->setAccessible(true);

echo "apiUrl: " . $apiUrlProp->getValue($service) . "\n";
echo "apiToken: " . $apiTokenProp->getValue($service) . "\n";
echo "cloudId: " . $cloudIdProp->getValue($service) . "\n";

try {
    $logs = $service->syncAttendance('2026-05-30', '2026-05-31');
    echo "LOGS COUNT: " . count($logs) . "\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

// Print last 10 lines of laravel.log
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "LAST 20 LOG LINES:\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    echo implode("", $lastLines);
} else {
    echo "No laravel.log found.\n";
}

$records = \App\Models\Presensi::whereBetween('tanggal', ['2026-05-30', '2026-05-31'])->get();
foreach ($records as $record) {
    echo "ID: {$record->id}, PIN: {$record->santri->fingerspot_pin}, Sholat: {$record->waktu_sholat}, Hadir: {$record->waktu_hadir}, Verify: {$record->verify_method_label} ({$record->verify}), Status Scan: {$record->status_scan_label} ({$record->status_scan})\n";
}
