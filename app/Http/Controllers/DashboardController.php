<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Santri;
use App\Models\Izin;
use App\Models\User;
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

        // 1. Perizinan (Izin)
        $izins = Izin::with('user.santri')
            ->latest('updated_at')
            ->take(15)
            ->get()
            ->map(function ($izin) {
                $user = $izin->user;
                $santri = $user ? $user->santri : null;
                $name = $santri ? $santri->nama : ($user ? $user->name : 'Tanpa Nama');
                $detail = $santri ? 'Kelas ' . $santri->kelas : ($user ? ucfirst($user->role) : 'Santri');
                $avatar = ($santri && $santri->foto_referensi) ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;
                if ($user && $user->avatar && !$avatar) {
                    $avatar = asset('storage/avatars/' . $user->avatar);
                }

                $status = $izin->status;
                $color = 'warning';
                $icon = 'bi-file-earmark-text-fill';
                $msg = "Mengajukan Izin {$izin->jenis_izin}";
                if ($status === 'Disetujui') {
                    $color = 'success';
                    $icon = 'bi-check-circle-fill';
                    $msg = "Izin {$izin->jenis_izin} Disetujui";
                } elseif ($status === 'Ditolak') {
                    $color = 'danger';
                    $icon = 'bi-x-circle-fill';
                    $msg = "Izin {$izin->jenis_izin} Ditolak";
                }

                return (object) [
                    'name' => $name,
                    'detail' => $detail,
                    'avatar' => $avatar,
                    'scan_time' => $izin->updated_at,
                    'verify_icon' => $icon,
                    'verify_method' => 'Perizinan',
                    'status_scan_label' => $msg,
                    'color' => $color,
                ];
            });

        // 2. Ketidakhadiran (Alfa)
        $alfas = Presensi::with('santri')
            ->whereIn('status', ['Alfa', 'Alpha'])
            ->latest('updated_at')
            ->take(15)
            ->get()
            ->map(function ($presensi) {
                $santri = $presensi->santri;
                $name = $santri ? $santri->nama : 'PIN ' . $presensi->santri_id;
                $detail = $santri ? 'Kelas ' . $santri->kelas : 'Santri';
                $avatar = ($santri && $santri->foto_referensi) ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;

                return (object) [
                    'name' => $name,
                    'detail' => $detail,
                    'avatar' => $avatar,
                    'scan_time' => $presensi->updated_at ?? \Carbon\Carbon::parse($presensi->tanggal . ' 18:00:00'),
                    'verify_icon' => 'bi-x-circle-fill',
                    'verify_method' => 'Ketidakhadiran',
                    'status_scan_label' => "Alfa Sholat {$presensi->waktu_sholat}",
                    'color' => 'danger',
                ];
            });

        // 3. Pendaftaran Santri Baru
        $newSantris = Santri::latest('created_at')
            ->take(15)
            ->get()
            ->map(function ($santri) {
                $name = $santri->nama;
                $detail = 'Kelas ' . $santri->kelas;
                $avatar = $santri->foto_referensi ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;

                return (object) [
                    'name' => $name,
                    'detail' => $detail,
                    'avatar' => $avatar,
                    'scan_time' => $santri->created_at,
                    'verify_icon' => 'bi-person-plus-fill',
                    'verify_method' => 'Santri Baru',
                    'status_scan_label' => 'Santri terdaftar aktif',
                    'color' => 'primary',
                ];
            });

        // 4. Pendaftaran Staf/Admin Baru
        $newStaffs = User::whereIn('role', ['asatidz', 'admin'])
            ->latest('created_at')
            ->take(15)
            ->get()
            ->map(function ($user) {
                $name = $user->name;
                $detail = ucfirst($user->role);
                $avatar = $user->avatar ? asset('storage/avatars/' . $user->avatar) : null;

                return (object) [
                    'name' => $name,
                    'detail' => $detail,
                    'avatar' => $avatar,
                    'scan_time' => $user->created_at,
                    'verify_icon' => 'bi-person-badge-fill',
                    'verify_method' => 'Pengurus Baru',
                    'status_scan_label' => "Akun {$detail} terdaftar",
                    'color' => 'info',
                ];
            });

        // Gabungkan dan urutkan berdasarkan waktu terbaru
        $recentActivities = collect()
            ->concat($izins)
            ->concat($alfas)
            ->concat($newSantris)
            ->concat($newStaffs)
            ->sortByDesc(function ($activity) {
                return $activity->scan_time;
            })
            ->take(5);

        
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

        // === Weekly Chart Data (last 7 days - Hadir, Izin, Alfa) ===
        $weeklyLabels = [];
        $weeklyHadir = [];
        $weeklyIzin = [];
        $weeklyAlfa = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $nowCarbon = \Carbon\Carbon::now('Asia/Jakarta');

        $startWeeklyDate = $nowCarbon->copy()->subDays(6)->format('Y-m-d');
        $endWeeklyDate = $nowCarbon->format('Y-m-d');

        $weeklyCounts = \App\Models\Presensi::whereBetween('tanggal', [$startWeeklyDate, $endWeeklyDate])
                                            ->selectRaw('tanggal, status, COUNT(DISTINCT santri_id) as total')
                                            ->groupBy('tanggal', 'status')
                                            ->get()
                                            ->groupBy('tanggal');

        for ($i = 6; $i >= 0; $i--) {
            $d = $nowCarbon->copy()->subDays($i);
            $dateStr = $d->format('Y-m-d');
            $weeklyLabels[] = $d->format('d M') . ' (' . $dayNames[$d->dayOfWeek] . ')';
            
            $dayRecords = $weeklyCounts->get($dateStr) ?? collect();
            
            $hadirCount = 0;
            $izinCount = 0;
            $alfaCount = 0;
            
            foreach ($dayRecords as $record) {
                $statusLower = strtolower($record->status);
                if ($statusLower === 'hadir') {
                    $hadirCount += $record->total;
                } elseif ($statusLower === 'izin' || $statusLower === 'sakit') {
                    $izinCount += $record->total;
                } elseif (in_array($statusLower, ['alfa', 'alpha'])) {
                    $alfaCount += $record->total;
                }
            }
            
            $weeklyHadir[] = $hadirCount;
            $weeklyIzin[] = $izinCount;
            $weeklyAlfa[] = $alfaCount;
        }

        // Generate Weekly Insight
        $maxHadirDay = '';
        $maxHadirVal = -1;
        $maxAlfaDay = '';
        $maxAlfaVal = -1;
        
        for ($i = 0; $i < 7; $i++) {
            if ($weeklyHadir[$i] > $maxHadirVal) {
                $maxHadirVal = $weeklyHadir[$i];
                $maxHadirDay = $weeklyLabels[$i];
            }
            if ($weeklyAlfa[$i] > $maxAlfaVal) {
                $maxAlfaVal = $weeklyAlfa[$i];
                $maxAlfaDay = $weeklyLabels[$i];
            }
        }
        
        $weeklyInsight = "Kehadiran terbanyak pada {$maxHadirDay} ({$maxHadirVal} santri).";
        if ($maxAlfaVal > 0) {
            $weeklyInsight .= " Perlu perhatian pada {$maxAlfaDay} karena terdapat {$maxAlfaVal} santri alfa.";
        } else {
            $weeklyInsight .= " Pekan ini tingkat kedisiplinan santri sangat baik.";
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
        $jadwal = $this->getJadwalSholat(\Carbon\Carbon::now('Asia/Jakarta'));

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

        $statusData = [
            $hadirHariIni,
            $totalIzin,
            $totalAlfa,
        ];

        // Generate Today Insight
        $totalPresensi = $hadirHariIni + $totalIzin + $totalAlfa;
        
        if ($totalPresensi > 0) {
            $attendanceRate = round(($hadirHariIni / $totalPresensi) * 100);
            $todayInsight = "Tingkat kehadiran hari ini mencapai {$attendanceRate}% ({$hadirHariIni} dari {$totalPresensi} santri).";
            if ($totalAlfa > 0) {
                $todayInsight .= " Ada {$totalAlfa} santri alfa yang belum melakukan scan.";
            } else {
                $todayInsight .= " Seluruh santri yang terdaftar hari ini hadir/izin.";
            }
        } else {
            $todayInsight = "Belum ada data presensi yang tercatat untuk periode hari ini.";
        }

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
            'weeklyLabels', 'weeklyHadir', 'weeklyIzin', 'weeklyAlfa', 'weeklyInsight', 'todayInsight', 'prayerLabels', 'prayerData',
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
