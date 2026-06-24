<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Santri;
use App\Actions\Santri\CreateSantriAction;
use App\Actions\Santri\UpdateSantriAction;
use App\Actions\Santri\DeleteSantriAction;
use App\Actions\Santri\SyncSantriFromMesinAction;

/**
 * Controller untuk manajemen data Santri.
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertanggung jawab untuk:
 * 1. Menerima dan memvalidasi HTTP Request.
 * 2. Mendelegasikan logika bisnis ke Action Class yang sesuai.
 * 3. Mengembalikan HTTP Response (view, JSON, atau redirect).
 *
 * Semua logika bisnis (create, update, delete, sync) telah dipindahkan
 * ke folder `app/Actions/Santri/`.
 *
 * @see \App\Actions\Santri\CreateSantriAction
 * @see \App\Actions\Santri\UpdateSantriAction
 * @see \App\Actions\Santri\DeleteSantriAction
 * @see \App\Actions\Santri\SyncSantriFromMesinAction
 */
class SantriController extends Controller
{
    /**
     * Menampilkan halaman form registrasi santri baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('santri.register');
    }

    /**
     * Menyimpan data santri baru ke database.
     *
     * Validasi request dilakukan di sini, lalu logika penyimpanan
     * didelegasikan ke CreateSantriAction.
     *
     * @param  \Illuminate\Http\Request          $request
     * @param  \App\Actions\Santri\CreateSantriAction  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, CreateSantriAction $action)
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'kelas'          => 'required|string|max:50',
            'email'          => 'required|string|email|max:255|unique:users',
            'password'       => 'required|string|min:5',
            'foto_referensi' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Sertakan file upload ke dalam validated data
        $validated['foto_referensi'] = $request->file('foto_referensi');

        $santri = $action->execute($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data santri dan akun berhasil didaftarkan.',
            'data'    => $santri,
        ]);
    }

    /**
     * Mengembalikan seluruh data santri dalam format JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $santris = Santri::all();
        return response()->json($santris);
    }

    /**
     * Menampilkan halaman daftar santri untuk admin dengan fitur
     * pencarian dan filter berdasarkan kelas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
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


    /**
     * Menampilkan halaman form edit santri.
     *
     * @param  \App\Models\Santri  $santri
     * @return \Illuminate\View\View
     */
    public function edit(Santri $santri)
    {
        return view('santri.edit', compact('santri'));
    }

    /**
     * Memperbarui data santri yang sudah ada.
     *
     * Validasi request dilakukan di sini, lalu logika update
     * didelegasikan ke UpdateSantriAction.
     *
     * @param  \Illuminate\Http\Request               $request
     * @param  \App\Models\Santri                     $santri
     * @param  \App\Actions\Santri\UpdateSantriAction  $action
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Santri $santri, UpdateSantriAction $action)
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'kelas'          => 'required|string|max:50',
            'foto_referensi' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Sertakan file upload jika ada
        if ($request->hasFile('foto_referensi')) {
            $validated['foto_referensi'] = $request->file('foto_referensi');
        }

        $santri = $action->execute($santri, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data santri berhasil diperbarui.',
                'data'    => $santri,
            ]);
        }

        return redirect()->route('santri.index')->with('success', 'Data santri berhasil diperbarui.');
    }

    /**
     * Menghapus data santri beserta akun user terkait.
     *
     * Logika penghapusan didelegasikan ke DeleteSantriAction.
     *
     * @param  \App\Models\Santri                     $santri
     * @param  \App\Actions\Santri\DeleteSantriAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Santri $santri, DeleteSantriAction $action)
    {
        $action->execute($santri);

        return redirect()->route('santri.index')->with('success', 'Data santri berhasil dihapus.');
    }

    /**
     * Sinkronisasi data santri dari mesin absensi Fingerspot.
     *
     * Logika sinkronisasi didelegasikan ke SyncSantriFromMesinAction.
     *
     * @param  \Illuminate\Http\Request                        $request
     * @param  \App\Actions\Santri\SyncSantriFromMesinAction   $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncMesin(Request $request, SyncSantriFromMesinAction $action)
    {
        try {
            $result = $action->execute();

            $statusCode = $result['success'] ? 200 : ($result['status'] ?? 400);
            unset($result['status']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
