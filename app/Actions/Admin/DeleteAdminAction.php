<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Aksi: Menghapus Akun Staff (Admin / Asatidz)
 *
 * Class ini bertanggung jawab untuk menghapus akun staff dengan
 * safeguard keamanan berikut:
 * 1. **Prevent Self-Deletion**: Admin yang sedang login tidak bisa
 *    menghapus akunnya sendiri untuk menghindari session orphan.
 * 2. **Role Validation**: Memastikan user yang dihapus memang memiliki
 *    role yang diharapkan (mencegah penghapusan user dengan role lain).
 * 3. **Avatar Cleanup**: Menghapus file avatar dari storage sebelum
 *    menghapus record dari database.
 *
 * @see \App\Http\Controllers\AdminController::destroy()
 * @see \App\Http\Controllers\AsatidzController::destroy()
 */
class DeleteAdminAction
{
    /**
     * Menjalankan aksi penghapusan akun staff.
     *
     * @param  \App\Models\User  $user           User yang akan dihapus.
     * @param  string            $expectedRole   Role yang diharapkan ('admin' atau 'asatidz').
     * @return array  Hasil proses: ['success' => bool, 'message' => string].
     *
     * @throws \LogicException  Jika terjadi pelanggaran safeguard.
     */
    public function execute(User $user, string $expectedRole = 'admin'): array
    {
        // Safeguard 1: Validasi role — pastikan user memang memiliki role yang benar
        if ($user->role !== $expectedRole) {
            return [
                'success' => false,
                'message' => "User bukan merupakan " . ucfirst($expectedRole) . ".",
            ];
        }

        // Safeguard 2: Prevent self-deletion — admin tidak bisa hapus dirinya sendiri
        if ($user->id === Auth::id()) {
            return [
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ];
        }

        // 1. Hapus file avatar dari storage
        $this->cleanupAvatar($user);

        // 2. Hapus record user dari database
        $user->delete();

        return [
            'success' => true,
            'message' => 'Akun ' . ucfirst($expectedRole) . ' berhasil dihapus.',
        ];
    }

    /**
     * Menghapus file avatar dari public storage jika ada.
     *
     * @param  \App\Models\User  $user  User yang avatarnya akan dihapus.
     * @return void
     */
    private function cleanupAvatar(User $user): void
    {
        if ($user->avatar && file_exists(public_path('storage/avatars/' . $user->avatar))) {
            @unlink(public_path('storage/avatars/' . $user->avatar));
        }
    }
}
