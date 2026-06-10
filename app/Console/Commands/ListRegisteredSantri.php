<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Santri;

class ListRegisteredSantri extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:cek-mesin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melihat daftar santri yang telah terdaftar di mesin Fingerspot (memiliki data biometrik)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== PENGECEKAN DATA SANTRI DI MESIN FINGERSPOT ===");
        
        $santris = Santri::orderBy('id', 'asc')->get();

        if ($santris->isEmpty()) {
            $this->warn("Belum ada data santri di database lokal.");
            return 0;
        }

        $headers = ['PIN / ID', 'Nama Santri', 'Kelas', 'Status Mesin', 'Detail Biometrik'];
        $tableData = [];

        $totalRegistered = 0;
        $totalNotRegistered = 0;

        foreach ($santris as $santri) {
            $hasFace = $santri->face_count > 0;
            $hasFinger = $santri->finger_count > 0;
            $isRegistered = $hasFace || $hasFinger;

            if ($isRegistered) {
                $statusMesin = 'TERDAFTAR';
                $totalRegistered++;
                
                $details = [];
                if ($hasFace) {
                    $details[] = 'Wajah';
                }
                if ($hasFinger) {
                    $details[] = 'Jari (' . $santri->finger_count . ')';
                }
                $detailBiometrik = implode(', ', $details);
            } else {
                $statusMesin = 'BELUM TERDAFTAR';
                $totalNotRegistered++;
                $detailBiometrik = '-';
            }

            // Gunakan tag warna bawaan Laravel Console untuk pewarnaan status
            $coloredStatus = $isRegistered 
                ? '<info>TERDAFTAR</info>' 
                : '<error>BELUM TERDAFTAR</error>';

            $tableData[] = [
                $santri->id,
                $santri->nama,
                $santri->kelas,
                $coloredStatus,
                $detailBiometrik
            ];
        }

        $this->table($headers, $tableData);

        $this->line("");
        $this->info("=== RINGKASAN ===");
        $this->line("Total Santri di Database  : " . $santris->count());
        $this->line("Terdaftar di Mesin (Aktif): " . $totalRegistered);
        $this->line("Belum Terdaftar di Mesin  : " . $totalNotRegistered);
        $this->line("======================================");

        return 0;
    }
}
