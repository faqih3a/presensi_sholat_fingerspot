<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
use App\Traits\DateAndPrayerHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Aksi: Mengambil Data Presensi untuk Halaman Kehadiran
 *
 * Menghandle query presensi dengan filter waktu sholat, status,
 * dan pencarian nama santri untuk halaman detail kehadiran.
 * Juga menghitung navigasi tanggal (prev/next) berdasarkan mode.
 *
 * @see \App\Http\Controllers\DashboardController::kehadiran()
 * @see \App\Http\Controllers\DashboardController::exportKehadiran()
 */
class FetchPresensiDataAction
{
    use DateAndPrayerHelper;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array  Data presensi + navigasi tanggal untuk view.
     */
    public function execute(Request $request): array
    {
        $resolvedDates = $this->resolveDateRange($request);
        $mode          = $resolvedDates['mode'];
        $ref_date      = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $refDate = Carbon::parse($ref_date, 'Asia/Jakarta');
        if ($mode === 'week') {
            $prev_date = $refDate->copy()->subWeek()->format('Y-m-d');
            $next_date = $refDate->copy()->addWeek()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } elseif ($mode === 'month') {
            $prev_date = $refDate->copy()->subMonth()->format('Y-m-d');
            $next_date = $refDate->copy()->addMonth()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } else {
            $prev_date = $refDate->copy()->subDay()->format('Y-m-d');
            $next_date = $refDate->copy()->addDay()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        }

        $waktuSholat = $request->get('waktu_sholat');
        $status      = $request->get('status');
        $search      = $request->get('search');

        // Query presensi (exclude data "Tes")
        $query = Presensi::with('santri')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
            ->where('waktu_sholat', '!=', 'Tes');

        if ($waktuSholat) $query->where('waktu_sholat', $waktuSholat);
        if ($status)      $query->where('status', $status);
        if ($search) {
            $query->whereHas('santri', function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%');
            });
        }

        $presensis = $query->orderBy('tanggal', 'desc')
            ->orderByRaw("FIELD(waktu_sholat, 'Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya') DESC")
            ->orderBy('waktu_hadir', 'desc')
            ->get();

        return compact(
            'presensis', 'tanggal_mulai', 'tanggal_akhir', 'waktuSholat', 'status',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date'
        );
    }
}
