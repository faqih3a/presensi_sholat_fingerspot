<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use Carbon\Carbon;

class PresensiController extends Controller
{
    /**
     * API endpoint for polling: returns recent scan records.
     * Used by the kehadiran page to auto-detect new webhook scans.
     */
    public function latestScans(Request $request)
    {
        $since = $request->get('since');
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        $query = Presensi::with('santri')
            ->where('tanggal', $today)
            ->where('status', 'Hadir')
            ->whereNotNull('waktu_hadir');

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        $records = $query->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id'           => $p->id,
                    'santri_id'    => $p->santri_id,
                    'nama'         => $p->santri ? $p->santri->nama : 'PIN ' . $p->santri_id,
                    'kelas'        => $p->santri ? $p->santri->kelas : '-',
                    'foto'         => $p->santri && $p->santri->foto_referensi
                        ? asset('storage/santri_fotos/' . $p->santri->foto_referensi) : null,
                    'waktu_sholat' => $p->waktu_sholat,
                    'waktu_hadir'  => $p->waktu_hadir,
                    'tanggal'      => $p->tanggal,
                    'status'       => $p->status,
                    'photo_url'    => $p->photo_url,
                    'updated_at'   => $p->updated_at->toISOString(),
                ];
            });

        return response()->json([
            'data'       => $records,
            'server_time' => now()->toISOString(),
        ]);
    }

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status kehadiran berhasil diperbarui.',
                'data' => [
                    'santri_id' => $presensi->santri_id,
                    'tanggal' => $presensi->tanggal,
                    'waktu_sholat' => $presensi->waktu_sholat,
                    'status' => $presensi->status,
                    'waktu_hadir' => $presensi->waktu_hadir,
                ],
            ]);
        }

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

        $deleted = Presensi::where('santri_id', $request->santri_id)
                ->where('tanggal', $request->tanggal)
                ->where('waktu_sholat', $request->waktu_sholat)
                ->delete();

        if ($request->ajax() || $request->wantsJson()) {
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Data presensi berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        return redirect()->back()->with('success', 'Data presensi berhasil dihapus.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:presensis,id',
        ]);

        $deletedCount = Presensi::whereIn('id', $request->ids)->delete();

        if ($request->ajax() || $request->wantsJson()) {
            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true, 
                    'message' => $deletedCount . ' data presensi berhasil dihapus.'
                ]);
            }
            return response()->json([
                'success' => false, 
                'message' => 'Data tidak ditemukan.'
            ], 404);
        }

        return redirect()->back()->with('success', $deletedCount . ' data presensi berhasil dihapus.');
    }
}

