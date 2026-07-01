<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\DateAndPrayerHelper;
use App\Actions\Dashboard\FetchDashboardStatsAction;
use App\Actions\Dashboard\FetchRecentActivitiesAction;
use App\Actions\Dashboard\FetchWeeklyChartDataAction;
use App\Actions\Dashboard\FetchPrayerChartDataAction;
use App\Actions\Dashboard\FetchPresensiDataAction;
use App\Actions\Dashboard\ExportKehadiranCsvAction;

/**
 * Controller untuk halaman Dashboard dan Kehadiran.
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima HTTP Request dan meresolusi parameter tanggal/navigasi.
 * 2. Mendelegasikan seluruh logika bisnis ke Action Classes.
 * 3. Menggabungkan hasil dari berbagai Action dan mengirimkannya ke view.
 *
 * Logika bisnis telah dipecah ke dalam 6 Action Class:
 * @see \App\Actions\Dashboard\FetchDashboardStatsAction
 * @see \App\Actions\Dashboard\FetchRecentActivitiesAction
 * @see \App\Actions\Dashboard\FetchWeeklyChartDataAction
 * @see \App\Actions\Dashboard\FetchPrayerChartDataAction
 * @see \App\Actions\Dashboard\FetchPresensiDataAction
 * @see \App\Actions\Dashboard\ExportKehadiranCsvAction
 */
class DashboardController extends Controller
{
    use DateAndPrayerHelper;

    public function __construct()
    {
    }

    /**
     * Menampilkan halaman dashboard utama.
     *
     * Meresolusi parameter tanggal dari request, lalu mendelegasikan
     * pengambilan data ke 4 Action Class yang masing-masing bertanggung
     * jawab atas satu bagian dashboard (stats, activities, charts, prayer).
     *
     * @param  \Illuminate\Http\Request                             $request
     * @param  \App\Actions\Dashboard\FetchDashboardStatsAction     $statsAction
     * @param  \App\Actions\Dashboard\FetchRecentActivitiesAction   $activitiesAction
     * @param  \App\Actions\Dashboard\FetchWeeklyChartDataAction    $weeklyChartAction
     * @param  \App\Actions\Dashboard\FetchPrayerChartDataAction    $prayerChartAction
     * @return \Illuminate\View\View
     */
    public function index(
        Request $request,
        FetchDashboardStatsAction $statsAction,
        FetchRecentActivitiesAction $activitiesAction,
        FetchWeeklyChartDataAction $weeklyChartAction,
        FetchPrayerChartDataAction $prayerChartAction
    ) {
        $waktuSholat = $request->waktu_sholat;

        // --- Resolusi navigasi tanggal ---
        $resolvedDates = $this->resolveDateRange($request);
        $mode          = $resolvedDates['mode'];
        $ref_date      = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $nav = $this->resolveNavigation($mode, $ref_date, $tanggal_mulai);
        $prev_date    = $nav['prev_date'];
        $next_date    = $nav['next_date'];
        $display_date = $nav['display_date'];

        // --- Delegasi ke Action Classes ---
        $stats      = $statsAction->execute($tanggal_mulai, $tanggal_akhir, $waktuSholat);
        $recentActivities = $activitiesAction->execute();
        $chartData  = $weeklyChartAction->execute($tanggal_mulai, $tanggal_akhir);
        $prayerData = $prayerChartAction->execute();

        // --- Gabungkan semua data untuk view ---
        $viewData = array_merge($stats, $chartData, $prayerData, [
            'waktuSholat'      => $waktuSholat,
            'tanggal_mulai'    => $tanggal_mulai,
            'tanggal_akhir'    => $tanggal_akhir,
            'mode'             => $mode,
            'ref_date'         => $ref_date,
            'prev_date'        => $prev_date,
            'next_date'        => $next_date,
            'display_date'     => $display_date,
            'recentActivities' => $recentActivities,
        ]);

        return view('dashboard.index', $viewData);
    }

    /**
     * Menampilkan halaman detail kehadiran (rekap presensi).
     *
     * @param  \Illuminate\Http\Request                          $request
     * @param  \App\Actions\Dashboard\FetchPresensiDataAction    $action
     * @return \Illuminate\View\View
     */
    public function kehadiran(Request $request, FetchPresensiDataAction $action)
    {
        $data = $action->execute($request);
        return view('dashboard.kehadiran', $data);
    }

    /**
     * Export data kehadiran ke format CSV.
     *
     * @param  \Illuminate\Http\Request                          $request
     * @param  \App\Actions\Dashboard\FetchPresensiDataAction    $presensiAction
     * @param  \App\Actions\Dashboard\ExportKehadiranCsvAction   $exportAction
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportKehadiran(
        Request $request,
        FetchPresensiDataAction $presensiAction,
        ExportKehadiranCsvAction $exportAction
    ) {
        $data    = $presensiAction->execute($request);
        $export  = $exportAction->execute($data['presensis']);

        return response()->stream($export['callback'], 200, $export['headers']);
    }
}
