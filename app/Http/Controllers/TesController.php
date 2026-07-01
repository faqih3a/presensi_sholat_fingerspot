<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use App\Actions\Attendance\FetchTesDataAction;
use App\Actions\Attendance\ExportTesCsvAction;
use Illuminate\Support\Facades\Cache;

/**
 * Controller untuk Presensi Tes (di luar waktu sholat).
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima HTTP Request.
 * 2. Mendelegasikan logika bisnis ke Action Class yang sesuai.
 * 3. Mengembalikan HTTP Response (view, stream, atau redirect).
 *
 * @see \App\Actions\Attendance\FetchTesDataAction
 * @see \App\Actions\Attendance\ExportTesCsvAction
 */
class TesController extends Controller
{
    /**
     * Menampilkan halaman daftar presensi tes.
     *
     * @param  \Illuminate\Http\Request                          $request
     * @param  \App\Actions\Attendance\FetchTesDataAction        $action
     * @return \Illuminate\View\View
     */
    public function index(Request $request, FetchTesDataAction $action)
    {
        $data = $action->execute($request);
        return view('tes.index', $data);
    }

    /**
     * Menghapus satu record presensi tes.
     *
     * Hanya presensi dengan waktu_sholat 'Tes' yang boleh dihapus
     * melalui endpoint ini (guard clause).
     *
     * @param  \App\Models\Presensi  $presensi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Presensi $presensi)
    {
        if ($presensi->waktu_sholat !== 'Tes') {
            return redirect()->back()->withErrors(['error' => 'Hanya bisa menghapus data presensi tes.']);
        }

        $presensi->delete();
        return redirect()->back()->with('success', 'Data presensi tes berhasil dihapus.');
    }

    /**
     * Export data presensi tes ke format CSV.
     *
     * @param  \Illuminate\Http\Request                          $request
     * @param  \App\Actions\Attendance\ExportTesCsvAction        $action
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportTes(Request $request, ExportTesCsvAction $action)
    {
        $export = $action->execute($request);
        return response()->stream($export['callback'], 200, $export['headers']);
    }

    /**
     * Toggle aktif/nonaktif halaman dan pencatatan Tes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(Request $request)
    {
        $enabled = $request->input('enabled') == '1';
        Cache::forever('tes_page_enabled', $enabled);

        $statusStr = $enabled ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Halaman dan pencatatan Tes berhasil {$statusStr}.");
    }
}
