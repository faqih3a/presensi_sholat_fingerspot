<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
use App\Models\Santri;
use App\Traits\DateAndPrayerHelper;
use Carbon\Carbon;

/**
 * Aksi: Mengambil Data Chart Per Waktu Sholat & Jadwal Sholat
 *
 * Menghitung jumlah santri hadir hari ini per waktu sholat (Subuh–Isya)
 * untuk ditampilkan sebagai bar/donut chart, serta mengambil jadwal
 * sholat dari API Aladhan dan menentukan waktu sholat berikutnya.
 *
 * @see \App\Http\Controllers\DashboardController::index()
 */
class FetchPrayerChartDataAction
{
    use DateAndPrayerHelper;

    /** @var array Urutan waktu sholat wajib. */
    private const PRAYER_LABELS = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];

    /** @var array Mapping nama sholat ke key API Aladhan. */
    private const PRAYER_MAP = [
        'Subuh'   => 'Fajr',
        'Syuruq'  => 'Sunrise',
        'Dzuhur'  => 'Dhuhr',
        'Ashar'   => 'Asr',
        'Maghrib' => 'Maghrib',
        'Isya'    => 'Isha',
    ];

    /**
     * @param  string|null  $waktuSholat  Filter waktu sholat opsional (Subuh/Dzuhur/Ashar/Maghrib/Isya).
     * @return array  Berisi: prayerLabels, prayerData, jadwal, nextPrayer, statusData, todayInsight.
     */
    public function execute(?string $waktuSholat = null): array
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $totalSantri = Santri::count();

        // Satu query agregat: jumlah hadir per waktu sholat hari ini
        $prayerCounts = Presensi::where('tanggal', $today)
            ->where('status', 'Hadir')
            ->selectRaw('waktu_sholat, COUNT(*) as total')
            ->groupBy('waktu_sholat')
            ->pluck('total', 'waktu_sholat')
            ->toArray();

        $prayerLabels = self::PRAYER_LABELS;
        $prayerData = [];
        foreach ($prayerLabels as $p) {
            $prayerData[] = $prayerCounts[$p] ?? 0;
        }

        // --- Donut chart: status data hari ini (filtered by waktu_sholat) ---
        $statusQuery = Presensi::where('tanggal', $today)
            ->when($waktuSholat, fn($q) => $q->where('waktu_sholat', $waktuSholat));

        $statusCounts = (clone $statusQuery)
            ->selectRaw('status, COUNT(DISTINCT santri_id) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $hadir = $statusCounts['Hadir'] ?? 0;
        $izin  = $statusCounts['Izin'] ?? 0;
        $alfa  = $statusCounts['Alfa'] ?? 0;
        $statusData = [$hadir, $izin, $alfa];

        $todayInsight = $this->generateTodayInsight($hadir, $izin, $alfa);

        // Jadwal sholat hari ini (dari API / cache)
        $jadwal = $this->getJadwalSholat(Carbon::now('Asia/Jakarta'));

        // Tentukan waktu sholat berikutnya
        $nextPrayer = $this->determineNextPrayer($jadwal, $today);

        return compact('prayerLabels', 'prayerData', 'jadwal', 'nextPrayer', 'statusData', 'todayInsight');
    }

    /**
     * Menentukan waktu sholat berikutnya berdasarkan jam saat ini.
     *
     * @param  array|null  $jadwal  Jadwal sholat dari API.
     * @param  string      $today   Tanggal hari ini (Y-m-d).
     * @return string|null  Nama waktu sholat berikutnya, atau null jika sudah lewat Isya.
     */
    private function determineNextPrayer(?array $jadwal, string $today): ?string
    {
        if (!$jadwal) return null;

        $nowTime = Carbon::now('Asia/Jakarta');
        foreach (self::PRAYER_MAP as $label => $key) {
            if (isset($jadwal[$key])) {
                $prayerTime = Carbon::parse($today . ' ' . $jadwal[$key], 'Asia/Jakarta');
                if ($nowTime->lessThan($prayerTime)) {
                    return $label;
                }
            }
        }
        return null;
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

