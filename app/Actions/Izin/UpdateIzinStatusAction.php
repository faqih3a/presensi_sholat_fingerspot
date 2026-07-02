<?php

namespace App\Actions\Izin;

use App\Models\Izin;

/**
 * Aksi: Memperbarui Status Permohonan Izin (Disetujui / Ditolak)
 *
 * @see \App\Http\Controllers\IzinController::updateStatus()
 */
class UpdateIzinStatusAction
{
    /**
     * Menjalankan aksi update status izin.
     *
     * @param  \App\Models\Izin  $izin              Instance izin yang akan diupdate.
     * @param  string            $status            Status baru: 'Disetujui' atau 'Ditolak'.
     * @param  string|null       $keteranganAdmin   Catatan/alasan dari admin.
     * @return \App\Models\Izin  Instance izin yang sudah ter-update.
     */
    public function execute(Izin $izin, string $status, ?string $keteranganAdmin = null): Izin
    {
        $izin->update([
            'status'           => $status,
            'keterangan_admin' => $keteranganAdmin,
        ]);

        return $izin->fresh();
    }
}
