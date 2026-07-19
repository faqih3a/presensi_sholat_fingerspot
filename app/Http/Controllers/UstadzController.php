<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Actions\Admin\CreateAdminAction;
use App\Actions\Admin\UpdateAdminAction;
use App\Actions\Admin\DeleteAdminAction;

/**
 * Controller untuk manajemen akun Ustadz (Pengurus Masjid).
 *
 * Controller ini menggunakan Action Classes yang sama dengan AdminController
 * (CreateAdminAction, UpdateAdminAction, DeleteAdminAction) karena logika
 * CRUD-nya identik — hanya dibedakan oleh parameter role ('ustadz').
 *
 * @see \App\Actions\Admin\CreateAdminAction
 * @see \App\Actions\Admin\UpdateAdminAction
 * @see \App\Actions\Admin\DeleteAdminAction
 */
class UstadzController extends Controller
{
    /**
     * Menampilkan daftar semua ustadz.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $ustadz = User::where('role', 'ustadz')
            ->when($search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalUstadz = User::where('role', 'ustadz')->count();

        return view('ustadz.index', compact('ustadz', 'totalUstadz'));
    }

    /**
     * Menyimpan akun ustadz baru.
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

        $action->execute($validated, 'ustadz');

        return redirect()->route('ustadz.index')->with('success', 'Akun Ustadz berhasil dibuat.');
    }



    /**
     * Memperbarui data ustadz.
     *
     * @param  \Illuminate\Http\Request                  $request
     * @param  \App\Models\User                          $ustadz
     * @param  \App\Actions\Admin\UpdateAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $ustadz, UpdateAdminAction $action)
    {
        if ($ustadz->role !== 'ustadz') {
            return redirect()->route('ustadz.index')->with('error', 'User bukan merupakan Ustadz.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $ustadz->id,
            'wa_number' => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:5|confirmed',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        $action->execute($ustadz, $validated);

        return redirect()->route('ustadz.index')->with('success', 'Data Ustadz berhasil diperbarui.');
    }

    /**
     * Menghapus akun ustadz dengan safeguard keamanan.
     *
     * @param  \App\Models\User                          $ustadz
     * @param  \App\Actions\Admin\DeleteAdminAction       $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $ustadz, DeleteAdminAction $action)
    {
        $result = $action->execute($ustadz, 'ustadz');

        $type = $result['success'] ? 'success' : 'error';
        return redirect()->route('ustadz.index')->with($type, $result['message']);
    }
}
