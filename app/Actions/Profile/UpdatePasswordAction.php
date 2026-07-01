<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Aksi: Memperbarui Password User
 *
 * Class ini bertanggung jawab untuk mengubah password user
 * setelah verifikasi password lama berhasil dilakukan
 * (verifikasi via Form Request / controller validation).
 *
 * @see \App\Http\Controllers\ProfileController::updatePassword()
 */
class UpdatePasswordAction
{
    /**
     * Menjalankan aksi perubahan password.
     *
     * @param  \App\Models\User  $user         User yang ingin mengubah password.
     * @param  string            $newPassword  Password baru (plain text, akan di-hash).
     * @return void
     */
    public function execute(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }
}
