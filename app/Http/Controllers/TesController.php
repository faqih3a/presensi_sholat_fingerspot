<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Presensi;
use App\Models\Santri;
use Carbon\Carbon;
use App\Traits\DateAndPrayerHelper;

class TesController extends Controller
{
    use DateAndPrayerHelper;

    public function index(Request $request)
    {
        $resolvedDates = $this->resolveDateRange($request);
        $mode = $resolvedDates['mode'];
        $ref_date = $resolvedDates['ref_date'];
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

        $search = $request->get('search');
        $status = $request->get('status');

        // Ambil data presensi Tes saja
        $query = Presensi::with('santri')
            ->where('waktu_sholat', 'Tes')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->whereHas('santri', function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%');
            });
        }

        $presensis = $query->get();

        return view('tes.index', compact(
            'presensis', 'tanggal_mulai', 'tanggal_akhir',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date',
            'status', 'search'
        ));
    }

    public function destroy(Presensi $presensi)
    {
        if ($presensi->waktu_sholat !== 'Tes') {
            return redirect()->back()->withErrors(['error' => 'Hanya bisa menghapus data presensi tes.']);
        }

        $presensi->delete();
        return redirect()->back()->with('success', 'Data presensi tes berhasil dihapus.');
    }

    public function exportTes(Request $request)
    {
        $resolvedDates = $this->resolveDateRange($request);
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $query = Presensi::with('santri')
            ->where('waktu_sholat', 'Tes')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        $presensis = $query->get();

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

        return response()->stream($callback, 200, $headers);
    }
}
