<?php

namespace App\Actions\Dashboard;

use Carbon\Carbon;

/**
 * Aksi: Export Data Kehadiran ke Format CSV
 *
 * Menghasilkan callback stream untuk mengirimkan file CSV kehadiran
 * ke browser. Data presensi diterima dari FetchPresensiDataAction,
 * lalu diformat dan ditulis ke stream output.
 *
 * Fitur:
 * - BOM (Byte Order Mark) untuk kompatibilitas Excel dengan karakter UTF-8.
 * - Kolom: No, Nama Santri, Kelas, Waktu Sholat, Tanggal, Waktu Hadir, Status.
 *
 * @see \App\Http\Controllers\DashboardController::exportKehadiran()
 */
class ExportKehadiranCsvAction
{
    /**
     * @param  \Illuminate\Support\Collection  $presensis  Koleksi model Presensi (with santri).
     * @return array  Berisi 'headers' (HTTP headers), 'callback' (closure stream), 'filename'.
     */
    public function execute($presensis): array
    {
        $filename = "rekap_kehadiran_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $columns = ['No', 'Nama Santri', 'Kelas', 'Waktu Sholat', 'Tanggal', 'Waktu Hadir', 'Status'];

        $callback = function () use ($presensis, $columns) {
            $file = fopen('php://output', 'w');
            // BOM untuk kompatibilitas Excel UTF-8
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($presensis as $presensi) {
                fputcsv($file, [
                    $no++,
                    $presensi->santri->nama,
                    $presensi->santri->kelas,
                    $presensi->waktu_sholat,
                    Carbon::parse($presensi->tanggal)->format('d M Y'),
                    $presensi->waktu_hadir ? Carbon::parse($presensi->waktu_hadir)->format('H:i:s') : '-',
                    $presensi->status,
                ]);
            }
            fclose($file);
        };

        return compact('headers', 'callback', 'filename');
    }
}
