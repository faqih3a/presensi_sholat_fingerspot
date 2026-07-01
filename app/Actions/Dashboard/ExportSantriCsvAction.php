<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aksi: Export Data Kehadiran Santri ke CSV
 *
 * Class ini bertanggung jawab untuk menghasilkan response CSV
 * dari data presensi personal santri. Data diambil berdasarkan
 * periode dan filter waktu sholat yang diberikan.
 *
 * @see \App\Http\Controllers\SantriDashboardController::export()
 */
class ExportSantriCsvAction
{
    /**
     * Menjalankan aksi export CSV kehadiran santri.
     *
     * @param  int          $santriId     ID santri.
     * @param  string       $period       Periode: 'today', 'week', atau 'month'.
     * @param  string|null  $waktuSholat  Filter waktu sholat (opsional).
     * @return array  Berisi 'callback' (Closure) dan 'headers' (array) untuk stream response.
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

        $presensis = $query->get();

        $filename = "rekap_kehadiran_saya_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'Waktu Sholat', 'Tanggal', 'Waktu Hadir', 'Status'];

        $callback = function () use ($presensis, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($presensis as $presensi) {
                fputcsv($file, [
                    $no++,
                    $presensi->waktu_sholat,
                    Carbon::parse($presensi->tanggal)->format('d M Y'),
                    $presensi->waktu_hadir ? Carbon::parse($presensi->waktu_hadir)->format('H:i:s') : '-',
                    $presensi->status
                ]);
            }
            fclose($file);
        };

        return [
            'callback' => $callback,
            'headers'  => $headers,
        ];
    }
}
