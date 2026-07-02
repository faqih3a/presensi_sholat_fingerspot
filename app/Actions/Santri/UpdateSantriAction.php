<?php

namespace App\Actions\Santri;

use App\Models\Santri;
use Illuminate\Support\Facades\Storage;

/**
 * Aksi: Memperbarui Data Santri
 *
 * @see \App\Http\Controllers\SantriController::update()
 */
class UpdateSantriAction
{
    /**
     * Menjalankan aksi update data santri.
     *
     * @param  \App\Models\Santri  $santri         Instance Santri yang akan di-update.
     * @param  array               $validatedData  Data tervalidasi: 'nama', 'kelas', 'foto_referensi'?.
     * @return \App\Models\Santri
     */
    public function execute(Santri $santri, array $validatedData): Santri
    {
        $data = [
            'nama'  => $validatedData['nama'],
            'kelas' => $validatedData['kelas'],
        ];

        if (!empty($validatedData['foto_referensi'])) {
            // Hapus foto lama
            if ($santri->foto_referensi && Storage::disk('public')->exists('santri_fotos/' . $santri->foto_referensi)) {
                Storage::disk('public')->delete('santri_fotos/' . $santri->foto_referensi);
            }
            $imagePath          = $validatedData['foto_referensi']->store('santri_fotos', 'public');
            $data['foto_referensi'] = basename($imagePath);
        }

        $santri->update($data);

        return $santri->fresh();
    }
}
