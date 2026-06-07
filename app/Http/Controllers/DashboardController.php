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
        $syncStart = \Carbon\Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
        $syncEnd = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $rawLogs = $this->fingerspotService->syncAttendance($syncStart, $syncEnd);
        $this->syncAlfas();

        $recentActivities = collect($rawLogs)->map(function ($log) {
            $pin = $log['pin'] ?? null;
            $scanTimeStr = $log['scan_date'] ?? $log['datetime'] ?? $log['scan'] ?? null;
            
            if (!$pin || !$scanTimeStr) {
                return null;
            }
            
            $name = 'PIN ' . $pin;
            $role = 'unknown';
            $detail = 'Belum terdaftar';
            $avatar = null;
            
            $santri = \App\Models\Santri::where('fingerspot_pin', $pin)->first();
            if ($santri) {
                $name = $santri->nama;
                $role = 'santri';
                $detail = 'Kelas ' . $santri->kelas;
                $avatar = $santri->foto_referensi ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;
            } else {
                $user = \App\Models\User::where('fingerspot_pin', $pin)->first();
                if ($user) {
                    $name = $user->name;
                    $role = $user->role;
                    $detail = ucfirst($user->role);
                    $avatar = $user->avatar ? asset('storage/avatars/' . $user->avatar) : null;
                }
            }
            
            $verifyVal = $log['verify'] ?? null;
            $verifyMethod = 'Unknown';
            $verifyIcon = 'bi-question-circle';
            switch ($verifyVal) {
                case '1':
                    $verifyMethod = 'Fingerprint';
                    $verifyIcon = 'bi-fingerprint';
                    break;
                case '2':
                    $verifyMethod = 'Password';
                    $verifyIcon = 'bi-key-fill';
                    break;
                case '3':
                    $verifyMethod = 'Card';
                    $verifyIcon = 'bi-card-list';
                    break;
                case '4':
                    $verifyMethod = 'Face';
                    $verifyIcon = 'bi-person-bounding-box';
                    break;
                case '6':
                    $verifyMethod = 'Vein';
                    $verifyIcon = 'bi-hand-index-thumb';
                    break;
                case '7':
                    $verifyMethod = 'QR Code';
                    $verifyIcon = 'bi-qr-code-scan';
                    break;
            }
            
            $statusScanVal = $log['status_scan'] ?? null;
            $statusScanLabel = 'Scan';
            switch ($statusScanVal) {
                case '0':
                    $statusScanLabel = 'Scan Masuk';
                    break;
                case '1':
                    $statusScanLabel = 'Scan Keluar';
                    break;
                case '2':
                    $statusScanLabel = 'Break In';
                    break;
                case '3':
                    $statusScanLabel = 'Break Out';
                    break;
                case '4':
                    $statusScanLabel = 'Overtime In';
                    break;
                case '5':
                    $statusScanLabel = 'Overtime Out';
                    break;
                case '6':
                    $statusScanLabel = 'Rapat In';
                    break;
                case '7':
                    $statusScanLabel = 'Rapat Out';
                    break;
                case '8':
                    $statusScanLabel = 'Custom 1';
                    break;
                case '9':
                    $statusScanLabel = 'Custom 2';
                    break;
            }

            try {
                $carbonScan = \Carbon\Carbon::parse($scanTimeStr, 'Asia/Jakarta');
            } catch (\Exception $e) {
                $carbonScan = \Carbon\Carbon::now('Asia/Jakarta');
            }

            return (object) [
                'pin' => $pin,
                'name' => $name,
                'role' => $role,
                'detail' => $detail,
                'avatar' => $avatar,
                'scan_time' => $carbonScan,
                'verify_method' => $verifyMethod,
                'verify_icon' => $verifyIcon,
                'status_scan_label' => $statusScanLabel,
                'photo_url' => $log['photo_url'] ?? null,
            ];
        })
        ->filter()
        ->sortByDesc('scan_time')
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
        $syncStart = \Carbon\Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
        $syncEnd = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $this->fingerspotService->syncAttendance($syncStart, $syncEnd);
        $this->syncAlfas();

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

        // Synthesize missing records for the entire range
        $synthesized = collect();
        $sholats = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
        $sholatList = $waktuSholat ? [$waktuSholat] : $sholats;

        $santriQuery = Santri::query();
        if ($search) $santriQuery->where('nama', 'like', '%' . $search . '%');
        $santris = $santriQuery->get();

        $currentDate = \Carbon\Carbon::parse($tanggal_mulai, 'Asia/Jakarta');
        $endRangeDate = \Carbon\Carbon::parse($tanggal_akhir, 'Asia/Jakarta');

        while ($currentDate->lte($endRangeDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $jadwal = $this->getJadwalSholat($currentDate);
            $times = $jadwal ? $this->getPrayerEndTimes($dateStr, $jadwal) : [];

            foreach ($santris as $santri) {
                foreach ($sholatList as $s) {
                    if (!isset($realRecordsGrouped[$dateStr][$santri->id][$s])) {
                        $hasIzin = Izin::where('user_id', $santri->user_id)
                                                ->where('status', 'Disetujui')
                                                ->whereDate('tanggal_mulai', '<=', $dateStr)
                                                ->whereDate('tanggal_selesai', '>=', $dateStr)
                                                ->exists();

                        $virtualStatus = $hasIzin ? 'Izin' : 'Alfa';
                        
                        if (!$status || $status === $virtualStatus) {
                            // Check if time has passed for this prayer
                            if ($dateStr === $now->format('Y-m-d') && $jadwal && isset($times[$s])) {
                                if ($now->lessThan($times[$s])) {
                                    continue; // Skip if it's not yet time to be Alfa today
                                }
                            } elseif ($currentDate->greaterThan($now)) {
                                continue; // Skip future dates
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

        return view('dashboard.kehadiran', compact(
            'presensis', 'tanggal_mulai', 'tanggal_akhir', 'waktuSholat', 'status',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date'
        ));
    }

    public function exportKehadiran(Request $request)
    {
        $resolvedDates = $this->resolveDateRange($request);
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        // Only sync today and yesterday to prevent API latency during dashboard loads
        $syncStart = \Carbon\Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
        $syncEnd = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $this->fingerspotService->syncAttendance($syncStart, $syncEnd);

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

        while ($currentDate->lte($endRangeDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $jadwal = $this->getJadwalSholat($currentDate);
            $times = $jadwal ? $this->getPrayerEndTimes($dateStr, $jadwal) : [];

            foreach ($santris as $santri) {
                foreach ($sholatList as $s) {
                    if (!isset($realRecordsGrouped[$dateStr][$santri->id][$s])) {
                        $hasIzin = Izin::where('user_id', $santri->user_id)
                                                ->where('status', 'Disetujui')
                                                ->whereDate('tanggal_mulai', '<=', $dateStr)
                                                ->whereDate('tanggal_selesai', '>=', $dateStr)
                                                ->exists();

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
        
        $filename = "rekap_kehadiran_" . date('Y-m-d_H-i-s') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        
        $columns = ['No', 'PIN', 'Nama Santri', 'Kelas', 'Waktu Sholat', 'Tanggal', 'Waktu Hadir', 'Metode Verifikasi', 'Status Scan', 'Status'];
        
        $callback = function() use($presensis, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
            fputcsv($file, $columns);
            $no = 1;
            foreach ($presensis as $presensi) {
                $verifyMethod = ($presensi instanceof \App\Models\Presensi) ? $presensi->verify_method_label : '-';
                $statusScan = ($presensi instanceof \App\Models\Presensi) ? $presensi->status_scan_label : '-';
                
                fputcsv($file, [
                    $no++,
                    $presensi->santri->fingerspot_pin ?? '-',
                    $presensi->santri->nama,
                    $presensi->santri->kelas,
                    $presensi->waktu_sholat,
                    \Carbon\Carbon::parse($presensi->tanggal)->format('d M Y'),
                    $presensi->waktu_hadir ? \Carbon\Carbon::parse($presensi->waktu_hadir)->format('H:i:s') : '-',
                    $verifyMethod,
                    $statusScan,
                    $presensi->status
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }



    private function syncAlfas()
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        
        // Ambil jadwal sholat hari ini
        $jadwal = $this->getJadwalSholat($now);
        if (!$jadwal) return;

        // Mapping nama sholat di API ke nama sholat di sistem
        $mapping = [
            'Fajr' => 'Subuh',
            'Dhuhr' => 'Dzuhur',
            'Asr' => 'Ashar',
            'Maghrib' => 'Maghrib',
            'Isha' => 'Isya'
        ];

        // Tentukan batas waktu sholat (misal: sholat dianggap selesai saat waktu sholat berikutnya tiba)
        // Kecuali Isya yang kita beri batas misal jam 23:59 atau Fajr besok.
        $times = $this->getPrayerEndTimes($today, $jadwal);

        $santris = \App\Models\Santri::all();

        foreach ($times as $sholat => $endTime) {
            if ($now->greaterThan($endTime)) {
                // Cari santri yang TIDAK punya record presensi untuk sholat ini hari ini
                $presentSantriIds = Presensi::where('tanggal', $today)
                                            ->where('waktu_sholat', $sholat)
                                            ->pluck('santri_id')
                                            ->toArray();

                foreach ($santris as $santri) {
                    if (!in_array($santri->id, $presentSantriIds)) {
                        // Cek apakah santri punya izin yang disetujui hari ini
                        $hasIzin = \App\Models\Izin::where('user_id', $santri->user_id)
                                                ->where('status', 'Disetujui')
                                                ->whereDate('tanggal_mulai', '<=', $today)
                                                ->whereDate('tanggal_selesai', '>=', $today)
                                                ->exists();
                        
                        $status = $hasIzin ? 'Izin' : 'Alfa';

                        Presensi::firstOrCreate([
                            'santri_id' => $santri->id,
                            'tanggal' => $today,
                            'waktu_sholat' => $sholat,
                        ], [
                            'status' => $status,
                            'waktu_hadir' => null
                        ]);
                    }
                }
            }
        }
        
        // Opsional: Cek juga hari kemarin jika ada yang tertinggal
        $yesterday = $now->copy()->subDay()->format('Y-m-d');
        $hasYesterdaySync = \Illuminate\Support\Facades\Cache::get('sync_alfa_' . $yesterday);
        if (!$hasYesterdaySync) {
            foreach ($mapping as $apiName => $sysName) {
                $presentSantriIds = Presensi::where('tanggal', $yesterday)
                                            ->where('waktu_sholat', $sysName)
                                            ->pluck('santri_id')
                                            ->toArray();

                foreach ($santris as $santri) {
                    if (!in_array($santri->id, $presentSantriIds)) {
                        $hasIzin = \App\Models\Izin::where('user_id', $santri->user_id)
                                                ->where('status', 'Disetujui')
                                                ->whereDate('tanggal_mulai', '<=', $yesterday)
                                                ->whereDate('tanggal_selesai', '>=', $yesterday)
                                                ->exists();
                        
                        $status = $hasIzin ? 'Izin' : 'Alfa';

                        Presensi::firstOrCreate([
                            'santri_id' => $santri->id,
                            'tanggal' => $yesterday,
                            'waktu_sholat' => $sysName,
                        ], [
                            'status' => $status,
                            'waktu_hadir' => null
                        ]);
                    }
                }
            }
            \Illuminate\Support\Facades\Cache::put('sync_alfa_' . $yesterday, true, 86400);
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
            
            return null;
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
