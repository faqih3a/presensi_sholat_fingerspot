<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Aksi: Memperbarui Profil Akun Staff (Admin / Asatidz)
 *
 * Class ini bertanggung jawab untuk mengupdate data profil akun staff,
 * dengan penanganan password yang cerdas:
 * - Jika password baru diisi → hash dan update.
 * - Jika password kosong → abaikan, password lama tetap aman.
 *
 * Juga menangani pergantian avatar (hapus file lama, simpan file baru).
 *
 * @see \App\Http\Controllers\AdminController::update()
 * @see \App\Http\Controllers\AsatidzController::update()
 */
class UpdateAdminAction
{
    /**
     * Menjalankan aksi update profil staff.
     *
     * @param  \App\Models\User  $user           Instance User yang akan diupdate.
     * @param  array             $validatedData  Data yang sudah divalidasi. Berisi:
     *   - 'name'       (string): Nama lengkap.
     *   - 'email'      (string): Alamat email.
     *   - 'wa_number'  (string|null): Nomor WhatsApp.
     *   - 'password'   (string|null): Password baru (opsional).
     *   - 'avatar'     (\Illuminate\Http\UploadedFile|null): File foto profil baru (opsional).
     * @return \App\Models\User  Instance User yang sudah ter-update.
     */
    public function execute(User $user, array $validatedData): User
    {
        $data = [
            'name'      => $validatedData['name'],
            'email'     => $validatedData['email'],
            'wa_number' => $validatedData['wa_number'] ?? null,
        ];

        // Password handling: hanya update jika diisi (tidak kosong)
        // Ini mencegah password lama tertimpa oleh string kosong
        if (!empty($validatedData['password'])) {
            $data['password'] = Hash::make($validatedData['password']);
        }

        // Avatar handling: ganti file lama dengan file baru
        if (isset($validatedData['avatar']) && $validatedData['avatar'] !== null) {
            $this->replaceAvatar($user, $validatedData['avatar']);
            $filename = time() . '_' . $user->id . '.' . $validatedData['avatar']->getClientOriginalExtension();
            $validatedData['avatar']->move(public_path('storage/avatars'), $filename);
            $data['avatar'] = $filename;
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Menghapus file avatar lama dari storage jika ada.
     *
     * @param  \App\Models\User  $user      User yang avatarnya akan diganti.
     * @param  mixed             $newFile   File baru (hanya untuk validasi keberadaan).
     * @return void
     */
    private function replaceAvatar(User $user, $newFile): void
    {
        if ($user->avatar && file_exists(public_path('storage/avatars/' . $user->avatar))) {
            @unlink(public_path('storage/avatars/' . $user->avatar));
        }
    }
}
