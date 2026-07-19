<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;

/**
 * Aksi: Menghapus Akun Staff (Admin / Ustadz)
 *
 * Safeguard: validasi role + prevent self-deletion.
 *
 * @see \App\Http\Controllers\AdminController::destroy()
 * @see \App\Http\Controllers\UstadzController::destroy()
 */
class DeleteAdminAction
{
    /**
     * Menjalankan aksi penghapusan akun staff.
     *
     * @param  \App\Models\User  $user           User yang akan dihapus.
     * @param  string            $expectedRole   Role yang diharapkan ('admin' atau 'ustadz').
     * @return array  ['success' => bool, 'message' => string]
     */
    public function execute(User $user, string $expectedRole = 'admin'): array
    {
        if ($user->role !== $expectedRole) {
            return ['success' => false, 'message' => 'User bukan merupakan ' . ucfirst($expectedRole) . '.'];
        }

        if ($user->id === Auth::id()) {
            return ['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
        }

        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        $user->delete();

        return ['success' => true, 'message' => 'Akun ' . ucfirst($expectedRole) . ' berhasil dihapus.'];
    }
}
