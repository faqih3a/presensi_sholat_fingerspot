<?php

namespace App\Actions\Santri;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Aksi: Mendaftarkan Santri Baru
 *
 * Class ini bertanggung jawab untuk membuat record Santri baru beserta
 * akun User yang terkait. Proses ini dilakukan dalam satu database
 * transaction untuk menjamin konsistensi data.
 *
 * Alur Proses:
 * 1. Menyimpan file foto referensi ke storage.
 * 2. Membuat record User dengan role 'santri'.
 * 3. Membuat record Santri yang ter-link ke User.
 *
 * @see \App\Http\Controllers\SantriController::store()
 */
class CreateSantriAction
{
    /**
     * Menjalankan aksi pembuatan data santri baru.
     *
     * @param  array  $validatedData  Data yang sudah divalidasi oleh controller.
     *   Berisi: 'nama', 'kelas', 'email', 'password', 'foto_referensi' (UploadedFile).
     * @return \App\Models\Santri  Instance Santri yang baru dibuat.
     *
     * @throws \Throwable  Jika terjadi kegagalan dalam proses database transaction.
     */
    public function execute(array $validatedData): Santri
    {
        return DB::transaction(function () use ($validatedData) {
            // 1. Simpan foto referensi ke disk 'public'
            $imagePath = $validatedData['foto_referensi']->store('santri_fotos', 'public');
            $fileName = basename($imagePath);

            // 2. Buat akun User untuk santri
            $user = User::create([
                'name'     => $validatedData['nama'],
                'email'    => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role'     => 'santri',
            ]);

            // 3. Buat record Santri yang terhubung ke User
            $santri = Santri::create([
                'user_id'        => $user->id,
                'nama'           => $validatedData['nama'],
                'kelas'          => $validatedData['kelas'],
                'foto_referensi' => $fileName,
            ]);

            return $santri;
        });
    }
}
