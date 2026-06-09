<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Santri;
use App\Models\Izin;
use Illuminate\Http\Request;
use App\Services\FingerspotService;

class DashboardController extends Controller
{
    protected $fingerspotService;

    public function __construct(FingerspotService $fingerspotService)
    {
        $this->fingerspotService = $fingerspotService;
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
            $display_date = $this->formatIndonesianDate($tanggal_mulai) . ' - ' . $this->formatIndonesianDate($tanggal_akhir);
        } elseif ($mode === 'month') {
            $prev_date = $refDate->copy()->subMonth()->format('Y-m-d');
            $next_date = $refDate->copy()->addMonth()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } else {
            $prev_date = $refDate->copy()->subDay()->format('Y-m-d');
            $next_date = $refDate->copy()->addDay()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai);
        }

        // Only sync today and yesterday to prevent API latency during dashboard loads
        $lastSyncKey = 'fingerspot_sync_last_run';
        $forceSync = $request->get('sync') === 'true';
        
        if ($forceSync || !\Illuminate\Support\Facades\Cache::has($lastSyncKey)) {
            $syncStart = \Carbon\Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
            $syncEnd = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
            $this->fingerspotService->syncAttendance($syncStart, $syncEnd);
            $this->syncAlfas();
            // Cache for 10 minutes (600 seconds)
            \Illuminate\Support\Facades\Cache::put($lastSyncKey, true, 600);
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
                'pin' => $santri ? $santri->fingerspot_pin : $presensi->santri_id,
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

        while ($start->lte($end)) {
            $dateStr = $start->format('Y-m-d');
            $chartLabels[] = $start->format('d M');
            
            $count = \App\Models\Presensi::where('tanggal', $dateStr)
                                         ->distinct('santri_id')
                                         ->count('santri_id');
            $chartData[] = $count;
            $start->addDay();
        }

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
        
        $statusData = [
            (clone $distQuery)->where('status', 'Hadir')->count(),
            (clone $distQuery)->where('status', 'Izin')->count(),
            (clone $distQuery)->where('status', 'Alfa')->count(),
        ];

        return view('dashboard.index', compact(
            'totalSantri', 'hadirHariIni', 'tidakHadir', 'persentase', 
            'jadwal', 'chartLabels', 'chartData', 'waktuSholat', 
            'absentSantris', 'izinTodayRecords', 'alfaTodayRecords', 'fullDayIzinSantriIds',
            'statusData', 'tanggal_mulai', 'tanggal_akhir',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date',
            'recentActivities'
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
            $display_date = $this->formatIndonesianDate($tanggal_mulai) . ' - ' . $this->formatIndonesianDate($tanggal_akhir);
        } elseif ($mode === 'month') {
            $prev_date = $refDate->copy()->subMonth()->format('Y-m-d');
            $next_date = $refDate->copy()->addMonth()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } else {
            $prev_date = $refDate->copy()->subDay()->format('Y-m-d');
            $next_date = $refDate->copy()->addDay()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai);
        }

        // Only sync today and yesterday to prevent API latency during dashboard loads
        $lastSyncKey = 'fingerspot_sync_last_run';
        $forceSync = $request->get('sync') === 'true';
        
        if ($forceSync || !\Illuminate\Support\Facades\Cache::has($lastSyncKey)) {
            $syncStart = \Carbon\Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
            $syncEnd = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
            $this->fingerspotService->syncAttendance($syncStart, $syncEnd);
            $this->syncAlfas();
            \Illuminate\Support\Facades\Cache::put($lastSyncKey, true, 600);
        }

        $waktuSholat = $request->get('waktu_sholat');
        $status = $request->get('status');
        $search = $request->get('search');

        $now = \Carbon\Carbon::now('Asia/Jakarta');
        
        // Fetch real records
        $query = Presensi::with('santri')
                         ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir]);
                         
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
        
        $realRecords = $query->get();
        $realRecordsGrouped = $realRecords->groupBy(['tanggal', 'santri_id', 'waktu_sholat']);

        // Synthesize missing records
        $synthesized = collect();
        $sholats = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
        $sholatList = $waktuSholat ? [$waktuSholat] : $sholats;

        $santriQuery = Santri::query();
        if ($search) $santriQuery->where('nama', 'like', '%' . $search . '%');
        $santris = $santriQuery->get();

        $currentDate = \Carbon\Carbon::parse($tanggal_mulai, 'Asia/Jakarta');
        $endRangeDate = \Carbon\Carbon::parse($tanggal_akhir, 'Asia/Jakarta');

        // Pre-fetch all approved izins covering this range to avoid N+1 query in loops!
        $izins = Izin::where('status', 'Disetujui')
            ->where(function($q) use ($tanggal_mulai, $tanggal_akhir) {
                $q->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_akhir])
                  ->orWhereBetween('tanggal_selesai', [$tanggal_mulai, $tanggal_akhir])
                  ->orWhere(function($sq) use ($tanggal_mulai, $tanggal_akhir) {
                      $sq->where('tanggal_mulai', '<=', $tanggal_mulai)
                         ->where('tanggal_selesai', '>=', $tanggal_akhir);
                  });
            })
            ->get()
            ->groupBy('user_id');

        while ($currentDate->lte($endRangeDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $jadwal = $this->getJadwalSholat($currentDate);
            $times = $jadwal ? $this->getPrayerEndTimes($dateStr, $jadwal) : [];

            foreach ($santris as $santri) {
                foreach ($sholatList as $s) {
                    if (!isset($realRecordsGrouped[$dateStr][$santri->id][$s])) {
                        // Check approved izin in-memory
                        $hasIzin = false;
                        if (isset($izins[$santri->user_id])) {
                            $hasIzin = $izins[$santri->user_id]->contains(function($izin) use ($dateStr) {
                                return $izin->tanggal_mulai <= $dateStr && $izin->tanggal_selesai >= $dateStr;
                            });
                        }

                        $virtualStatus = $hasIzin ? 'Izin' : 'Alfa';
                        
                        if (!$status || $status === $virtualStatus) {
                            if ($dateStr === $now->format('Y-m-d') && $jadwal && isset($times[$s])) {
                                if ($now->lessThan($times[$s])) {
                                    continue;
                                }
                            } elseif ($currentDate->greaterThan($now)) {
                                continue;
                            }

                            $synthesized->push((object) [
                                'santri' => $santri,
                                'santri_id' => $santri->id,
                                'waktu_sholat' => $s,
                                'tanggal' => $dateStr,
                                'waktu_hadir' => null,
                                'status' => $virtualStatus
                            ]);
                        }
                    }
                }
            }
            $currentDate->addDay();
        }

        $presensis = $realRecords->concat($synthesized)
                                ->sortByDesc('waktu_hadir')
                                ->sortByDesc('tanggal');

        return compact(
            'presensis', 'tanggal_mulai', 'tanggal_akhir', 'waktuSholat', 'status',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date'
        );
    }

    private function syncAlfas()
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        
        $jadwal = $this->getJadwalSholat($now);
        if (!$jadwal) return;

        $times = $this->getPrayerEndTimes($today, $jadwal);
        $santris = \App\Models\Santri::all();

        // Check passed prayers today
        $todaySholats = [];
        foreach ($times as $sholat => $endTime) {
            if ($now->greaterThan($endTime)) {
                $todaySholats[] = $sholat;
            }
        }
        if (!empty($todaySholats)) {
            $this->syncAlfasForDate($today, $todaySholats, $santris);
        }
        
        // Sync yesterday
        $yesterday = $now->copy()->subDay()->format('Y-m-d');
        $hasYesterdaySync = \Illuminate\Support\Facades\Cache::get('sync_alfa_' . $yesterday);
        if (!$hasYesterdaySync) {
            $yesterdaySholats = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
            $this->syncAlfasForDate($yesterday, $yesterdaySholats, $santris);
            \Illuminate\Support\Facades\Cache::put('sync_alfa_' . $yesterday, true, 86400);
        }
    }

    private function syncAlfasForDate($dateStr, $sholatsToSync, $santris)
    {
        $izinUserIds = \App\Models\Izin::where('status', 'Disetujui')
            ->where(function ($q) use ($dateStr) {
                $q->whereDate('tanggal_mulai', '<=', $dateStr)
                  ->whereDate('tanggal_selesai', '>=', $dateStr);
            })
            ->pluck('user_id')
            ->toArray();

        foreach ($sholatsToSync as $sholat) {
            $presentSantriIds = Presensi::where('tanggal', $dateStr)
                                        ->where('waktu_sholat', $sholat)
                                        ->pluck('santri_id')
                                        ->toArray();

            $existingPresensis = Presensi::where('tanggal', $dateStr)
                                        ->where('waktu_sholat', $sholat)
                                        ->get()
                                        ->keyBy('santri_id');

            $newPresensis = [];
            foreach ($santris as $santri) {
                if (!in_array($santri->id, $presentSantriIds)) {
                    $hasIzin = in_array($santri->user_id, $izinUserIds);
                    $status = $hasIzin ? 'Izin' : 'Alfa';

                    if (!$existingPresensis->has($santri->id)) {
                        $newPresensis[] = [
                            'santri_id' => $santri->id,
                            'tanggal' => $dateStr,
                            'waktu_sholat' => $sholat,
                            'status' => $status,
                            'waktu_hadir' => null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }

            if (!empty($newPresensis)) {
                Presensi::insert($newPresensis);
            }
        }
    }

    private function getJadwalSholat(\Carbon\Carbon $date)
    {
        $address = 'Bogor, Kecamatan Cibeureum, Kp Joglo, Indonesia';
        $cacheKey = 'jadwal_sholat_' . md5($address) . '_' . $date->format('Y-m-d');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($date, $address) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://api.aladhan.com/v1/timingsByAddress', [
                    'address' => $address,
                    'method' => 20, // Kemenag RI
                    'date' => $date->format('d-m-Y')
                ]);

                if ($response->successful()) {
                    $timings = $response->json('data.timings');
                    // Sanitize timings to remove timezone suffixes like (WIB)
                    foreach ($timings as $key => $time) {
                        $timings[$key] = substr($time, 0, 5);
                    }
                    return $timings;
                }
            } catch (\Exception $e) {
                // Log error if needed
            }
            
            return [
                'Fajr' => '04:30',
                'Dhuhr' => '12:00',
                'Asr' => '15:15',
                'Maghrib' => '18:00',
                'Isha' => '19:15',
                'Subuh' => '04:30',
                'Dzuhur' => '12:00',
                'Ashar' => '15:15',
                'Isya' => '19:15'
            ];
        });
    }

    private function getPrayerEndTimes($date, $jadwal)
    {
        return [
            'Subuh' => \Carbon\Carbon::parse($date . ' ' . $jadwal['Fajr'], 'Asia/Jakarta')->addMinutes(15),
            'Dzuhur' => \Carbon\Carbon::parse($date . ' ' . $jadwal['Dhuhr'], 'Asia/Jakarta')->addMinutes(15),
            'Ashar' => \Carbon\Carbon::parse($date . ' ' . $jadwal['Asr'], 'Asia/Jakarta')->addMinutes(15),
            'Maghrib' => \Carbon\Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta')->addMinutes(10),
            'Isya' => \Carbon\Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta')->addMinutes(15),
        ];
    }

    private function resolveDateRange(Request $request)
    {
        $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        if ($request->has('mode') || $request->has('ref_date')) {
            $mode = $request->get('mode', 'day');
            $refDateStr = $request->get('ref_date', $today);
            
            try {
                $refDate = \Carbon\Carbon::parse($refDateStr, 'Asia/Jakarta');
            } catch (\Exception $e) {
                $refDate = \Carbon\Carbon::now('Asia/Jakarta');
            }
            
            if ($mode === 'week') {
                $tanggal_mulai = $refDate->copy()->startOfWeek()->format('Y-m-d');
                $tanggal_akhir = $refDate->copy()->endOfWeek()->format('Y-m-d');
            } elseif ($mode === 'month') {
                $tanggal_mulai = $refDate->copy()->startOfMonth()->format('Y-m-d');
                $tanggal_akhir = $refDate->copy()->endOfMonth()->format('Y-m-d');
            } else {
                $mode = 'day';
                $tanggal_mulai = $refDate->format('Y-m-d');
                $tanggal_akhir = $refDate->format('Y-m-d');
            }
        } else {
            $tanggal_mulai = $request->get('tanggal_mulai', $today);
            $tanggal_akhir = $request->get('tanggal_akhir', $today);
            
            if ($tanggal_mulai === $tanggal_akhir) {
                $mode = 'day';
                $refDateStr = $tanggal_mulai;
            } else {
                $mode = 'day';
                $refDateStr = $tanggal_mulai;
            }
        }
        
        return [
            'mode' => $mode,
            'ref_date' => $refDateStr,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_akhir' => $tanggal_akhir,
        ];
    }

    private function formatIndonesianDate($date, $format = 'day')
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $day = $carbonDate->format('j');
        $month = $months[$carbonDate->month];
        $year = $carbonDate->format('Y');
        
        if ($format === 'month') {
            return "$month $year";
        }
        
        return "$day $month $year";
    }
}
