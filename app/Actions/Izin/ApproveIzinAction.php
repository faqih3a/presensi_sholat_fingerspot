<?php

namespace App\Actions\Izin;

use App\Models\Izin;

/**
 * Aksi: Menyetujui Permohonan Izin
 *
 * Class ini bertanggung jawab untuk mengubah status permohonan izin
 * menjadi "Disetujui" beserta catatan keterangan dari admin/asatidz.
 *
 * @see \App\Http\Controllers\IzinController::updateStatus()
 */
class ApproveIzinAction
{
    /**
     * Menjalankan aksi persetujuan izin.
     *
     * @param  \App\Models\Izin  $izin              Instance izin yang akan disetujui.
     * @param  string|null       $keteranganAdmin    Catatan opsional dari admin/asatidz.
     * @return \App\Models\Izin  Instance izin yang sudah ter-update.
     */
    public function execute(Izin $izin, ?string $keteranganAdmin = null): Izin
    {
        $izin->update([
            'status'           => 'Disetujui',
            'keterangan_admin' => $keteranganAdmin,
        ]);

        return $izin->fresh();
    }
}
