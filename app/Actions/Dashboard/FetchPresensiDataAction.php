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
    public function execute(Request $request, bool $forExport = false): array
    {
        // ── Resolusi navigasi tanggal ──
        $resolvedDates = $this->resolveDateRange($request);
        $mode          = $resolvedDates['mode'];
        $ref_date      = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $nav = $this->resolveNavigation($mode, $ref_date, $tanggal_mulai);

        // ── Tangkap parameter filter dengan aman ──
        $waktuSholat = $request->input('waktu_sholat');
        $status      = $request->input('status');
        $search      = $request->input('search');
        $kelas       = $request->input('kelas');

        // ── Build query: when() pattern ──
        // Query presensi (exclude data "Tes")
        $presensis = Presensi::with('santri')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
            ->where('waktu_sholat', '!=', 'Tes')

            // Filter waktu sholat: exact match
            ->when($waktuSholat, fn($q, $v) => $q->where('waktu_sholat', $v))

            // Filter status: exact match
            ->when($status, fn($q, $v) => $q->where('status', $v))

            // Search nama santri: LIKE partial match
            ->when($search, fn($q, $v) => $q->whereHas('santri',
                fn($sq) => $sq->where('nama', 'like', "%{$v}%")
            ))

            // Filter kelas santri: exact match
            ->when($kelas, fn($q, $v) => $q->whereHas('santri',
                fn($sq) => $sq->where('kelas', $v)
            ))

            ->orderBy('tanggal', 'desc')
            ->orderByRaw("FIELD(waktu_sholat, 'Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya') DESC")
            ->orderBy('waktu_hadir', 'desc');

        // Export mendapatkan semua data (tanpa pagination), tampilan normal pakai pagination
        $presensis = $forExport
            ? $presensis->get()
            : $presensis->paginate(25)->withQueryString();

        return compact(
            'presensis', 'tanggal_mulai', 'tanggal_akhir', 'waktuSholat', 'status',
            'mode', 'ref_date', 'search', 'kelas'
        ) + $nav;
    }
}
