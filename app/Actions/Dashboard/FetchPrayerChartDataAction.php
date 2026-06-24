<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
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
     * @return array  Berisi: prayerLabels, prayerData, jadwal, nextPrayer.
     */
    public function execute(): array
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');

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

        // Jadwal sholat hari ini (dari API / cache)
        $jadwal = $this->getJadwalSholat(Carbon::now('Asia/Jakarta'));

        // Tentukan waktu sholat berikutnya
        $nextPrayer = $this->determineNextPrayer($jadwal, $today);

        return compact('prayerLabels', 'prayerData', 'jadwal', 'nextPrayer');
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
}
