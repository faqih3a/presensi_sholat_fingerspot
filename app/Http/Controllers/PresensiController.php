<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use App\Actions\Presensi\UpdatePresensiAction;
use App\Actions\Presensi\DeletePresensiAction;
use Carbon\Carbon;

/**
 * Controller untuk manajemen data Presensi (kehadiran sholat).
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima dan memvalidasi HTTP Request.
 * 2. Mendelegasikan logika bisnis ke Action Class yang sesuai.
 * 3. Mengembalikan HTTP Response (JSON atau redirect).
 *
 * Logika bisnis telah dipindahkan ke:
 * @see \App\Actions\Presensi\StorePresensiAction   (digunakan di webhook store.php)
 * @see \App\Actions\Presensi\UpdatePresensiAction
 * @see \App\Actions\Presensi\DeletePresensiAction
 */
class PresensiController extends Controller
{
    /**
     * API endpoint for polling: returns recent scan records.
     * Used by the kehadiran page to auto-detect new webhook scans.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
            'data'        => $records,
            'server_time' => now()->toISOString(),
        ]);
    }

    /**
     * Update status kehadiran santri (manual oleh admin).
     *
     * @param  \Illuminate\Http\Request                       $request
     * @param  \App\Actions\Presensi\UpdatePresensiAction     $action
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, UpdatePresensiAction $action)
    {
        $validated = $request->validate([
            'santri_id'    => 'required|exists:santris,id',
            'tanggal'      => 'required|date',
            'waktu_sholat' => 'required|string',
            'status'       => 'required|in:Hadir,Izin,Alfa',
        ]);

        $presensi = $action->execute($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status kehadiran berhasil diperbarui.',
                'data'    => [
                    'santri_id'    => $presensi->santri_id,
                    'tanggal'      => $presensi->tanggal,
                    'waktu_sholat' => $presensi->waktu_sholat,
                    'status'       => $presensi->status,
                    'waktu_hadir'  => $presensi->waktu_hadir,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Status kehadiran berhasil diperbarui.');
    }

    /**
     * Menghapus satu record presensi berdasarkan model binding.
     *
     * @param  \App\Models\Presensi                           $presensi
     * @param  \App\Actions\Presensi\DeletePresensiAction     $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Presensi $presensi, DeletePresensiAction $action)
    {
        $action->execute($presensi);

        return redirect()->back()->with('success', 'Data kehadiran berhasil dihapus.');
    }

    /**
     * Menghapus presensi berdasarkan parameter santri_id + tanggal + waktu_sholat.
     *
     * @param  \Illuminate\Http\Request                       $request
     * @param  \App\Actions\Presensi\DeletePresensiAction     $action
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function deleteByParams(Request $request, DeletePresensiAction $action)
    {
        $validated = $request->validate([
            'santri_id'    => 'required|exists:santris,id',
            'tanggal'      => 'required|date',
            'waktu_sholat' => 'required|string',
        ]);

        $deleted = $action->executeByParams(
            (int) $validated['santri_id'],
            $validated['tanggal'],
            $validated['waktu_sholat']
        );

        if ($request->ajax() || $request->wantsJson()) {
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Data presensi berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        return redirect()->back()->with('success', 'Data presensi berhasil dihapus.');
    }

    /**
     * Menghapus banyak record presensi sekaligus (bulk delete).
     *
     * @param  \Illuminate\Http\Request                       $request
     * @param  \App\Actions\Presensi\DeletePresensiAction     $action
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkDelete(Request $request, DeletePresensiAction $action)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:presensis,id',
        ]);

        $deletedCount = $action->executeBulk($validated['ids']);

        if ($request->ajax() || $request->wantsJson()) {
            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => $deletedCount . ' data presensi berhasil dihapus.',
                ]);
            }
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        return redirect()->back()->with('success', $deletedCount . ' data presensi berhasil dihapus.');
    }
}
