<?php

namespace App\Actions\Izin;

use App\Models\Izin;

/**
 * Aksi: Menolak Permohonan Izin
 *
 * Class ini bertanggung jawab untuk mengubah status permohonan izin
 * menjadi "Ditolak" beserta alasan penolakan dari admin/asatidz.
 *
 * @see \App\Http\Controllers\IzinController::updateStatus()
 */
class RejectIzinAction
{
    /**
     * Menjalankan aksi penolakan izin.
     *
     * @param  \App\Models\Izin  $izin              Instance izin yang akan ditolak.
     * @param  string|null       $keteranganAdmin    Alasan penolakan dari admin/asatidz.
     * @return \App\Models\Izin  Instance izin yang sudah ter-update.
     */
    public function execute(Izin $izin, ?string $keteranganAdmin = null): Izin
    {
        $izin->update([
            'status'           => 'Ditolak',
            'keterangan_admin' => $keteranganAdmin,
        ]);

        return $izin->fresh();
    }
}
