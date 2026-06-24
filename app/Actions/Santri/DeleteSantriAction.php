<?php

namespace App\Actions\Santri;

use App\Models\Santri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Aksi: Menghapus Data Santri
 *
 * Class ini bertanggung jawab untuk menghapus record Santri beserta
 * akun User terkait dan file foto referensi dari storage.
 * Proses dilakukan dalam database transaction untuk menjamin
 * konsistensi data.
 *
 * Alur Proses:
 * 1. Menghapus file foto referensi dari storage (jika ada).
 * 2. Menghapus record Santri dari database.
 * 3. Menghapus record User yang terkait (jika ada).
 *
 * @see \App\Http\Controllers\SantriController::destroy()
 */
class DeleteSantriAction
{
    /**
     * Menjalankan aksi penghapusan data santri.
     *
     * @param  \App\Models\Santri  $santri  Instance Santri yang akan dihapus.
     * @return void
     *
     * @throws \Throwable  Jika terjadi kegagalan dalam proses database transaction.
     */
    public function execute(Santri $santri): void
    {
        DB::transaction(function () use ($santri) {
            // 1. Hapus file foto dari storage
            if ($santri->foto_referensi && Storage::disk('public')->exists('santri_fotos/' . $santri->foto_referensi)) {
                Storage::disk('public')->delete('santri_fotos/' . $santri->foto_referensi);
            }

            // 2. Simpan referensi user sebelum santri dihapus
            $user = $santri->user;

            // 3. Hapus record santri
            $santri->delete();

            // 4. Hapus akun user terkait (jika ada)
            if ($user) {
                $user->delete();
            }
        });
    }
}
