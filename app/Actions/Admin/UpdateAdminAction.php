<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Aksi: Memperbarui Profil Akun Staff (Admin / Asatidz)
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
     * @param  array             $validatedData  Data tervalidasi: 'name', 'email', 'wa_number', 'password'?, 'avatar'?.
     * @return \App\Models\User
     */
    public function execute(User $user, array $validatedData): User
    {
        $data = [
            'name'      => $validatedData['name'],
            'email'     => $validatedData['email'],
            'wa_number' => $validatedData['wa_number'] ?? null,
        ];

        if (!empty($validatedData['password'])) {
            $data['password'] = Hash::make($validatedData['password']);
        }

        if (!empty($validatedData['avatar'])) {
            // Hapus avatar lama
            if ($user->avatar && file_exists(public_path('storage/avatars/' . $user->avatar))) {
                @unlink(public_path('storage/avatars/' . $user->avatar));
            }
            $file     = $validatedData['avatar'];
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/avatars'), $filename);
            $data['avatar'] = $filename;
        }

        $user->update($data);

        return $user->fresh();
    }
}
