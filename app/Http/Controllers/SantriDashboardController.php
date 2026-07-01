<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Actions\Dashboard\FetchSantriDashboardAction;
use App\Actions\Dashboard\ExportSantriCsvAction;

/**
 * Controller untuk Dashboard Santri (halaman personal santri).
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima HTTP Request dan memverifikasi profil santri.
 * 2. Mendelegasikan logika bisnis ke Action Class yang sesuai.
 * 3. Mengembalikan HTTP Response (view atau stream).
 *
 * @see \App\Actions\Dashboard\FetchSantriDashboardAction
 * @see \App\Actions\Dashboard\ExportSantriCsvAction
 */
class SantriDashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard personal santri.
     *
     * @param  \Illuminate\Http\Request                               $request
     * @param  \App\Actions\Dashboard\FetchSantriDashboardAction      $action
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request, FetchSantriDashboardAction $action)
    {
        $user = Auth::user();

        // Pastikan user memiliki profil santri terhubung
        if (!$user->santri) {
            return redirect('/')->withErrors(['error' => 'Profil santri tidak ditemukan untuk akun ini.']);
        }

        $waktuSholat = $request->waktu_sholat;
        $period      = $request->get('period', 'today');

        $data = $action->execute($user->santri->id, $period, $waktuSholat);

        return view('santri.dashboard', [
            'riwayatPresensi' => $data['riwayatPresensi'],
            'user'            => $user,
            'totalHadir'      => $data['totalHadir'],
            'totalAlpha'      => $data['totalAlpha'],
            'period'          => $period,
            'waktuSholat'     => $waktuSholat,
        ]);
    }

    /**
     * Export data kehadiran santri ke format CSV.
     *
     * @param  \Illuminate\Http\Request                           $request
     * @param  \App\Actions\Dashboard\ExportSantriCsvAction       $action
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function export(Request $request, ExportSantriCsvAction $action)
    {
        $user = Auth::user();

        if (!$user->santri) {
            return redirect('/')->withErrors(['error' => 'Profil santri tidak ditemukan untuk akun ini.']);
        }

        $export = $action->execute(
            $user->santri->id,
            $request->get('period', 'today'),
            $request->waktu_sholat
        );

        return response()->stream($export['callback'], 200, $export['headers']);
    }
}
