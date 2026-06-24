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

        // --- Statistik kehadiran dalam periode ---

        $hadirHariIni = $this->countByStatus($startDate, $endDate, 'Hadir', $waktuSholat);
        $totalAlfa    = $this->countByStatus($startDate, $endDate, 'Alfa', $waktuSholat);
        $totalIzin    = $this->countByStatus($startDate, $endDate, 'Izin', $waktuSholat);

        $tidakHadir = $totalAlfa + $totalIzin;
        $persentase = $totalSantri > 0 ? round(($hadirHariIni / $totalSantri) * 100, 1) : 0;

        // --- Daftar santri yang tidak hadir (Alfa/Izin) ---

        $absentSantris = $this->fetchAbsentSantris($startDate, $endDate, $waktuSholat);

        // --- Record Izin & Alfa untuk detail lists di view ---

        $izinTodayRecords = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->where('status', 'Izin')
            ->with('santri')
            ->get()
            ->groupBy('santri_id');

        $alfaTodayRecords = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->where('status', 'Alfa')
            ->with('santri')
            ->get()
            ->groupBy('santri_id');

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

        // --- Donut chart status data ---

        $statusData = [$hadirHariIni, $totalIzin, $totalAlfa];

        // --- Statistik scan hari ini ---

        $totalScanHariIni = Presensi::where('tanggal', $today)
            ->whereNotNull('waktu_hadir')
            ->count();

        $jamaahHadirHariIni = Presensi::where('tanggal', $today)
            ->where('status', 'Hadir')
            ->distinct('santri_id')
            ->count('santri_id');

        // --- Ketepatan waktu (persentase hadir hari ini vs ekspektasi 5 waktu sholat) ---

        $hadirToday = Presensi::where('tanggal', $today)->where('status', 'Hadir')->count();
        $totalExpectedToday = $totalSantri * 5;
        $ketepatanWaktu = $totalExpectedToday > 0 ? round(($hadirToday / $totalExpectedToday) * 100, 0) : 0;

        // --- Generate insight teks ---

        $todayInsight = $this->generateTodayInsight($hadirHariIni, $totalIzin, $totalAlfa);

        return compact(
            'totalSantri', 'hadirHariIni', 'tidakHadir', 'persentase',
            'absentSantris', 'izinTodayRecords', 'alfaTodayRecords', 'fullDayIzinSantriIds',
            'statusData', 'totalScanHariIni', 'jamaahHadirHariIni', 'ketepatanWaktu',
            'todayInsight'
        );
    }

    /**
     * Menghitung jumlah santri unik berdasarkan status dalam periode tertentu.
     *
     * @param  string       $startDate    Tanggal awal (Y-m-d).
     * @param  string       $endDate      Tanggal akhir (Y-m-d).
     * @param  string       $status       Status presensi (Hadir/Alfa/Izin).
     * @param  string|null  $waktuSholat  Filter waktu sholat opsional.
     * @return int  Jumlah santri unik dengan status tersebut.
     */
    private function countByStatus(string $startDate, string $endDate, string $status, ?string $waktuSholat): int
    {
        $query = Presensi::whereBetween('tanggal', [$startDate, $endDate])
            ->where('status', $status);

        if ($waktuSholat) {
            $query->where('waktu_sholat', $waktuSholat);
        }

        return $query->distinct('santri_id')->count('santri_id');
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

    /**
     * Membuat teks insight ringkasan kehadiran hari ini.
     *
     * @param  int  $hadir  Jumlah hadir.
     * @param  int  $izin   Jumlah izin.
     * @param  int  $alfa   Jumlah alfa.
     * @return string  Kalimat insight untuk ditampilkan di dashboard.
     */
    private function generateTodayInsight(int $hadir, int $izin, int $alfa): string
    {
        $totalPresensi = $hadir + $izin + $alfa;

        if ($totalPresensi > 0) {
            $attendanceRate = round(($hadir / $totalPresensi) * 100);
            $insight = "Tingkat kehadiran hari ini mencapai {$attendanceRate}% ({$hadir} dari {$totalPresensi} santri).";
            $insight .= $alfa > 0
                ? " Ada {$alfa} santri alfa yang belum melakukan scan."
                : " Seluruh santri yang terdaftar hari ini hadir/izin.";
            return $insight;
        }

        return "Belum ada data presensi yang tercatat untuk periode hari ini.";
    }
}
