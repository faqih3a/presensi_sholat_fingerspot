<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Santri;
use App\Models\Presensi;
use Illuminate\Support\Facades\Storage;

$santris = Santri::where('foto_referensi', '')->orWhereNull('foto_referensi')->orWhere('foto_referensi', 'like', 'http%')->get();
$updated = 0;
foreach ($santris as $santri) {
    $photoUrl = '';
    
    if (str_starts_with($santri->foto_referensi, 'http')) {
        $photoUrl = $santri->foto_referensi;
    } else {
        $latestPresensi = Presensi::where('santri_id', $santri->id)->whereNotNull('photo_url')->orderBy('waktu_scan', 'desc')->first();
        if ($latestPresensi) {
            $photoUrl = $latestPresensi->photo_url;
        }
    }

    if ($photoUrl) {
        try {
            // Download foto dari link AWS
            $contents = file_get_contents($photoUrl);
            if ($contents) {
                $filename = 'sync_' . $santri->id . '_' . time() . '.jpg';
                Storage::disk('public')->put('santri_fotos/' . $filename, $contents);
                $santri->foto_referensi = $filename;
                $santri->save();
                $updated++;
                echo "Downloaded photo for Santri ID {$santri->id}\n";
            }
        } catch (\Exception $e) {
            echo "Failed to download for Santri ID {$santri->id}: " . $e->getMessage() . "\n";
        }
    }
}
echo "Success: Downloaded and saved {$updated} photos locally.\n";
