<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Santri;
use App\Models\Presensi;
use App\Models\Izin;
use App\Traits\DateAndPrayerHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncAlfaEvent extends Command
{
    use DateAndPrayerHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:sync-alfa-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Alfa records for the prayer time whose tolerance limit has just passed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->format('Y-m-d');

        $this->info("Starting Alfa sync check at " . $now->toDateTimeString());

        // 1. Get prayer schedules for today
        $jadwal = $this->getJadwalSholat($now);
        if (!$jadwal) {
            $this->error('Failed to retrieve prayer times.');
            return 1;
        }

        // 2. Determine prayer end times (including tolerance)
        $times = $this->getPrayerEndTimes($today, $jadwal);

        foreach ($times as $prayer => $endTime) {
            // "baru saja terlewat" window: now >= endTime AND now < endTime + 15 minutes
            $windowEnd = $endTime->copy()->addMinutes(15);

            if ($now->greaterThanOrEqualTo($endTime) && $now->lessThan($windowEnd)) {
                $cacheKey = "alfa_recorded_{$today}_{$prayer}";

                if (Cache::has($cacheKey)) {
                    $this->info("Alfa records for {$prayer} today ({$today}) have already been recorded. Skipping.");
                    continue;
                }

                $this->info("Tolerance window for {$prayer} just passed (End time: " . $endTime->toTimeString() . "). Recording Alfas...");
                
                try {
                    $this->syncAlfasForSpecificPrayer($today, $prayer);
                    Cache::put($cacheKey, true, 86400);
                    $this->info("Alfa records for {$prayer} today successfully recorded.");
                    Log::info("Auto-Alfa recorded for {$prayer} on {$today}.");
                } catch (\Exception $e) {
                    $this->error("Error syncing Alfa for {$prayer}: " . $e->getMessage());
                    Log::error("Error syncing Alfa for {$prayer} on {$today}: " . $e->getMessage());
                }
            }
        }

        $this->info("Alfa sync check completed.");
        return 0;
    }

    /**
     * Logic to record Alfa/Izin for a specific prayer time.
     */
    protected function syncAlfasForSpecificPrayer($dateStr, $sholat)
    {
        $santris = Santri::all();
        if ($santris->isEmpty()) {
            $this->info("No santri found. Skipping.");
            return;
        }

        // Get user IDs of santris who have an approved permit covering the date
        $izinUserIds = Izin::where('status', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $dateStr)
            ->whereDate('tanggal_selesai', '>=', $dateStr)
            ->pluck('user_id')
            ->toArray();

        $santriIds = $santris->pluck('id')->toArray();

        // Get santri IDs who ALREADY have a record for this prayer time on this date (Hadir/Izin/Alfa)
        $existingPresensiSantriIds = Presensi::where('tanggal', $dateStr)
            ->where('waktu_sholat', $sholat)
            ->whereIn('santri_id', $santriIds)
            ->pluck('santri_id')
            ->toArray();

        $newPresensis = [];
        foreach ($santris as $santri) {
            // Only insert if no presensi record exists at all for this santri and prayer time
            if (!in_array($santri->id, $existingPresensiSantriIds)) {
                $hasIzin = in_array($santri->user_id, $izinUserIds);
                $status = $hasIzin ? 'Izin' : 'Alfa';

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

        if (!empty($newPresensis)) {
            Presensi::insert($newPresensis);
            $this->info("Inserted " . count($newPresensis) . " absent (Alfa/Izin) records.");
        } else {
            $this->info("All santri already have attendance records. No records inserted.");
        }
    }
}
