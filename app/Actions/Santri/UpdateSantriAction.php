<?php

namespace App\Actions\Santri;

use App\Models\Santri;
use Illuminate\Support\Facades\Storage;

/**
 * Aksi: Memperbarui Data Santri
 *
 * Class ini bertanggung jawab untuk memperbarui informasi profil santri,
 * termasuk menangani pergantian foto referensi (menghapus file lama
 * dari storage dan menyimpan file baru).
 *
 * Alur Proses:
 * 1. Menyiapkan data yang akan di-update (nama, kelas).
 * 2. Jika ada foto baru, hapus foto lama dari storage lalu simpan yang baru.
 * 3. Update record Santri di database.
 *
 * @see \App\Http\Controllers\SantriController::update()
 */
class UpdateSantriAction
{
    /**
     * Menjalankan aksi update data santri.
     *
     * @param  \App\Models\Santri  $santri          Instance Santri yang akan di-update.
     * @param  array               $validatedData   Data yang sudah divalidasi oleh controller.
     *   Berisi: 'nama', 'kelas', dan opsional 'foto_referensi' (UploadedFile|null).
     * @return \App\Models\Santri  Instance Santri yang sudah ter-update.
     */
    public function execute(Santri $santri, array $validatedData): Santri
    {
        $data = [
            'nama'  => $validatedData['nama'],
            'kelas' => $validatedData['kelas'],
        ];

        // Jika ada file foto baru, ganti foto lama
        if (isset($validatedData['foto_referensi']) && $validatedData['foto_referensi'] !== null) {
            $this->replacePhoto($santri, $validatedData['foto_referensi']);
            $imagePath = $validatedData['foto_referensi']->store('santri_fotos', 'public');
            $data['foto_referensi'] = basename($imagePath);
        }

        $santri->update($data);

        return $santri->fresh();
    }

    /**
     * Menghapus foto referensi lama dari storage jika ada.
     *
     * @param  \App\Models\Santri                    $santri  Santri yang fotonya akan diganti.
     * @param  \Illuminate\Http\UploadedFile|null     $newPhoto  File foto baru (unused, hanya untuk validasi keberadaan).
     * @return void
     */
    private function replacePhoto(Santri $santri, $newPhoto): void
    {
        if ($santri->foto_referensi && Storage::disk('public')->exists('santri_fotos/' . $santri->foto_referensi)) {
            Storage::disk('public')->delete('santri_fotos/' . $santri->foto_referensi);
        }
    }
}
