<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Santri;
use App\Models\Izin;
use Illuminate\Http\Request;
use App\Traits\DateAndPrayerHelper;

class DashboardController extends Controller
{
    use DateAndPrayerHelper;
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $waktuSholat = $request->waktu_sholat;
        
        $resolvedDates = $this->resolveDateRange($request);
        $mode = $resolvedDates['mode'];
        $ref_date = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $refDate = \Carbon\Carbon::parse($ref_date, 'Asia/Jakarta');
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

        $recentPresensis = Presensi::with('santri')
            ->whereNotNull('waktu_hadir')
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc')
            ->take(5)
            ->get();

        $recentActivities = $recentPresensis->map(function ($presensi) {
            $santri = $presensi->santri;
            $name = $santri ? $santri->nama : 'PIN ' . $presensi->santri_id;
            $role = 'santri';
            $detail = $santri ? 'Kelas ' . $santri->kelas : 'Santri';
            $avatar = ($santri && $santri->foto_referensi) ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;
            
            $verifyMethod = 'Fingerprint';
            $verifyIcon = 'bi-fingerprint';
            
            if ($presensi->photo_url) {
                $verifyMethod = 'Face';
                $verifyIcon = 'bi-person-bounding-box';
            }
            
            $statusScanLabel = 'Scan Masuk';
            
            try {
                $carbonScan = \Carbon\Carbon::parse($presensi->tanggal . ' ' . $presensi->waktu_hadir, 'Asia/Jakarta');
            } catch (\Exception $e) {
                $carbonScan = \Carbon\Carbon::now('Asia/Jakarta');
            }

            return (object) [
                'pin' => $presensi->santri_id,
                'name' => $name,
                'role' => $role,
                'detail' => $detail,
                'avatar' => $avatar,
                'scan_time' => $carbonScan,
                'verify_method' => $verifyMethod,
                'verify_icon' => $verifyIcon,
                'status_scan_label' => $statusScanLabel,
                'photo_url' => $presensi->photo_url,
            ];
        });

        
        // Hitung total santri
        $totalSantri = \App\Models\Santri::count();
        
        $startDate = $tanggal_mulai;
        $endDate = $tanggal_akhir;

