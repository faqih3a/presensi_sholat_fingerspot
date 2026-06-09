<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Presensi;
use App\Traits\DateAndPrayerHelper;

class SantriDashboardController extends Controller
{
    use DateAndPrayerHelper;
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user has a linked santri profile
        if (!$user->santri) {
            return redirect('/')->withErrors(['error' => 'Profil santri tidak ditemukan untuk akun ini.']);
        }

        $waktuSholat = $request->waktu_sholat;
        $period = $request->get('period', 'today');

        $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $startDate = $today;
        $endDate = $today;

        if ($period === 'week') {
            $startDate = \Carbon\Carbon::now('Asia/Jakarta')->subDays(6)->format('Y-m-d');
        } elseif ($period === 'month') {
            $startDate = \Carbon\Carbon::now('Asia/Jakarta')->subDays(29)->format('Y-m-d');
        }

        $this->syncAlfas($user->santri->id);

        // Get personal presensi history
        $query = Presensi::where('santri_id', $user->santri->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        if ($waktuSholat) {
            $query->where('waktu_sholat', $waktuSholat);
        }

        $presensis = $query->get();

        // Calculate totals based on filtered results
        $totalHadir = $presensis->where('status', 'Hadir')->count();
        $totalAlfa = $presensis->where('status', 'Alfa')->count();

        return view('santri.dashboard', compact('presensis', 'user', 'totalHadir', 'totalAlfa', 'period', 'waktuSholat'));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->santri) {
            return redirect('/')->withErrors(['error' => 'Profil santri tidak ditemukan untuk akun ini.']);
        }

        $waktuSholat = $request->waktu_sholat;
        $period = $request->get('period', 'today');

        $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $startDate = $today;
        $endDate = $today;

        if ($period === 'week') {
            $startDate = \Carbon\Carbon::now('Asia/Jakarta')->subDays(6)->format('Y-m-d');
        } elseif ($period === 'month') {
            $startDate = \Carbon\Carbon::now('Asia/Jakarta')->subDays(29)->format('Y-m-d');
        }

        // Only sync today and yesterday to prevent API latency during dashboard loads


        $query = Presensi::where('santri_id', $user->santri->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
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
        
        $callback = function() use($presensis, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
            fputcsv($file, $columns);
            
            $no = 1;
            foreach ($presensis as $presensi) {
                fputcsv($file, [
                    $no++,
                    $presensi->waktu_sholat,
                    \Carbon\Carbon::parse($presensi->tanggal)->format('d M Y'),
                    $presensi->waktu_hadir ? \Carbon\Carbon::parse($presensi->waktu_hadir)->format('H:i:s') : '-',
                    $presensi->status
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

}
