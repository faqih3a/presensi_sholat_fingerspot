<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use Carbon\Carbon;

class PresensiController extends Controller
{
    public function updateStatus(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|exists:santris,id',
            'tanggal' => 'required|date',
            'waktu_sholat' => 'required|string',
            'status' => 'required|in:Hadir,Izin,Alfa',
        ]);

        $presensi = Presensi::updateOrCreate([
            'santri_id' => $request->santri_id,
            'tanggal' => $request->tanggal,
            'waktu_sholat' => $request->waktu_sholat,
        ], [
            'status' => $request->status,
            'waktu_hadir' => $request->status === 'Hadir' ? Carbon::now()->format('H:i') : null,
        ]);

        return redirect()->back()->with('success', 'Status kehadiran berhasil diperbarui.');
    }

    public function destroy(Presensi $presensi)
    {
        $presensi->delete();
        return redirect()->back()->with('success', 'Data kehadiran berhasil dihapus.');
    }

    public function deleteByParams(Request $request)
    {
        $request->validate([
            'santri_id' => 'required|exists:santris,id',
            'tanggal' => 'required|date',
            'waktu_sholat' => 'required|string',
        ]);

        Presensi::where('santri_id', $request->santri_id)
                ->where('tanggal', $request->tanggal)
                ->where('waktu_sholat', $request->waktu_sholat)
                ->delete();

        return redirect()->back()->with('success', 'Data kehadiran berhasil dihapus.');
    }
}
