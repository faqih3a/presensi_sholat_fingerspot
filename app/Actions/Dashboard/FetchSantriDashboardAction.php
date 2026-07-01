<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
use Carbon\Carbon;

/**
 * Aksi: Mengambil Data Dashboard Santri (Personal)
 *
 * Class ini bertanggung jawab untuk mengambil dan mengolah data
 * presensi personal santri berdasarkan periode yang dipilih
 * (today/week/month) dan filter waktu sholat.
 *
 * Data yang dikembalikan:
 * - Riwayat presensi terfilter
 * - Total hadir
 * - Total alfa/alpha
 *
 * @see \App\Http\Controllers\SantriDashboardController::index()
 */
class FetchSantriDashboardAction
{
    /**
     * Menjalankan aksi pengambilan data dashboard santri.
     *
     * @param  int          $santriId     ID santri.
     * @param  string       $period       Periode: 'today', 'week', atau 'month'.
     * @param  string|null  $waktuSholat  Filter waktu sholat (opsional).
     * @return array  Berisi 'riwayatPresensi', 'totalHadir', 'totalAlpha'.
     */
    public function execute(int $santriId, string $period = 'today', ?string $waktuSholat = null): array
    {
        $today     = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $startDate = $today;
        $endDate   = $today;

        if ($period === 'week') {
            $startDate = Carbon::now('Asia/Jakarta')->subDays(6)->format('Y-m-d');
        } elseif ($period === 'month') {
            $startDate = Carbon::now('Asia/Jakarta')->subDays(29)->format('Y-m-d');
        }

        $query = Presensi::where('santri_id', $santriId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('waktu_sholat', '!=', 'Tes')
            ->where('status', '!=', 'Tes')
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        if ($waktuSholat) {
            $query->where('waktu_sholat', $waktuSholat);
        }

        $riwayatPresensi = $query->get();

        // Hitung total dengan case-insensitive dan support 'Alfa'/'Alpha'
        $totalHadir = $riwayatPresensi->filter(fn($p) => strtolower($p->status) === 'hadir')->count();
        $totalAlpha = $riwayatPresensi->filter(fn($p) => in_array(strtolower($p->status), ['alfa', 'alpha']))->count();

        return [
            'riwayatPresensi' => $riwayatPresensi,
            'totalHadir'      => $totalHadir,
            'totalAlpha'      => $totalAlpha,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
        ];
    }
}
