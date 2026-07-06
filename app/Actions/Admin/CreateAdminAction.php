<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Storage;

/**
 * Aksi: Membuat Akun Staff Baru (Admin / Asatidz)
 *
 * @see \App\Http\Controllers\AdminController::store()
 * @see \App\Http\Controllers\AsatidzController::store()
 */
class CreateAdminAction
{
    /**
     * Menjalankan aksi pembuatan akun staff baru.
     *
     * @param  array   $validatedData  Data tervalidasi: 'name', 'email', 'wa_number', 'password', 'avatar'.
     * @param  string  $role           Role: 'admin' atau 'asatidz'.
     * @return \App\Models\User
     */
    public function execute(array $validatedData, string $role = 'admin'): User
    {
        $user = User::create([
            'name'      => $validatedData['name'],
            'email'     => $validatedData['email'],
            'wa_number' => $validatedData['wa_number'] ?? null,
            'role'      => $role,
            'password'  => Hash::make($validatedData['password']),
        ]);

        if (!empty($validatedData['avatar'])) {
            $file     = $validatedData['avatar'];
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->storeAs('avatars', $filename, 'public');
            $user->update(['avatar' => $filename]);
        }

        return $user;
    }
}
