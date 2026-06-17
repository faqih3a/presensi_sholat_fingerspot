<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Santri;
use App\Models\User;

class SantriController extends Controller
{
    public function create()
    {
        return view('santri.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:5',
            'foto_referensi' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = $request->file('foto_referensi')->store('santri_fotos', 'public');
        $fileName = basename($imagePath);

        // Buat akun User untuk santri
        $user = User::create([
            'name' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'santri',
        ]);

        $santri = Santri::create([
            'user_id' => $user->id,
            'nama' => $request->nama,
            'kelas' => $request->kelas,
            'foto_referensi' => $fileName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data santri dan akun berhasil didaftarkan.',
            'data' => $santri
        ]);
    }

    public function index()
    {
        $santris = Santri::all();
        return response()->json($santris);
    }

    public function adminList(Request $request)
    {
        $search = $request->input('search');
        $kelas = $request->input('kelas');

        $query = Santri::query();

        $query->when($search, function ($q) use ($search) {
            return $q->where('nama', 'like', '%' . $search . '%');
        });

        $query->when($kelas, function ($q) use ($kelas) {
            return $q->where('kelas', $kelas);
        });

        $santris = $query->orderBy('nama', 'asc')->paginate(15)->withQueryString();

        return view('santri.index', compact('santris'));
    }

    /**
     * API endpoint: Returns santri data as JSON for AJAX table refresh.
     * Used by the sync mesin feature to reload table without full page reload.
     */
    public function apiList(Request $request)
    {
        $search = $request->input('search');
        $kelas = $request->input('kelas');
        $page = $request->input('page', 1);

        $query = Santri::with('user');

        $query->when($search, function ($q) use ($search) {
            return $q->where('nama', 'like', '%' . $search . '%');
        });

        $query->when($kelas, function ($q) use ($kelas) {
            return $q->where('kelas', $kelas);
        });

        $santris = $query->orderBy('nama', 'asc')->paginate(15)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $santris->map(function ($santri) {
                return [
                    'id'             => $santri->id,
                    'nama'           => $santri->nama,
                    'email'          => $santri->user->email ?? '-',
                    'kelas'          => $santri->kelas,
                    'foto'           => $santri->display_photo,
                    'face_count'     => $santri->face_count ?? 0,
                    'finger_count'   => $santri->finger_count ?? 0,
                    'created_at'     => $santri->created_at->format('d M Y'),
                    'created_time'   => $santri->created_at->format('H:i'),
                    'edit_url'       => route('santri.edit', $santri),
                    'delete_url'     => route('santri.destroy', $santri),
                ];
            }),
            'pagination' => [
                'current_page' => $santris->currentPage(),
                'last_page'    => $santris->lastPage(),
                'per_page'     => $santris->perPage(),
                'total'        => $santris->total(),
                'first_item'   => $santris->firstItem(),
                'last_item'    => $santris->lastItem(),
            ],
            'total_santri' => Santri::count(),
        ]);
    }


    public function edit(Santri $santri)
    {
        return view('santri.edit', compact('santri'));
    }

    public function update(Request $request, Santri $santri)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'foto_referensi' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'nama' => $request->nama,
            'kelas' => $request->kelas,
        ];

        if ($request->hasFile('foto_referensi')) {
            if ($santri->foto_referensi && Storage::disk('public')->exists('santri_fotos/' . $santri->foto_referensi)) {
                Storage::disk('public')->delete('santri_fotos/' . $santri->foto_referensi);
            }
            $imagePath = $request->file('foto_referensi')->store('santri_fotos', 'public');
            $data['foto_referensi'] = basename($imagePath);
        }

        $santri->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data santri berhasil diperbarui.',
                'data' => $santri
            ]);
        }

        return redirect()->route('santri.index')->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Santri $santri)
    {
        if ($santri->foto_referensi && Storage::disk('public')->exists('santri_fotos/' . $santri->foto_referensi)) {
            Storage::disk('public')->delete('santri_fotos/' . $santri->foto_referensi);
        }

        $user = $santri->user;
        $santri->delete();
        
        if ($user) {
            $user->delete();
        }
        return redirect()->route('santri.index')->with('success', 'Data santri berhasil dihapus.');
    }
}
