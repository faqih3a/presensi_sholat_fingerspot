<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
use App\Models\Santri;
use Carbon\Carbon;

/**
 * Aksi: Mengambil Statistik Kehadiran untuk Dashboard
 *
 * Class ini bertanggung jawab menghitung seluruh angka statistik
 * yang ditampilkan di kartu-kartu ringkasan halaman dashboard utama,
 * termasuk total santri, jumlah hadir/alfa/izin, persentase kehadiran,
 * daftar santri yang tidak hadir, data status donut chart, serta
 * statistik scan dan ketepatan waktu hari ini.
 *
 * Optimasi Query:
 * - Menggunakan `COUNT(DISTINCT santri_id)` agar satu query agregat
 *   menggantikan iterasi N+1.
 * - Mengambil model Santri yang absent dalam satu batch query
 *   (bukan per-record) lalu di-map secara in-memory.
 *
 * @see \App\Http\Controllers\DashboardController::index()
 */
class FetchDashboardStatsAction
{
    /**
     * Menjalankan aksi pengambilan statistik dashboard.
     *
     * @param  string       $startDate    Tanggal awal periode (Y-m-d).
     * @param  string       $endDate      Tanggal akhir periode (Y-m-d).
     * @param  string|null  $waktuSholat  Filter waktu sholat opsional (Subuh/Dzuhur/Ashar/Maghrib/Isya).
     * @return array  Array asosiatif berisi seluruh variabel statistik yang dibutuhkan view.
     */
    public function execute(string $startDate, string $endDate, ?string $waktuSholat = null): array
    {
        $totalSantri = Santri::count();
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

        // --- Statistik kehadiran: 1 query aggregate instead of 3 separate COUNT ---
        $statusCounts = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->when($waktuSholat, fn($q) => $q->where('waktu_sholat', $waktuSholat))
            ->selectRaw('status, COUNT(DISTINCT santri_id) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $hadirHariIni = $statusCounts['Hadir'] ?? 0;
        $totalAlfa    = $statusCounts['Alfa'] ?? 0;
        $totalIzin    = $statusCounts['Izin'] ?? 0;

        $tidakHadir = $totalAlfa + $totalIzin;
        $persentase = $totalSantri > 0 ? round(($hadirHariIni / $totalSantri) * 100, 1) : 0;

        // --- Daftar santri yang tidak hadir (Alfa/Izin) ---
        $absentSantris = $this->fetchAbsentSantris($startDate, $endDate, $waktuSholat);

        // --- Record Izin & Alfa: 1 query instead of 2 ---
        $allAbsentRecords = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status', ['Izin', 'Alfa'])
            ->with('santri')
            ->get();

        $izinTodayRecords = $allAbsentRecords->where('status', 'Izin')->groupBy('santri_id');
        $alfaTodayRecords = $allAbsentRecords->where('status', 'Alfa')->groupBy('santri_id');

        // --- Santri dengan izin yang disetujui dalam periode ---
        $fullDayIzinSantriIds = Santri::whereIn('user_id', function ($query) use ($startDate, $endDate) {
            $query->select('user_id')
                ->from('izins')
                ->where('status', 'Disetujui')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                        ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                        ->orWhere(function ($sq) use ($startDate, $endDate) {
                            $sq->where('tanggal_mulai', '<=', $startDate)
                                ->where('tanggal_selesai', '>=', $endDate);
                        });
                });
        })->pluck('id')->toArray();

        // --- Statistik scan & ketepatan waktu: 1 query instead of 3 ---
        $todayStats = Presensi::where('tanggal', $today)
            ->selectRaw("
                COUNT(CASE WHEN waktu_hadir IS NOT NULL THEN 1 END) as total_scan,
                COUNT(DISTINCT CASE WHEN status = 'Hadir' THEN santri_id END) as jamaah_hadir,
                COUNT(CASE WHEN status = 'Hadir' THEN 1 END) as hadir_count
            ")
            ->first();

        $totalScanHariIni   = (int) $todayStats->total_scan;
        $jamaahHadirHariIni = (int) $todayStats->jamaah_hadir;
        $hadirToday         = (int) $todayStats->hadir_count;

        $totalExpectedToday = $totalSantri * 5;
        $ketepatanWaktu = $totalExpectedToday > 0 ? round(($hadirToday / $totalExpectedToday) * 100, 0) : 0;

        return compact(
            'totalSantri', 'hadirHariIni', 'tidakHadir', 'persentase',
            'absentSantris', 'izinTodayRecords', 'alfaTodayRecords', 'fullDayIzinSantriIds',
            'totalScanHariIni', 'jamaahHadirHariIni', 'ketepatanWaktu'
        );
    }


    /**
     * Mengambil daftar santri yang tidak hadir (Alfa atau Izin) dalam periode.
     *
     * Menggunakan batch query untuk mengambil semua model Santri sekaligus
     * lalu di-map secara in-memory untuk menghindari N+1 query.
     *
     * @param  string       $startDate    Tanggal awal (Y-m-d).
     * @param  string       $endDate      Tanggal akhir (Y-m-d).
     * @param  string|null  $waktuSholat  Filter waktu sholat opsional.
     * @return \Illuminate\Support\Collection  Koleksi model Santri yang tidak hadir (dengan atribut current_status).
     */
    private function fetchAbsentSantris(string $startDate, string $endDate, ?string $waktuSholat)
    {
        $query = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status', ['Alfa', 'Izin']);

        if ($waktuSholat) {
            $query->where('waktu_sholat', $waktuSholat);
        }

        $absentRecords = $query->get();
        $absentSantriIds = $absentRecords->pluck('santri_id')->unique();
        $santriModels = Santri::whereIn('id', $absentSantriIds)->get()->keyBy('id');

        return $absentRecords->map(function ($record) use ($santriModels) {
            $santri = $santriModels->get($record->santri_id);
            if ($santri) {
                $santri->current_status = $record->status;
            }
            return $santri;
        })->filter()->unique('id');
    }

}
