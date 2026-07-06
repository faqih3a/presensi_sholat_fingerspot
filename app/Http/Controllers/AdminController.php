<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Actions\Admin\CreateAdminAction;
use App\Actions\Admin\UpdateAdminAction;
use App\Actions\Admin\DeleteAdminAction;

/**
 * Controller untuk manajemen akun Admin.
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima dan memvalidasi HTTP Request.
 * 2. Mendelegasikan logika bisnis ke Action Class.
 * 3. Mengembalikan HTTP Response (view atau redirect).
 *
 * Action Classes yang digunakan bersifat role-agnostic, sehingga
 * logika CRUD identik antara AdminController dan AsatidzController
 * tanpa duplikasi kode.
 *
 * @see \App\Actions\Admin\CreateAdminAction
 * @see \App\Actions\Admin\UpdateAdminAction
 * @see \App\Actions\Admin\DeleteAdminAction
 */
class AdminController extends Controller
{
    /**
     * Menampilkan daftar semua admin.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $admins = User::where('role', 'admin')->latest()->get();
        $totalAdmins = $admins->count();
        return view('admin_manage.index', compact('admins', 'totalAdmins'));
    }

    /**
     * Menyimpan akun admin baru.
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

        $action->execute($validated, 'admin');

        return redirect()->route('admin-manage.index')->with('success', 'Akun Admin berhasil dibuat.');
    }



    /**
     * Memperbarui data admin.
     *
     * @param  \Illuminate\Http\Request                  $request
     * @param  \App\Models\User                          $admin
     * @param  \App\Actions\Admin\UpdateAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $admin, UpdateAdminAction $action)
    {
        if ($admin->role !== 'admin') {
            return redirect()->route('admin-manage.index')->with('error', 'User bukan merupakan Admin.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'wa_number' => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:5|confirmed',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        $action->execute($admin, $validated);

        return redirect()->route('admin-manage.index')->with('success', 'Data Admin berhasil diperbarui.');
    }

    /**
     * Menghapus akun admin dengan safeguard keamanan.
     *
     * @param  \App\Models\User                          $admin
     * @param  \App\Actions\Admin\DeleteAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $admin, DeleteAdminAction $action)
    {
        $result = $action->execute($admin, 'admin');

        $type = $result['success'] ? 'success' : 'error';
        return redirect()->route('admin-manage.index')->with($type, $result['message']);
    }
}
