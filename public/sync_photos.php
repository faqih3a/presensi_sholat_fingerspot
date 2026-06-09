<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Santri;
use App\Models\Presensi;

$santris = Santri::where('foto_referensi', '')->orWhereNull('foto_referensi')->get();
$updated = 0;
foreach ($santris as $santri) {
    $latestPresensi = Presensi::where('santri_id', $santri->id)->whereNotNull('photo_url')->orderBy('waktu_scan', 'desc')->first();
    if ($latestPresensi) {
        $santri->foto_referensi = $latestPresensi->photo_url;
        $santri->save();
        $updated++;
    }
}
echo "Success: Updated {$updated} photos.\n";
