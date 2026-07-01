<?php

namespace App\Actions\Attendance;

use App\Models\Presensi;
use App\Traits\DateAndPrayerHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Aksi: Export Data Presensi Tes ke CSV
 *
 * Class ini bertanggung jawab untuk menghasilkan response CSV
 * dari data presensi tes (di luar waktu sholat).
 *
 * @see \App\Http\Controllers\TesController::exportTes()
 */
class ExportTesCsvAction
{
    use DateAndPrayerHelper;

    /**
     * Menjalankan aksi export CSV presensi tes.
     *
     * @param  \Illuminate\Http\Request  $request  HTTP request dengan parameter tanggal.
     * @return array  Berisi 'callback' (Closure) dan 'headers' (array) untuk stream response.
     */
    public function execute(Request $request): array
    {
        $resolvedDates = $this->resolveDateRange($request);
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $presensis = Presensi::with('santri')
            ->where('waktu_sholat', 'Tes')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc')
            ->get();

        $filename = "rekap_tes_presensi_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'Nama Santri', 'Kelas', 'Tanggal', 'Waktu Hadir', 'Status'];

        $callback = function () use ($presensis, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);
            $no = 1;
            foreach ($presensis as $presensi) {
                fputcsv($file, [
                    $no++,
                    $presensi->santri->nama ?? '-',
                    $presensi->santri->kelas ?? '-',
                    Carbon::parse($presensi->tanggal)->format('d M Y'),
                    $presensi->waktu_hadir ? Carbon::parse($presensi->waktu_hadir)->format('H:i:s') : '-',
                    $presensi->status,
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
