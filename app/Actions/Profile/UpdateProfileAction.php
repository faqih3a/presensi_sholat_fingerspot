<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Aksi: Memperbarui Profil User
 *
 * Class ini bertanggung jawab untuk memperbarui data profil user,
 * termasuk menangani upload avatar dengan logika berbeda untuk
 * santri (foto_referensi) dan admin/ustadz (avatar).
 *
 * Aturan Bisnis:
 * - Avatar santri disimpan di 'storage/santri_fotos/' dan diupdate di tabel santris.
 * - Avatar admin/ustadz disimpan di 'storage/avatars/' dan diupdate di tabel users.
 * - Jika user adalah santri, nama dan kelas juga diupdate di tabel santris.
 *
 * @see \App\Http\Controllers\ProfileController::update()
 */
class UpdateProfileAction
{
    /**
     * Menjalankan aksi update profil user.
     *
     * @param  \App\Models\User       $user           User yang sedang login.
     * @param  array                  $validatedData  Data yang sudah divalidasi. Berisi:
     *   - 'name'      (string): Nama lengkap.
     *   - 'email'     (string): Alamat email.
     *   - 'wa_number' (string|null): Nomor WhatsApp.
     *   - 'kelas'     (string|null): Kelas santri (hanya untuk role santri).
     * @param  UploadedFile|null      $avatarFile     File avatar yang diupload (opsional).
     * @return \App\Models\User  User yang sudah diperbarui.
     */
    public function execute(User $user, array $validatedData, ?UploadedFile $avatarFile = null): User
    {
        $userData = [
            'name'      => $validatedData['name'],
            'email'     => $validatedData['email'],
            'wa_number' => $validatedData['wa_number'] ?? null,
        ];

        // Handle Avatar Upload
        if ($avatarFile) {
            $filename = time() . '_' . $user->id . '.' . $avatarFile->getClientOriginalExtension();

            if ($user->role === 'santri') {
                $avatarFile->move(public_path('storage/santri_fotos'), $filename);
                $user->santri?->update(['foto_referensi' => $filename]);
            } else {
                $avatarFile->move(public_path('storage/avatars'), $filename);
                $userData['avatar'] = $filename;
            }
        }

        $user->update($userData);

        // Sinkronisasi data ke tabel santris jika role santri
        if ($user->role === 'santri' && $user->santri) {
            $user->santri->update([
                'nama'  => $validatedData['name'],
                'kelas' => $validatedData['kelas'] ?? $user->santri->kelas,
            ]);
        }

        return $user;
    }
}