        // Hitung santri yang hadir (status Hadir) dalam periode tersebut
        $hadirQuery = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate])->where('status', 'Hadir');
        if ($waktuSholat) {
            $hadirQuery->where('waktu_sholat', $waktuSholat);
        }
        $hadirHariIni = $hadirQuery->distinct('santri_id')->count('santri_id');
        
        // Hitung santri yang Alfa (status Alfa) dalam periode tersebut
        $alfaQuery = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate])->where('status', 'Alfa');
        if ($waktuSholat) {
            $alfaQuery->where('waktu_sholat', $waktuSholat);
        }
        $totalAlfa = $alfaQuery->distinct('santri_id')->count('santri_id');

        // Hitung santri yang Izin (status Izin) dalam periode tersebut
        $izinQuery = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate])->where('status', 'Izin');
        if ($waktuSholat) {
            $izinQuery->where('waktu_sholat', $waktuSholat);
        }
        $totalIzin = $izinQuery->distinct('santri_id')->count('santri_id');

        // Untuk tampilan dashboard, "Tidak Hadir" mencakup Alfa dan Izin
        $tidakHadir = $totalAlfa + $totalIzin;
        
        // Persentase kehadiran
        $persentase = $totalSantri > 0 ? round(($hadirHariIni / $totalSantri) * 100, 1) : 0;

        // Fetch absent santris (Alfa or Izin)
        $absentSantris = collect();
        $absentRecords = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate])
                                            ->whereIn('status', ['Alfa', 'Izin']);
        if ($waktuSholat) {
            $absentRecords->where('waktu_sholat', $waktuSholat);
        }
        
        $absentRecords = $absentRecords->get();
        $absentSantriIds = $absentRecords->pluck('santri_id')->unique();
        $santriModels = \App\Models\Santri::whereIn('id', $absentSantriIds)->get()->keyBy('id');

        $absentSantris = $absentRecords->map(function($record) use ($santriModels) {
            $santri = $santriModels->get($record->santri_id);
            if ($santri) {
                $santri->current_status = $record->status;
            }
            return $santri;
        })->filter()->unique('id');

        // Data untuk grafik kehadiran
        $chartLabels = [];
        $chartData = [];
        
        $start = \Carbon\Carbon::parse($startDate, 'Asia/Jakarta');
        $end = \Carbon\Carbon::parse($endDate, 'Asia/Jakarta');
        
        // limit to 31 days for chart if range is too big
        if ($start->diffInDays($end) > 31) {
            $start = $end->copy()->subDays(30);
        }

        $chartStartStr = $start->format('Y-m-d');
        $chartEndStr = $end->format('Y-m-d');

        $dailyCounts = \App\Models\Presensi::whereBetween('tanggal', [$chartStartStr, $chartEndStr])
                                            ->selectRaw('tanggal, COUNT(DISTINCT santri_id) as total')
                                            ->groupBy('tanggal')
                                            ->pluck('total', 'tanggal')
                                            ->toArray();

        while ($start->lte($end)) {
            $dateStr = $start->format('Y-m-d');
            $chartLabels[] = $start->format('d M');
            $chartData[] = $dailyCounts[$dateStr] ?? 0;
            $start->addDay();
        }

        // === Weekly Chart Data (last 7 days) ===
        $weeklyLabels = [];
        $weeklyData = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $nowCarbon = \Carbon\Carbon::now('Asia/Jakarta');

        $startWeeklyDate = $nowCarbon->copy()->subDays(6)->format('Y-m-d');
        $endWeeklyDate = $nowCarbon->format('Y-m-d');

        $weeklyCounts = \App\Models\Presensi::whereBetween('tanggal', [$startWeeklyDate, $endWeeklyDate])
                                            ->where('status', 'Hadir')
                                            ->selectRaw('tanggal, COUNT(*) as total')
                                            ->groupBy('tanggal')
                                            ->pluck('total', 'tanggal')
                                            ->toArray();

        for ($i = 6; $i >= 0; $i--) {
            $d = $nowCarbon->copy()->subDays($i);
            $dateStr = $d->format('Y-m-d');
            $weeklyLabels[] = $dayNames[$d->dayOfWeek];
            $weeklyData[] = $weeklyCounts[$dateStr] ?? 0;
        }

        // === Per-Prayer-Time Chart Data (today) ===
        $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $prayerLabels = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
        $prayerData = [];

        $prayerCounts = \App\Models\Presensi::where('tanggal', $today)
                                            ->where('status', 'Hadir')
                                            ->selectRaw('waktu_sholat, COUNT(*) as total')
                                            ->groupBy('waktu_sholat')
                                            ->pluck('total', 'waktu_sholat')
                                            ->toArray();

        foreach ($prayerLabels as $p) {
            $prayerData[] = $prayerCounts[$p] ?? 0;
        }

        // === Total Scan Hari Ini (all scans today, not just unique santri) ===
        $totalScanHariIni = \App\Models\Presensi::where('tanggal', $today)
                                                 ->whereNotNull('waktu_hadir')
                                                 ->count();

        // === Jamaah Hadir Hari Ini (unique santri who attended today) ===
        $jamaahHadirHariIni = \App\Models\Presensi::where('tanggal', $today)
                                                   ->where('status', 'Hadir')
                                                   ->distinct('santri_id')
                                                   ->count('santri_id');

        // === Ketepatan Waktu (on-time percentage for today) ===
        $hadirToday = \App\Models\Presensi::where('tanggal', $today)->where('status', 'Hadir')->count();
        $totalExpectedToday = $totalSantri * 5; // 5 prayer times
        $ketepatanWaktu = $totalExpectedToday > 0 ? round(($hadirToday / $totalExpectedToday) * 100, 0) : 0;

        // Ambil jadwal sholat untuk tanggal akhir range
        $jadwal = $this->getJadwalSholat(\Carbon\Carbon::parse($endDate, 'Asia/Jakarta'));

        // Fetch specifically for the range's Izin and Alfa lists
        $izinTodayRecords = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate])
                                            ->where('status', 'Izin')
                                            ->with('santri')
                                            ->get()
                                            ->groupBy('santri_id');

        $alfaTodayRecords = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate])
                                            ->where('status', 'Alfa')
                                            ->with('santri')
                                            ->get()
                                            ->groupBy('santri_id');

        // Identify santris with approved permits covering the range
        $fullDayIzinSantriIds = \App\Models\Santri::whereIn('user_id', function($query) use ($startDate, $endDate) {
            $query->select('user_id')
                  ->from('izins')
                  ->where('status', 'Disetujui')
                  ->where(function($q) use ($startDate, $endDate) {
                      $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                        ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                        ->orWhere(function($sq) use ($startDate, $endDate) {
                            $sq->where('tanggal_mulai', '<=', $startDate)
                               ->where('tanggal_selesai', '>=', $endDate);
                        });
                  });
        })->pluck('id')->toArray();

        // Data for status distribution chart
        $distQuery = \App\Models\Presensi::whereBetween('tanggal', [$startDate, $endDate]);
        if ($waktuSholat) {
            $distQuery->where('waktu_sholat', $waktuSholat);
        }
        
        $statusCounts = (clone $distQuery)->selectRaw('status, COUNT(*) as total')
                                          ->groupBy('status')
                                          ->pluck('total', 'status')
                                          ->toArray();

        $statusData = [
            $statusCounts['Hadir'] ?? 0,
            $statusCounts['Izin'] ?? 0,
            $statusCounts['Alfa'] ?? 0,
        ];

        // Determine next prayer time
        $nextPrayer = null;
        if ($jadwal) {
            $prayerMap = [
                'Subuh' => 'Fajr',
                'Syuruq' => 'Sunrise',
                'Dzuhur' => 'Dhuhr',
                'Ashar' => 'Asr',
                'Maghrib' => 'Maghrib',
                'Isya' => 'Isha',
            ];
            $nowTime = \Carbon\Carbon::now('Asia/Jakarta');
            foreach ($prayerMap as $label => $key) {
                if (isset($jadwal[$key])) {
                    $prayerTime = \Carbon\Carbon::parse($today . ' ' . $jadwal[$key], 'Asia/Jakarta');
                    if ($nowTime->lessThan($prayerTime)) {
                        $nextPrayer = $label;
                        break;
                    }
                }
            }
        }

        return view('dashboard.index', compact(
            'totalSantri', 'hadirHariIni', 'tidakHadir', 'persentase', 
            'jadwal', 'chartLabels', 'chartData', 'waktuSholat', 
            'absentSantris', 'izinTodayRecords', 'alfaTodayRecords', 'fullDayIzinSantriIds',
            'statusData', 'tanggal_mulai', 'tanggal_akhir',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date',
            'recentActivities',
            'weeklyLabels', 'weeklyData', 'prayerLabels', 'prayerData',
            'totalScanHariIni', 'jamaahHadirHariIni', 'ketepatanWaktu', 'nextPrayer'
        ));
    }

    public function kehadiran(Request $request)
    {
        $data = $this->fetchPresensiData($request);
        return view('dashboard.kehadiran', $data);
    }

    public function exportKehadiran(Request $request)
    {
        $data = $this->fetchPresensiData($request);
        $presensis = $data['presensis'];

        $filename = "rekap_kehadiran_" . date('Y-m-d_H-i-s') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        
        $columns = ['No', 'Nama Santri', 'Kelas', 'Waktu Sholat', 'Tanggal', 'Waktu Hadir', 'Status'];
        
        $callback = function() use($presensis, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
            fputcsv($file, $columns);
            $no = 1;
            foreach ($presensis as $presensi) {
                fputcsv($file, [
                    $no++,
                    $presensi->santri->nama,
                    $presensi->santri->kelas,
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

    private function fetchPresensiData(Request $request)
    {
        $resolvedDates = $this->resolveDateRange($request);
        $mode = $resolvedDates['mode'];
        $ref_date = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $refDate = \Carbon\Carbon::parse($ref_date, 'Asia/Jakarta');
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

        $waktuSholat = $request->get('waktu_sholat');
        $status = $request->get('status');
        $search = $request->get('search');

        $now = \Carbon\Carbon::now('Asia/Jakarta');
        
        // Fetch real records (hanya data sholat, exclude Tes)
        $query = Presensi::with('santri')
                         ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
                         ->where('waktu_sholat', '!=', 'Tes');
                         
        if ($waktuSholat) {
            $query->where('waktu_sholat', $waktuSholat);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->whereHas('santri', function($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%');
            });
        }
        
        $presensis = $query->orderBy('tanggal', 'desc')
                           ->orderByRaw("FIELD(waktu_sholat, 'Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya') DESC")
                           ->orderBy('waktu_hadir', 'desc')
                           ->get();

        return compact(
            'presensis', 'tanggal_mulai', 'tanggal_akhir', 'waktuSholat', 'status',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date'
        );
    }
}
