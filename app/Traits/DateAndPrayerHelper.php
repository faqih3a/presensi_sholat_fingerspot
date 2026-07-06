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
                $response = Http::timeout(2)->get('https://api.aladhan.com/v1/timingsByAddress', [
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
            'Maghrib' => Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta')->addMinutes(15),
            'Isya' => Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta')->addMinutes(15),
        ];
    }

    /**
     * Menghitung prev_date, next_date, dan display_date berdasarkan mode navigasi.
     *
     * Mengeliminasi duplikasi kode navigasi tanggal yang sebelumnya
     * di-copy-paste di DashboardController, IzinController, dan TesController.
     *
     * @param  string  $mode           Mode navigasi: 'day', 'week', atau 'month'.
     * @param  string  $refDateStr     Tanggal referensi (Y-m-d).
     * @param  string  $tanggalMulai   Tanggal awal range (Y-m-d), untuk display.
     * @return array   ['prev_date', 'next_date', 'display_date']
     */
    protected function resolveNavigation(string $mode, string $refDateStr, string $tanggalMulai): array
    {
        $refDate = Carbon::parse($refDateStr, 'Asia/Jakarta');

        return match ($mode) {
            'week' => [
                'prev_date'    => $refDate->copy()->subWeek()->format('Y-m-d'),
                'next_date'    => $refDate->copy()->addWeek()->format('Y-m-d'),
                'display_date' => $this->formatIndonesianDate($tanggalMulai, 'month'),
            ],
            'month' => [
                'prev_date'    => $refDate->copy()->subMonth()->format('Y-m-d'),
                'next_date'    => $refDate->copy()->addMonth()->format('Y-m-d'),
                'display_date' => $this->formatIndonesianDate($tanggalMulai, 'month'),
            ],
            default => [
                'prev_date'    => $refDate->copy()->subDay()->format('Y-m-d'),
                'next_date'    => $refDate->copy()->addDay()->format('Y-m-d'),
                'display_date' => $this->formatIndonesianDate($tanggalMulai, 'month'),
            ],
        };
    }
}

