<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AsatidzController extends Controller
{
    public function index()
    {
        $asatidz = User::where('role', 'asatidz')->latest()->get();
        $totalAsatidz = $asatidz->count();
        return view('asatidz.index', compact('asatidz', 'totalAsatidz'));
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
            'role' => 'asatidz',
            'password' => Hash::make($request->password),
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/avatars'), $filename);
            $user->update(['avatar' => $filename]);
        }

        return redirect()->route('asatidz.index')->with('success', 'Akun Asatidz berhasil dibuat.');
    }

    public function edit(User $asatidz)
    {
        if ($asatidz->role !== 'asatidz') {
            return redirect()->route('asatidz.index')->with('error', 'User bukan merupakan Asatidz.');
        }
        return view('asatidz.edit', compact('asatidz'));
    }

    public function update(Request $request, User $asatidz)
    {
        if ($asatidz->role !== 'asatidz') {
            return redirect()->route('asatidz.index')->with('error', 'User bukan merupakan Asatidz.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $asatidz->id,
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
            if ($asatidz->avatar && file_exists(public_path('storage/avatars/' . $asatidz->avatar))) {
                @unlink(public_path('storage/avatars/' . $asatidz->avatar));
            }
            $file = $request->file('avatar');
            $filename = time() . '_' . $asatidz->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/avatars'), $filename);
            $data['avatar'] = $filename;
        }

        $asatidz->update($data);

        return redirect()->route('asatidz.index')->with('success', 'Data Asatidz berhasil diperbarui.');
    }

    public function destroy(User $asatidz)
    {
        if ($asatidz->role !== 'asatidz') {
            return redirect()->route('asatidz.index')->with('error', 'User bukan merupakan Asatidz.');
        }

        // Delete avatar if exists
        if ($asatidz->avatar && file_exists(public_path('storage/avatars/' . $asatidz->avatar))) {
            @unlink(public_path('storage/avatars/' . $asatidz->avatar));
        }

        $asatidz->delete();

        return redirect()->route('asatidz.index')->with('success', 'Akun Asatidz berhasil dihapus.');
    }
}
