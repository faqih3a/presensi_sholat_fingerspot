<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

trait DateAndPrayerHelper
{
    protected function resolveDateRange(Request $request)
    {
        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        if ($request->has('mode') || $request->has('ref_date')) {
            $mode = $request->get('mode', 'day');
            $refDateStr = $request->get('ref_date', $today);
            
            try {
                $refDate = Carbon::parse($refDateStr, 'Asia/Jakarta');
            } catch (\Exception $e) {
                $refDate = Carbon::now('Asia/Jakarta');
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

    protected function formatIndonesianDate($date, $format = 'day')
    {
        $carbonDate = Carbon::parse($date);
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

    protected function getJadwalSholat(Carbon $date)
    {
        $address = 'Bogor, Kecamatan Cibeureum, Kp Joglo, Indonesia';
        $cacheKey = 'jadwal_sholat_' . md5($address) . '_' . $date->format('Y-m-d');

        return Cache::remember($cacheKey, 86400, function () use ($date, $address) {
            try {
                $response = Http::timeout(5)->get('https://api.aladhan.com/v1/timingsByAddress', [
                    'address' => $address,
                    'method' => 20, // Kemenag RI
                    'date' => $date->format('d-m-Y')
                ]);

                if ($response->successful()) {
                    $timings = $response->json('data.timings');
                    foreach ($timings as $key => $time) {
                        $timings[$key] = substr($time, 0, 5);
                    }
                    return $timings;
                }
            } catch (\Exception $e) {
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

    protected function getPrayerEndTimes($date, $jadwal)
    {
        return [
            'Subuh' => Carbon::parse($date . ' ' . $jadwal['Fajr'], 'Asia/Jakarta')->addMinutes(15),
            'Dzuhur' => Carbon::parse($date . ' ' . $jadwal['Dhuhr'], 'Asia/Jakarta')->addMinutes(15),
            'Ashar' => Carbon::parse($date . ' ' . $jadwal['Asr'], 'Asia/Jakarta')->addMinutes(15),
            'Maghrib' => Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta')->addMinutes(10),
            'Isya' => Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta')->addMinutes(15),
        ];
    }

    protected function syncAlfas($santriId = null)
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');
        
        $jadwal = $this->getJadwalSholat($now);
        if (!$jadwal) return;

        $times = $this->getPrayerEndTimes($today, $jadwal);
        
        if ($santriId) {
            $santris = \App\Models\Santri::where('id', $santriId)->get();
        } else {
            $santris = \App\Models\Santri::all();
        }

        if ($santris->isEmpty()) return;

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
        
        if ($santriId) {
            $cacheKey = 'sync_alfa_' . $yesterday . '_santri_' . $santriId;
        } else {
            $cacheKey = 'sync_alfa_' . $yesterday;
        }

        $hasYesterdaySync = Cache::get($cacheKey);
        if (!$hasYesterdaySync) {
            $yesterdaySholats = ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'];
            $this->syncAlfasForDate($yesterday, $yesterdaySholats, $santris);
            Cache::put($cacheKey, true, 86400);
        }
    }

    protected function syncAlfasForDate($dateStr, $sholatsToSync, $santris)
    {
        $izinUserIds = \App\Models\Izin::where('status', 'Disetujui')
            ->where(function ($q) use ($dateStr) {
                $q->whereDate('tanggal_mulai', '<=', $dateStr)
                  ->whereDate('tanggal_selesai', '>=', $dateStr);
            })
            ->pluck('user_id')
            ->toArray();

        foreach ($sholatsToSync as $sholat) {
            $santriIds = $santris->pluck('id')->toArray();
            
            $presentSantriIds = \App\Models\Presensi::where('tanggal', $dateStr)
                                        ->where('waktu_sholat', $sholat)
                                        ->whereIn('santri_id', $santriIds)
                                        ->pluck('santri_id')
                                        ->toArray();

            $existingPresensis = \App\Models\Presensi::where('tanggal', $dateStr)
                                        ->where('waktu_sholat', $sholat)
                                        ->whereIn('santri_id', $santriIds)
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
                \App\Models\Presensi::insert($newPresensis);
            }
        }
    }
}
