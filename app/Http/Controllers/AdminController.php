<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')->latest()->get();
        $totalAdmins = $admins->count();
        return view('admin_manage.index', compact('admins', 'totalAdmins'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'wa_number' => 'nullable|string|max:20',
            'password' => 'required|string|min:5|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'wa_number' => $request->wa_number,
            'role' => 'admin',
            'password' => Hash::make($request->password),
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/avatars'), $filename);
            $user->update(['avatar' => $filename]);
        }

        return redirect()->route('admin-manage.index')->with('success', 'Akun Admin berhasil dibuat.');
    }

    public function edit(User $admin)
    {
        if ($admin->role !== 'admin') {
            return redirect()->route('admin-manage.index')->with('error', 'User bukan merupakan Admin.');
        }
        return view('admin_manage.edit', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        if ($admin->role !== 'admin') {
            return redirect()->route('admin-manage.index')->with('error', 'User bukan merupakan Admin.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'wa_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:5|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'wa_number' => $request->wa_number,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($admin->avatar && file_exists(public_path('storage/avatars/' . $admin->avatar))) {
                @unlink(public_path('storage/avatars/' . $admin->avatar));
            }
            $file = $request->file('avatar');
            $filename = time() . '_' . $admin->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/avatars'), $filename);
            $data['avatar'] = $filename;
        }

        $admin->update($data);

        return redirect()->route('admin-manage.index')->with('success', 'Data Admin berhasil diperbarui.');
    }

    public function destroy(User $admin)
    {
        if ($admin->role !== 'admin') {
            return redirect()->route('admin-manage.index')->with('error', 'User bukan merupakan Admin.');
        }

        // Prevent admin from deleting themselves
        if ($admin->id === auth()->id()) {
            return redirect()->route('admin-manage.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Delete avatar if exists
        if ($admin->avatar && file_exists(public_path('storage/avatars/' . $admin->avatar))) {
            @unlink(public_path('storage/avatars/' . $admin->avatar));
        }

        $admin->delete();

        return redirect()->route('admin-manage.index')->with('success', 'Akun Admin berhasil dihapus.');
    }
}
