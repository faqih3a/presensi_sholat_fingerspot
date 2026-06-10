<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Presensi;
use App\Models\Santri;

$data = Presensi::where('tanggal', '2026-06-10')->get();
echo "Total records on 2026-06-10: " . count($data) . "\n";
foreach ($data as $p) {
    echo "ID: {$p->id}, Santri ID: {$p->santri_id}, Sholat: {$p->waktu_sholat}, Hadir: {$p->waktu_hadir}, Status: {$p->status}\n";
}
