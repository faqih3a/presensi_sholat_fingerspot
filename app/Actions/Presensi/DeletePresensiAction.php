<?php

namespace App\Actions\Presensi;

use App\Models\Presensi;

/**
 * Aksi: Menghapus Data Presensi
 *
 * Class ini menangani tiga jenis penghapusan presensi:
 * 1. **Single delete** — Hapus satu record berdasarkan model instance.
 * 2. **Delete by params** — Hapus berdasarkan kombinasi santri_id + tanggal + waktu_sholat.
 * 3. **Bulk delete** — Hapus banyak record sekaligus berdasarkan array ID.
 *
 * @see \App\Http\Controllers\PresensiController::destroy()
 * @see \App\Http\Controllers\PresensiController::deleteByParams()
 * @see \App\Http\Controllers\PresensiController::bulkDelete()
 */
class DeletePresensiAction
{
    /**
     * Menghapus satu record presensi berdasarkan model instance.
     *
     * @param  \App\Models\Presensi  $presensi  Record yang akan dihapus.
     * @return bool  True jika berhasil dihapus.
     */
    public function execute(Presensi $presensi): bool
    {
        return (bool) $presensi->delete();
    }

    /**
     * Menghapus record presensi berdasarkan kombinasi parameter unik.
     *
     * @param  int     $santriId     ID santri.
     * @param  string  $tanggal      Tanggal presensi (Y-m-d).
     * @param  string  $waktuSholat  Waktu sholat.
     * @return int  Jumlah record yang berhasil dihapus (0 jika tidak ditemukan).
     */
    public function executeByParams(int $santriId, string $tanggal, string $waktuSholat): int
    {
        return Presensi::where('santri_id', $santriId)
            ->where('tanggal', $tanggal)
            ->where('waktu_sholat', $waktuSholat)
            ->delete();
    }

    /**
     * Menghapus banyak record presensi sekaligus berdasarkan array ID.
     *
     * @param  array  $ids  Array berisi ID-ID presensi yang akan dihapus.
     * @return int  Jumlah record yang berhasil dihapus.
     */
    public function executeBulk(array $ids): int
    {
        return Presensi::whereIn('id', $ids)->delete();
    }
}
