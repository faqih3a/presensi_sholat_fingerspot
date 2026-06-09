<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PengurusController extends Controller
{
    public function index()
    {
        $pengurus = User::whereIn('role', ['admin', 'asatidz'])->latest()->get();
        return view('pengurus.index', compact('pengurus'));
    }

    public function create()
    {
        return view('pengurus.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'wa_number' => 'nullable|string|max:20',
            'role' => 'required|in:admin,asatidz',
            'password' => 'required|string|min:5|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'wa_number' => $request->wa_number,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/avatars'), $filename);
            $user->update(['avatar' => $filename]);
        }

        return redirect()->route('pengurus.index')->with('success', 'Akun Asatidz Masjid berhasil dibuat.');
    }

    public function edit(User $pengurus)
    {
        if (!in_array($pengurus->role, ['admin', 'asatidz'])) {
            return redirect()->route('pengurus.index')->with('error', 'User bukan merupakan pengurus.');
        }
        return view('pengurus.edit', compact('pengurus'));
    }

    public function update(Request $request, User $pengurus)
    {
        if (!in_array($pengurus->role, ['admin', 'asatidz'])) {
            return redirect()->route('pengurus.index')->with('error', 'User bukan merupakan pengurus.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $pengurus->id,
            'wa_number' => 'nullable|string|max:20',
            'role' => 'required|in:admin,asatidz',
            'password' => 'nullable|string|min:5|confirmed',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'wa_number' => $request->wa_number,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $pengurus->update($data);

        return redirect()->route('pengurus.index')->with('success', 'Data Pengurus Masjid berhasil diperbarui.');
    }

    public function destroy(User $pengurus)
    {
        if (!in_array($pengurus->role, ['admin', 'asatidz'])) {
            return redirect()->route('pengurus.index')->with('error', 'User bukan merupakan pengurus.');
        }

        // Prevent admin from deleting themselves
        if ($pengurus->id === auth()->id()) {
            return redirect()->route('pengurus.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $pengurus->delete();

        return redirect()->route('pengurus.index')->with('success', 'Akun Pengurus Masjid berhasil dihapus.');
    }
}
