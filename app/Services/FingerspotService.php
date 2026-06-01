<?php

namespace App\Services;

use App\Models\Presensi;
use App\Models\Santri;
use App\Models\Izin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FingerspotService
{
    protected $apiUrl;
    protected $apiToken;
    protected $cloudId;

    public function __construct()
    {
        $this->apiUrl = env('FINGERSPOT_API_URL', 'https://developer.fingerspot.io/api/get_attlog');
        $this->apiToken = env('FINGERSPOT_API_TOKEN');
        $this->cloudId = env('FINGERSPOT_CLOUD_ID');
    }

    /**
     * Fetch user info from Fingerspot API and sync to local database.
     */
    public function syncUsers($pin = null)
    {
        try {
            $users = [];

            // Check if API Token is empty, default or not configured
            if (empty($this->apiToken) || $this->apiToken === 'your_api_token_here' || empty($this->cloudId) || $this->cloudId === 'your_cloud_id_here') {
                Log::info('Fingerspot: Using MOCK users since FINGERSPOT_API_TOKEN/FINGERSPOT_CLOUD_ID is not configured.');
                
                // Mock user data
                $users = [
                    [
                        'pin' => '101',
                        'name' => 'Ahmad Santri',
                        'privilege' => 0, // 0 = standard user (pengguna)
                    ],
                    [
                        'pin' => '102',
                        'name' => 'Budi Santri',
                        'privilege' => 0,
                    ],
                    [
                        'pin' => '103',
                        'name' => 'Candra Santri',
                        'privilege' => 0,
                    ],
                    [
                        'pin' => '201',
                        'name' => 'Ustadz Hasan',
                        'privilege' => 14, // non-zero = admin/asatidz
                    ],
                    [
                        'pin' => '202',
                        'name' => 'Ustadz Salim',
                        'privilege' => 14,
                    ],
                ];

                $syncedCount = $this->syncUserList($users);
                return [
                    'success' => true,
                    'message' => "Sinkronisasi MOCK berhasil! {$syncedCount['santri']} santri dan {$syncedCount['asatidz']} asatidz disinkronkan.",
                ];
            } else {
                $userinfoUrl = str_replace('get_attlog', 'get_userinfo', $this->apiUrl);
                
                $postData = [
                    'trans_id' => (string) time(),
                    'cloud_id' => $this->cloudId,
                ];

                if ($pin !== null) {
                    $postData['pin'] = (string) $pin;
                }
                
                // Perform real HTTP request to Fingerspot API to trigger userinfo command
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ])->timeout(10)->post($userinfoUrl, $postData);

                if (!$response->successful()) {
                    Log::warning('Fingerspot Userinfo API request failed: ' . $response->body());
                    return [
                        'success' => false,
                        'message' => 'Fingerspot API request failed: ' . $response->status(),
                    ];
                }

                $result = $response->json();
                
                // In case fingerspot sends user data synchronously in the direct response
                $directUsers = [];
                if (isset($result['data']) && is_array($result['data'])) {
                    $directUsers = $result['data'];
                } elseif (isset($result['users']) && is_array($result['users'])) {
                    $directUsers = $result['users'];
                }

                if (!empty($directUsers)) {
                    $syncedCount = $this->syncUserList($directUsers);
                    return [
                        'success' => true,
                        'message' => "Sinkronisasi berhasil! {$syncedCount['santri']} santri dan {$syncedCount['asatidz']} asatidz disinkronkan.",
                    ];
                }

                // Standard asynchronous behavior: command is sent, data comes via Webhook
                return [
                    'success' => true,
                    'message' => 'Perintah pengambilan data pengguna telah dikirim ke mesin absensi. Data akan disinkronkan melalui webhook beberapa saat lagi.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Fingerspot users sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch logs from Fingerspot API and sync to local MySQL database.
     */
    public function syncAttendance($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?: Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $endDate = $endDate ?: Carbon::now('Asia/Jakarta')->format('Y-m-d');

        try {
            $logs = [];

            // Check if API Token is empty, default or not configured
            if (empty($this->apiToken) || $this->apiToken === 'your_api_token_here' || empty($this->cloudId) || $this->cloudId === 'your_cloud_id_here') {
                Log::info('Fingerspot: Using MOCK logs for testing since FINGERSPOT_API_TOKEN/FINGERSPOT_CLOUD_ID is not configured.');
                
                // Retrieve all santri with a fingerspot_pin to generate mock data
                $allSantris = Santri::whereNotNull('fingerspot_pin')
                                    ->where('fingerspot_pin', '!=', '')
                                    ->get();
                
                $start = Carbon::parse($startDate, 'Asia/Jakarta');
                $end = Carbon::parse($endDate, 'Asia/Jakarta');
                
                while ($start->lte($end)) {
                    $dateStr = $start->format('Y-m-d');
                    $jadwal = $this->getJadwalSholat($start);
                    if ($jadwal) {
                        foreach ($allSantris as $santri) {
                            // Generate mock logs for each sholat slot with some randomness
                            // Subuh
                            if (rand(0, 10) < 9) {
                                $fajrTime = Carbon::createFromFormat('H:i', $jadwal['Fajr'] ?? '04:30')
                                    ->addMinutes(rand(-15, 10))
                                    ->format('H:i');
                                $logs[] = [
                                    'pin' => $santri->fingerspot_pin,
                                    'scan_date' => "$dateStr $fajrTime",
                                    'verify' => (string)rand(1, 4),
                                    'status_scan' => '0',
                                ];
                            }
                            // Dzuhur
                            if (rand(0, 10) < 9) {
                                $dhuhrTime = Carbon::createFromFormat('H:i', $jadwal['Dhuhr'] ?? '12:00')
                                    ->addMinutes(rand(-15, 10))
                                    ->format('H:i');
                                $logs[] = [
                                    'pin' => $santri->fingerspot_pin,
                                    'scan_date' => "$dateStr $dhuhrTime",
                                    'verify' => (string)rand(1, 4),
                                    'status_scan' => '0',
                                ];
                            }
                            // Ashar
                            if (rand(0, 10) < 9) {
                                $asrTime = Carbon::createFromFormat('H:i', $jadwal['Asr'] ?? '15:15')
                                    ->addMinutes(rand(-15, 10))
                                    ->format('H:i');
                                $logs[] = [
                                    'pin' => $santri->fingerspot_pin,
                                    'scan_date' => "$dateStr $asrTime",
                                    'verify' => (string)rand(1, 4),
                                    'status_scan' => '0',
                                ];
                            }
                            // Maghrib
                            if (rand(0, 10) < 9) {
                                $maghribTime = Carbon::createFromFormat('H:i', $jadwal['Maghrib'] ?? '18:00')
                                    ->addMinutes(rand(-15, 7))
                                    ->format('H:i');
                                $logs[] = [
                                    'pin' => $santri->fingerspot_pin,
                                    'scan_date' => "$dateStr $maghribTime",
                                    'verify' => (string)rand(1, 4),
                                    'status_scan' => '0',
                                ];
                            }
                            // Isya
                            if (rand(0, 10) < 9) {
                                $ishaTime = Carbon::createFromFormat('H:i', $jadwal['Isha'] ?? '19:15')
                                    ->addMinutes(rand(-15, 10))
                                    ->format('H:i');
                                $logs[] = [
                                    'pin' => $santri->fingerspot_pin,
                                    'scan_date' => "$dateStr $ishaTime",
                                    'verify' => (string)rand(1, 4),
                                    'status_scan' => '0',
                                ];
                            }
                        }
                    }
                    $start->addDay();
                }
            } else {
                // Perform real HTTP request to Fingerspot API in chunks of max 2 days
                $start = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
                $end = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay();
                
                // Limit to the last 60 days
                $limitDate = Carbon::now('Asia/Jakarta')->subDays(60)->startOfDay();
                if ($start->lessThan($limitDate)) {
                    $start = $limitDate->copy();
                }
                
                if ($start->greaterThan($end)) {
                    return [];
                }
                
                $current = $start->copy();
                while ($current->lte($end)) {
                    $chunkStartStr = $current->format('Y-m-d');
                    
                    // End of the chunk is at most 1 day after start (so 2 days total: e.g. 2026-05-20 to 2026-05-21)
                    $chunkEnd = $current->copy()->addDay();
                    if ($chunkEnd->greaterThan($end)) {
                        $chunkEnd = $end->copy();
                    }
                    $chunkEndStr = $chunkEnd->format('Y-m-d');
                    
                    // Cache chunk-specific sync call to prevent redundant API hits
                    $chunkCacheKey = "fingerspot_sync_{$chunkStartStr}_{$chunkEndStr}";
                    
                    // Check if chunk is recent (today, yesterday, or tomorrow)
                    $todayStr = Carbon::now('Asia/Jakarta')->format('Y-m-d');
                    $yesterdayStr = Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');
                    $isRecent = ($chunkStartStr === $todayStr || $chunkEndStr === $todayStr || $chunkStartStr === $yesterdayStr || $chunkEndStr === $yesterdayStr);
                    $cacheTime = $isRecent ? 10 : 3600; // 10 seconds for recent, 1 hour for past static data
                    
                    if (!Cache::has($chunkCacheKey)) {
                        Log::info("Fingerspot: Fetching attlog chunk from {$chunkStartStr} to {$chunkEndStr}");
                        
                        $response = Http::withHeaders([
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $this->apiToken,
                        ])->timeout(12)->post($this->apiUrl, [
                            'trans_id' => (string) time(),
                            'cloud_id' => $this->cloudId,
                            'start_date' => $chunkStartStr,
                            'end_date' => $chunkEndStr,
                        ]);
                        
                        if ($response->successful()) {
                            $result = $response->json();
                            $chunkLogs = [];
                            
                            if (isset($result['data']) && is_array($result['data'])) {
                                $chunkLogs = $result['data'];
                            } elseif (isset($result['logs']) && is_array($result['logs'])) {
                                $chunkLogs = $result['logs'];
                            }
                            
                            if (!empty($chunkLogs)) {
                                $logs = array_merge($logs, $chunkLogs);
                            }
                            
                            Cache::put($chunkCacheKey, true, $cacheTime);
                        } else {
                            Log::warning("Fingerspot: API request failed for chunk {$chunkStartStr} - {$chunkEndStr}: " . $response->body());
                            // Cache shortly on failure to avoid hitting the API immediately again
                            Cache::put($chunkCacheKey, false, 5);
                        }
                    }
                    
                    // Move current to chunkEnd + 1 day
                    $current = $chunkEnd->copy()->addDay();
                }
            }

            if (empty($logs)) {
                return [];
            }

            $this->processAttlogs($logs);
            return $logs;
        } catch (\Exception $e) {
            Log::error('Fingerspot sync error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Process array of attlogs to database.
     */
    public function processAttlogs(array $logs)
    {
        // Retrieve all santris indexed by fingerspot_pin
        $santris = Santri::whereNotNull('fingerspot_pin')
                        ->where('fingerspot_pin', '!=', '')
                        ->get()
                        ->keyBy('fingerspot_pin');

        $prayerSchedules = [];

        foreach ($logs as $log) {
            $pin = $log['pin'] ?? null;
            $scanTimeStr = $log['scan_date'] ?? $log['datetime'] ?? $log['scan'] ?? null;

            if (!$pin || !$scanTimeStr) {
                continue;
            }

            $santri = $santris->get($pin);
            if (!$santri) {
                // Auto-register student if PIN does not exist
                $name = $log['name'] ?? $log['username'] ?? 'Santri ' . $pin;
                $email = 'santri.' . $pin . '@fingerspot.io';
                
                $userModel = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make($pin),
                        'role' => 'santri',
                        'fingerspot_pin' => $pin,
                    ]
                );
                
                $santri = Santri::create([
                    'user_id' => $userModel->id,
                    'nama' => $name,
                    'kelas' => 'Fingerspot',
                    'foto_referensi' => '',
                    'face_descriptor' => '[]',
                    'fingerspot_pin' => $pin,
                ]);
                
                // Add to our runtime map to prevent duplicate creates
                $santris->put($pin, $santri);
            }

            $scanDateTime = Carbon::parse($scanTimeStr, 'Asia/Jakarta');
            $dateStr = $scanDateTime->format('Y-m-d');
            $timeStr = $scanDateTime->format('H:i');

            // Fetch or retrieve cached prayer times for the log date
            if (!isset($prayerSchedules[$dateStr])) {
                $prayerSchedules[$dateStr] = $this->getJadwalSholat($scanDateTime);
            }

            $jadwal = $prayerSchedules[$dateStr];
            if (!$jadwal) {
                continue;
            }

            // Determine matching prayer session
            $waktuSholat = $this->determinePrayerTime($timeStr, $jadwal);
            if (!$waktuSholat) {
                continue; // Not within active prayer windows
            }

            // Check for approved permit
            $hasIzin = Izin::where('user_id', $santri->user_id)
                            ->where('status', 'Disetujui')
                            ->whereDate('tanggal_mulai', '<=', $dateStr)
                            ->whereDate('tanggal_selesai', '>=', $dateStr)
                            ->exists();

            $status = $hasIzin ? 'Izin' : 'Hadir';
            
            // Extract the AWS photo URL or other image URL
            $photoUrl = $log['image_url'] ?? $log['photo_url'] ?? $log['photo'] ?? $log['image'] ?? $log['photo_link'] ?? null;

            // Look for existing presensi record
            $existing = Presensi::where('santri_id', $santri->id)
                                ->where('tanggal', $dateStr)
                                ->where('waktu_sholat', $waktuSholat)
                                ->first();

            if ($existing) {
                // Update only if status is Alfa, or update the time if Hadir scan is newer/earlier
                if ($existing->status === 'Alfa') {
                    $existing->update([
                        'waktu_hadir' => $timeStr,
                        'status' => $status,
                        'photo_url' => $photoUrl
                    ]);
                }
            } else {
                Presensi::create([
                    'santri_id' => $santri->id,
                    'waktu_sholat' => $waktuSholat,
                    'tanggal' => $dateStr,
                    'waktu_hadir' => $timeStr,
                    'status' => $status,
                    'photo_url' => $photoUrl
                ]);
            }
        }
    }

    /**
     * Sync user list array to database.
     * Returns array of synced counts.
     */
    public function syncUserList(array $users)
    {
        $syncedSantriCount = 0;
        $syncedAsatidzCount = 0;

        foreach ($users as $user) {
            $pin = $user['pin'] ?? $user['pin_number'] ?? $user['user_id'] ?? null;
            $name = $user['name'] ?? $user['nama'] ?? $user['username'] ?? null;
            
            $privRaw = $user['privilege'] ?? $user['priv'] ?? $user['hak_akses'] ?? 0;
            $isAdmin = false;
            
            if (is_numeric($privRaw)) {
                $privInt = (int)$privRaw;
                if ($privInt > 0) {
                    $isAdmin = true;
                }
            } else {
                $privStr = strtolower(trim((string)$privRaw));
                if ($privStr === 'admin' || $privStr === 'asatidz' || $privStr === 'administrator') {
                    $isAdmin = true;
                }
            }

            if (!$pin || !$name) {
                continue;
            }

            // If not admin, it's a normal user (santri)
            if (!$isAdmin) {
                // Check if Santri with this fingerspot_pin already exists
                $santri = Santri::where('fingerspot_pin', $pin)->first();

                if ($santri) {
                    // Update existing santri and user details
                    $santri->update([
                        'nama' => $name,
                    ]);
                    if ($santri->user) {
                        $santri->user->update([
                            'name' => $name,
                            'fingerspot_pin' => $pin,
                        ]);
                    }
                } else {
                    // Create a new User
                    $email = 'santri.' . $pin . '@fingerspot.io';
                    
                    // Check if user with this email already exists
                    $existingUser = User::where('email', $email)->first();
                    if ($existingUser) {
                        $userModel = $existingUser;
                        $userModel->update([
                            'name' => $name,
                            'role' => 'santri',
                            'fingerspot_pin' => $pin,
                        ]);
                    } else {
                        $userModel = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make($pin), // use PIN as default password
                            'role' => 'santri',
                            'fingerspot_pin' => $pin,
                        ]);
                    }

                    // Create Santri record
                    Santri::create([
                        'user_id' => $userModel->id,
                        'nama' => $name,
                        'kelas' => 'Fingerspot', // Default class
                        'foto_referensi' => '',
                        'face_descriptor' => '[]',
                        'fingerspot_pin' => $pin,
                    ]);
                }
                $syncedSantriCount++;
            } else {
                // If privilege is non-zero, it's an admin (asatidz)
                // Check if User with this fingerspot_pin already exists
                $asatidzUser = User::where('role', 'asatidz')
                                   ->where('fingerspot_pin', $pin)
                                   ->first();

                if ($asatidzUser) {
                    $asatidzUser->update([
                        'name' => $name,
                    ]);
                } else {
                    $email = 'asatidz.' . $pin . '@fingerspot.io';
                    
                    // Check if user with this email already exists
                    $existingUser = User::where('email', $email)->first();
                    if ($existingUser) {
                        $existingUser->update([
                            'name' => $name,
                            'role' => 'asatidz',
                            'fingerspot_pin' => $pin,
                        ]);
                    } else {
                        User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make($pin), // use PIN as default password
                            'role' => 'asatidz',
                            'fingerspot_pin' => $pin,
                        ]);
                    }
                }
                $syncedAsatidzCount++;
            }
        }

        return [
            'santri' => $syncedSantriCount,
            'asatidz' => $syncedAsatidzCount
        ];
    }

    /**
     * Map current time to prayer slot
     */
    private function determinePrayerTime($currentTime, $jadwal)
    {
        foreach (['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'] as $sholat) {
            if ($this->isTimeInPrayerWindow($currentTime, $sholat, $jadwal)) {
                return $sholat;
            }
        }
        return null;
    }

    /**
     * Check if a given time is in a prayer slot scan window (30 mins before to 15 mins after, Maghrib is 10 mins after)
     */
    private function isTimeInPrayerWindow($currentTime, $sholat, $jadwal)
    {
        $fajr = $jadwal['Fajr'] ?? '04:00';
        $dhuhr = $jadwal['Dhuhr'] ?? '11:30';
        $asr = $jadwal['Asr'] ?? '14:30';
        $maghrib = $jadwal['Maghrib'] ?? '17:30';
        $isha = $jadwal['Isha'] ?? '18:45';

        $getStart = function($timeStr) {
            try {
                return Carbon::createFromFormat('H:i', $timeStr)->subMinutes(30)->format('H:i');
            } catch (\Exception $e) {
                return $timeStr;
            }
        };

        $getEnd = function($timeStr, $sholatName) {
            try {
                $minutes = ($sholatName === 'Maghrib') ? 10 : 15;
                return Carbon::createFromFormat('H:i', $timeStr)->addMinutes($minutes)->format('H:i');
            } catch (\Exception $e) {
                return $timeStr;
            }
        };

        $fajrStart = $getStart($fajr);
        $dhuhrStart = $getStart($dhuhr);
        $asrStart = $getStart($asr);
        $maghribStart = $getStart($maghrib);
        $ishaStart = $getStart($isha);

        $fajrEnd = $getEnd($fajr, 'Subuh');
        $dhuhrEnd = $getEnd($dhuhr, 'Dzuhur');
        $asrEnd = $getEnd($asr, 'Ashar');
        $maghribEnd = $getEnd($maghrib, 'Maghrib');
        $ishaEnd = $getEnd($isha, 'Isya');

        switch ($sholat) {
            case 'Subuh':
                return $currentTime >= $fajrStart && $currentTime <= $fajrEnd;
            case 'Dzuhur':
                return $currentTime >= $dhuhrStart && $currentTime <= $dhuhrEnd;
            case 'Ashar':
                return $currentTime >= $asrStart && $currentTime <= $asrEnd;
            case 'Maghrib':
                return $currentTime >= $maghribStart && $currentTime <= $maghribEnd;
            case 'Isya':
                return $currentTime >= $ishaStart && $currentTime <= $ishaEnd;
        }

        return false;
    }

    /**
     * Get local cached prayer times or request from API
     */
    private function getJadwalSholat(Carbon $date)
    {
        $address = 'Bogor, Kecamatan Cibeureum, Kp Joglo, Indonesia';
        $cacheKey = 'jadwal_sholat_' . md5($address) . '_' . $date->format('Y-m-d');

        return Cache::remember($cacheKey, 86400, function () use ($date, $address) {
            try {
                $response = Http::timeout(5)->get('https://api.aladhan.com/v1/timingsByAddress', [
                    'address' => $address,
                    'method' => 20,
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
                // Log error
            }
            return null;
        });
    }
}
