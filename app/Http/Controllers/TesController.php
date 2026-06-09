<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use App\Models\Santri;
use Carbon\Carbon;

class TesController extends Controller
{
    public function index(Request $request)
    {
        $santris = Santri::orderBy('nama')->get();
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        // Get test attendance records
        $query = Presensi::with('santri')
            ->where('waktu_sholat', 'Tes')
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        if ($request->filled('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

        if ($request->filled('search')) {
            $query->whereHas('santri', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            });
        }

        $presensis = $query->paginate(20)->withQueryString();

        return view('tes.index', compact('santris', 'presensis', 'today'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|exists:santris,id',
            'tanggal' => 'required|date',
            'waktu_hadir' => 'required',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $presensi = Presensi::create([
            'santri_id' => $request->santri_id,
            'tanggal' => $request->tanggal,
            'waktu_sholat' => 'Tes',
            'waktu_hadir' => $request->waktu_hadir,
            'status' => 'Hadir',
        ]);

        return redirect()->back()->with('success', 'Presensi tes berhasil dicatat untuk ' . $presensi->santri->nama);
    }

    public function destroy(Presensi $presensi)
    {
        if ($presensi->waktu_sholat !== 'Tes') {
            return redirect()->back()->withErrors(['error' => 'Hanya bisa menghapus data presensi tes.']);
        }
        
        $presensi->delete();
        return redirect()->back()->with('success', 'Data presensi tes berhasil dihapus.');
    }
}
