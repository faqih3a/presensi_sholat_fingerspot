<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\Profile\UpdateProfileAction;
use App\Actions\Profile\UpdatePasswordAction;

/**
 * Controller untuk manajemen Profil User.
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima dan memvalidasi HTTP Request.
 * 2. Mendelegasikan logika bisnis ke Action Class yang sesuai.
 * 3. Mengembalikan HTTP Response (view atau redirect).
 *
 * @see \App\Actions\Profile\UpdateProfileAction
 * @see \App\Actions\Profile\UpdatePasswordAction
 */
class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil user.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    /**
     * Memperbarui data profil user.
     *
     * @param  \Illuminate\Http\Request                      $request
     * @param  \App\Actions\Profile\UpdateProfileAction      $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, UpdateProfileAction $action)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'wa_number' => ['nullable', 'string', 'max:20'],
            'kelas'     => ['nullable', 'string', 'max:50'],
            'avatar'    => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $action->execute(
            $user,
            $validated,
            $request->file('avatar')
        );

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Memperbarui password user.
     *
     * @param  \Illuminate\Http\Request                       $request
     * @param  \App\Actions\Profile\UpdatePasswordAction      $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request, UpdatePasswordAction $action)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'min:5'],
        ], [
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
            'password.min'                      => 'Password baru minimal harus 5 karakter.',
        ]);

        $action->execute(auth()->user(), $request->password);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
