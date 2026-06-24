<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Aksi: Membuat Akun Staff Baru (Admin / Asatidz)
 *
 * Class ini bertanggung jawab untuk membuat akun User baru dengan
 * role tertentu (admin/asatidz). Password dienkripsi secara otomatis
 * menggunakan Hash::make() sebelum disimpan ke database.
 *
 * Dirancang role-agnostic agar bisa digunakan oleh AdminController
 * maupun AsatidzController tanpa duplikasi logika.
 *
 * Alur Proses:
 * 1. Hash password input.
 * 2. Buat record User dengan role yang ditentukan.
 * 3. Simpan avatar ke public storage (jika ada).
 *
 * @see \App\Http\Controllers\AdminController::store()
 * @see \App\Http\Controllers\AsatidzController::store()
 */
class CreateAdminAction
{
    /**
     * Menjalankan aksi pembuatan akun staff baru.
     *
     * @param  array   $validatedData  Data yang sudah divalidasi. Berisi:
     *   - 'name'       (string): Nama lengkap.
     *   - 'email'      (string): Alamat email (harus unik).
     *   - 'wa_number'  (string|null): Nomor WhatsApp.
     *   - 'password'   (string): Password plaintext (akan di-hash).
     *   - 'avatar'     (\Illuminate\Http\UploadedFile|null): File foto profil.
     * @param  string  $role  Role yang akan di-assign ('admin' atau 'asatidz').
     * @return \App\Models\User  Instance User yang baru dibuat.
     */
    public function execute(array $validatedData, string $role = 'admin'): User
    {
        // 1. Buat record user dengan password ter-hash
        $user = User::create([
            'name'      => $validatedData['name'],
            'email'     => $validatedData['email'],
            'wa_number' => $validatedData['wa_number'] ?? null,
            'role'      => $role,
            'password'  => Hash::make($validatedData['password']),
        ]);

        // 2. Simpan avatar jika ada
        if (isset($validatedData['avatar']) && $validatedData['avatar'] !== null) {
            $this->storeAvatar($user, $validatedData['avatar']);
        }

        // TODO: Jika menggunakan Spatie Permission, assign role di sini:
        // $user->assignRole($role);

        return $user;
    }

    /**
     * Menyimpan file avatar ke public storage.
     *
     * @param  \App\Models\User                     $user  User pemilik avatar.
     * @param  \Illuminate\Http\UploadedFile        $file  File avatar yang diupload.
     * @return void
     */
    private function storeAvatar(User $user, $file): void
    {
        $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('storage/avatars'), $filename);
        $user->update(['avatar' => $filename]);
    }
}
