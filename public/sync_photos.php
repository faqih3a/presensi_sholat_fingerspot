<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();

use App\Models\Santri;
use App\Models\Presensi;

\ = Santri::where('foto_referensi', '')->orWhereNull('foto_referensi')->get();
\ = 0;
foreach (\ as \) {
    \ = Presensi::where('santri_id', \->id)->whereNotNull('photo_url')->orderBy('waktu_scan', 'desc')->first();
    if (\) {
        \->foto_referensi = \->photo_url;
        \->save();
        \++;
    }
}
echo 'Success: Updated ' . \ . ' photos.';
