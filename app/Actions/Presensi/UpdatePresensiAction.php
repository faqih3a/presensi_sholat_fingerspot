<?php

namespace App\Actions\Presensi;

use App\Models\Presensi;
use Carbon\Carbon;

/**
 * Aksi: Memperbarui Status Presensi yang Sudah Ada
 *
 * Class ini bertanggung jawab untuk mengupdate status kehadiran
 * santri pada record presensi yang sudah ada. Digunakan oleh admin
 * untuk koreksi manual (misal: mengubah Alfa menjadi Hadir/Izin).
 *
 * Aturan Bisnis:
 * - Jika status diubah ke "Hadir", waktu_hadir diisi waktu saat ini.
 * - Jika status diubah ke "Alfa" atau "Izin", waktu_hadir di-null-kan.
 * - Method `updateOrCreate` digunakan untuk menangani kasus dimana
 *   record belum ada (edge case dari dashboard).
 *
 * @see \App\Http\Controllers\PresensiController::updateStatus()
 */
class UpdatePresensiAction
{
    /**
     * Menjalankan aksi update status presensi.
     *
     * @param  array  $validatedData  Data yang sudah divalidasi. Berisi:
     *   - 'santri_id'    (int): ID santri.
     *   - 'tanggal'      (string): Tanggal presensi (Y-m-d).
     *   - 'waktu_sholat' (string): Nama waktu sholat.
     *   - 'status'       (string): Status baru (Hadir/Izin/Alfa).
     * @return \App\Models\Presensi  Instance presensi yang ter-update.
     */
    public function execute(array $validatedData): Presensi
    {
        return Presensi::updateOrCreate(
            [
                'santri_id'    => (int) $validatedData['santri_id'],
                'tanggal'      => $validatedData['tanggal'],
                'waktu_sholat' => $validatedData['waktu_sholat'],
            ],
            [
                'status'      => $validatedData['status'],
                'waktu_hadir' => $validatedData['status'] === 'Hadir'
                    ? Carbon::now()->format('H:i')
                    : null,
            ]
        );
    }
}
