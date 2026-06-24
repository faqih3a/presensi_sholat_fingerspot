<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Actions\Admin\CreateAdminAction;
use App\Actions\Admin\UpdateAdminAction;
use App\Actions\Admin\DeleteAdminAction;

/**
 * Controller untuk manajemen akun Asatidz (Pengurus Masjid).
 *
 * Controller ini menggunakan Action Classes yang sama dengan AdminController
 * (CreateAdminAction, UpdateAdminAction, DeleteAdminAction) karena logika
 * CRUD-nya identik — hanya dibedakan oleh parameter role ('asatidz').
 *
 * @see \App\Actions\Admin\CreateAdminAction
 * @see \App\Actions\Admin\UpdateAdminAction
 * @see \App\Actions\Admin\DeleteAdminAction
 */
class AsatidzController extends Controller
{
    /**
     * Menampilkan daftar semua asatidz.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $asatidz = User::where('role', 'asatidz')->latest()->get();
        $totalAsatidz = $asatidz->count();
        return view('asatidz.index', compact('asatidz', 'totalAsatidz'));
    }

    /**
     * Menyimpan akun asatidz baru.
     *
     * @param  \Illuminate\Http\Request                  $request
     * @param  \App\Actions\Admin\CreateAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, CreateAdminAction $action)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'wa_number' => 'nullable|string|max:20',
            'password'  => 'required|string|min:5|confirmed',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        $action->execute($validated, 'asatidz');

        return redirect()->route('asatidz.index')->with('success', 'Akun Asatidz berhasil dibuat.');
    }

    /**
     * Menampilkan halaman edit asatidz.
     *
     * @param  \App\Models\User  $asatidz
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(User $asatidz)
    {
        if ($asatidz->role !== 'asatidz') {
            return redirect()->route('asatidz.index')->with('error', 'User bukan merupakan Asatidz.');
        }
        return view('asatidz.edit', compact('asatidz'));
    }

    /**
     * Memperbarui data asatidz.
     *
     * @param  \Illuminate\Http\Request                  $request
     * @param  \App\Models\User                          $asatidz
     * @param  \App\Actions\Admin\UpdateAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $asatidz, UpdateAdminAction $action)
    {
        if ($asatidz->role !== 'asatidz') {
            return redirect()->route('asatidz.index')->with('error', 'User bukan merupakan Asatidz.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $asatidz->id,
            'wa_number' => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:5|confirmed',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        $action->execute($asatidz, $validated);

        return redirect()->route('asatidz.index')->with('success', 'Data Asatidz berhasil diperbarui.');
    }

    /**
     * Menghapus akun asatidz dengan safeguard keamanan.
     *
     * @param  \App\Models\User                          $asatidz
     * @param  \App\Actions\Admin\DeleteAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $asatidz, DeleteAdminAction $action)
    {
        $result = $action->execute($asatidz, 'asatidz');

        $type = $result['success'] ? 'success' : 'error';
        return redirect()->route('asatidz.index')->with($type, $result['message']);
    }
}
