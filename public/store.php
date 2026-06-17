<?php

/**
 * ====================================================================
 * Webhook Realtime Attlog - FingerSpot
 * ====================================================================
 * 
 * Endpoint: POST https://masjidnurulilmi.site/store.php
 * 
 * Menerima data absensi realtime dari mesin fingerspot,
 * lalu menyimpannya ke tabel `presensis` via Laravel Eloquent.
 * 
 * Flow:
 * 1. Terima JSON body dari mesin
 * 2. Validasi type = "attlog"
 * 3. Mapping pin → santri_id (pin di mesin = id di tabel santris)
 * 4. Tentukan waktu_sholat berdasarkan waktu scan
 * 5. Simpan/update ke tabel presensis
 * 6. Log raw data ke file untuk debugging
 * ====================================================================
 */

// ─── Bootstrap Laravel ──────────────────────────────────────────────
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application (initializes DB, config, etc.)
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Santri;
use App\Models\Presensi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

// ─── Helper: Ambil Jadwal Sholat ────────────────────────────────────
function getJadwalSholat(Carbon $date): array
{
    $address = 'Bogor, Kecamatan Cibeureum, Kp Joglo, Indonesia';
    $cacheKey = 'jadwal_sholat_' . md5($address) . '_' . $date->format('Y-m-d');

    return Cache::remember($cacheKey, 86400, function () use ($date, $address) {
        try {
            $response = Http::timeout(2)->get('https://api.aladhan.com/v1/timingsByAddress', [
                'address' => $address,
                'method'  => 20, // Kemenag RI
                'date'    => $date->format('d-m-Y'),
            ]);

            if ($response->successful()) {
                $timings = $response->json('data.timings');
                foreach ($timings as $key => $time) {
                    $timings[$key] = substr($time, 0, 5);
                }
                return $timings;
            }
        } catch (\Exception $e) {
            // Fallback below
        }

        // Fallback jadwal jika API gagal
        return [
            'Fajr'    => '04:30',
            'Dhuhr'   => '12:00',
            'Asr'     => '15:15',
            'Maghrib' => '18:00',
            'Isha'    => '19:15',
        ];
    });
}

// ─── Helper: Tentukan Waktu Sholat dari Jam Scan ────────────────────
function determineWaktuSholat(Carbon $scanTime, array $jadwal): ?string
{
    $date = $scanTime->format('Y-m-d');

    // Definisi rentang waktu presensi setiap sholat:
    // Mulai = 30 menit sebelum adzan, Selesai = 10 menit setelah adzan
    $ranges = [
        'Subuh'   => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Fajr'], 'Asia/Jakarta')->subMinutes(30),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Fajr'], 'Asia/Jakarta')->addMinutes(10),
        ],
        'Dzuhur'  => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Dhuhr'], 'Asia/Jakarta')->subMinutes(30),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Dhuhr'], 'Asia/Jakarta')->addMinutes(10),
        ],
        'Ashar'   => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Asr'], 'Asia/Jakarta')->subMinutes(30),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Asr'], 'Asia/Jakarta')->addMinutes(10),
        ],
        'Maghrib' => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta')->subMinutes(30),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta')->addMinutes(10),
        ],
        'Isya'    => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta')->subMinutes(30),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta')->addMinutes(10),
        ],
    ];

    foreach ($ranges as $sholat => $range) {
        if ($scanTime->between($range['start'], $range['end'])) {
            return $sholat;
        }
    }

    return null;
}

// ─── Helper: Log data ke file ───────────────────────────────────────
function logWebhook(string $message): void
{
    try {
        $logFile = __DIR__ . '/../storage/logs/webhook.log';
        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    } catch (\Exception $e) {
        // Jangan crash webhook hanya karena logging gagal
    }
}

// ─── Main: Proses Webhook ───────────────────────────────────────────
// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Ambil raw body
$rawBody = file_get_contents('php://input');
$decoded = json_decode($rawBody, true);

// Log raw data
logWebhook("RAW: $rawBody");

// Validasi JSON
if (!$decoded || !isset($decoded['type']) || !isset($decoded['cloud_id'])) {
    logWebhook("ERROR: Invalid JSON or missing type/cloud_id");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

// ─── Route berdasarkan type ─────────────────────────────────────────
$type = $decoded['type'];

if ($type === 'attlog') {
    handleAttlog($decoded);
} elseif ($type === 'get_userinfo') {
    handleGetUserinfo($decoded);
} else {
    logWebhook("SKIP: type=$type (tidak diproses)");
    echo json_encode(['status' => 'ok', 'message' => "Type '$type' ignored"]);
    exit;
}

// Terminate the kernel
$kernel->terminate($request, new Illuminate\Http\Response());
exit;

// =====================================================================
// HANDLER: attlog (data absensi realtime)
// =====================================================================
function handleAttlog(array $decoded): void
{
$data = $decoded['data'] ?? null;
if (!$data || !isset($data['pin']) || !isset($data['scan'])) {
    logWebhook("ERROR: Missing data.pin or data.scan");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required data fields']);
    exit;
}

$pin       = $data['pin'];
$scanStr   = $data['scan'];
$verify    = $data['verify'] ?? null;
$statusScan = $data['status_scan'] ?? null;
$photoUrl  = $data['photo_url'] ?? null;

// Parse waktu scan
try {
    $scanTime = Carbon::parse($scanStr, 'Asia/Jakarta');
} catch (\Exception $e) {
    logWebhook("ERROR: Invalid scan datetime: $scanStr");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid scan datetime']);
    exit;
}

$tanggal   = $scanTime->format('Y-m-d');
$waktuHadir = $scanTime->format('H:i:s');

// ─── Mapping PIN → Santri ───────────────────────────────────────────
// PIN di mesin FingerSpot = ID santri di database
$santri = Santri::find($pin);
if (!$santri) {
    try {
        // Buat email dari kata pertama nama PIN (fallback: santriPIN@thursina.id)
        $firstName = strtolower('santri' . $pin);
        $email = $firstName . '@thursina.id';
        $displayName = "Nama Belum Diatur (PIN: " . $pin . ")";

        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $displayName,
                'password' => \Illuminate\Support\Facades\Hash::make('santri'),
                'role'     => 'santri',
            ]
        );

        $santri = new Santri();
        $santri->id             = $pin;
        $santri->user_id        = $user->id;
        $santri->nama           = $displayName;
        $santri->kelas          = 'Belum Diatur';
        $santri->foto_referensi = '';
        $santri->finger_count   = 0;
        $santri->face_count     = 1; // Otomatis tipe wajah
        $santri->save();

        logWebhook("AUTO-CREATE: Santri baru dari attlog - pin=$pin, email=$email");

        // AUTO-FETCH NAME (FIRE & FORGET): Kirim request get_userinfo ke API Fingerspot
        // Gunakan timeout rendah (1 detik) agar tidak menghambat webhook response (menghindari duplikasi/retry webhook).
        try {
            logWebhook("AUTO-FETCH-NAME: Memicu get_userinfo untuk PIN $pin (Fire & Forget)...");
            Http::timeout(1)->connectTimeout(1)->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer DWJ7LY8ZJQ6CD5NN'
            ])->post('https://developer.fingerspot.io/api/get_userinfo', [
                'trans_id' => (string) rand(100000, 999999999),
                'cloud_id' => 'S118001290',
                'pin'      => (string) $pin,
            ]);
        } catch (\Exception $e) {
            // Abaikan timeout/koneksi terputus karena ini bersifat fire-and-forget
            logWebhook("AUTO-FETCH-NAME: Request sent for PIN $pin");
        }
    } catch (\Exception $e) {
        logWebhook("ERROR: Gagal auto-create santri pin=$pin - " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuat santri: ' . $e->getMessage()]);
        exit;
    }
}

// AUTO-CAPTURE FOTO: Jika santri belum punya foto atau masih foto default, ambil dari hasil scan presensi ini!
if (!empty($photoUrl) && (empty($santri->foto_referensi) || $santri->foto_referensi === 'default.jpg')) {
    $santri->foto_referensi = $photoUrl;
    $santri->save();
    logWebhook("AUTO-PHOTO: Menyimpan link foto profil otomatis untuk santri $pin");
}

// ─── Tentukan Waktu Sholat ──────────────────────────────────────────
$jadwal = getJadwalSholat($scanTime);
$waktuSholat = determineWaktuSholat($scanTime, $jadwal);

if (!$waktuSholat) {
    // Cek apakah fitur/halaman tes diaktifkan
    $tesEnabled = Cache::get('tes_page_enabled', true);
    if (!$tesEnabled) {
        logWebhook("INFO: Scan diluar waktu sholat diabaikan karena pencatatan tes dinonaktifkan - pin=$pin, waktu=$scanStr");
        echo json_encode([
            'status'  => 'ok',
            'message' => 'Scan outside prayer window ignored (testing disabled).'
        ]);
        exit;
    }

    $waktuSholat = 'Tes';
    logWebhook("INFO: Scan diluar waktu sholat, dicatat sebagai Tes - pin=$pin, waktu=$scanStr");
}

// ─── Simpan ke Database (FIRST SCAN WINS) ───────────────────────────
// Logika: Cek dulu apakah sudah ada record presensi untuk santri ini
// pada tanggal & waktu sholat yang sama. Jika sudah ada dan berstatus
// "Hadir", ABAIKAN scan baru (return OK agar mesin tidak kirim ulang).
// Hanya buat record baru jika belum ada data sama sekali.
// Untuk "Tes", setiap scan selalu membuat record baru (tidak ada constraint unik).
try {
    // --- Khusus Tes: langsung create baru setiap kali scan ---
    if ($waktuSholat === 'Tes') {
        $presensi = Presensi::create([
            'santri_id'    => $santri->id,
            'tanggal'      => $tanggal,
            'waktu_sholat' => $waktuSholat,
            'waktu_hadir'  => $waktuHadir,
            'status'       => 'Tes',
            'photo_url'    => $photoUrl,
        ]);

        logWebhook("SUCCESS: CREATED presensi Tes - santri_id={$santri->id}, nama={$santri->nama}, tanggal=$tanggal, waktu=$waktuHadir, verify=$verify, status_scan=$statusScan");

        echo json_encode([
            'status'  => 'ok',
            'message' => "Presensi Tes recorded for {$santri->nama}",
            'data'    => [
                'santri_id'    => $santri->id,
                'nama'         => $santri->nama,
                'tanggal'      => $tanggal,
                'waktu_sholat' => $waktuSholat,
                'waktu_hadir'  => $waktuHadir,
                'action'       => 'created',
            ]
        ]);
        return;
    }

    // --- Presensi Sholat: First Scan Wins ---
    // Cek apakah sudah ada record untuk santri + tanggal + waktu_sholat ini
    $existing = Presensi::where('santri_id', $santri->id)
        ->where('tanggal', $tanggal)
        ->where('waktu_sholat', $waktuSholat)
        ->first();

    if ($existing && $existing->status === 'Hadir') {
        // Record sudah ada & berstatus Hadir → ABAIKAN (jangan update/overwrite)
        logWebhook("SKIP: Presensi sudah tercatat (First Scan Wins) - santri_id={$santri->id}, nama={$santri->nama}, tanggal=$tanggal, sholat=$waktuSholat, waktu_awal={$existing->waktu_hadir}, scan_baru=$waktuHadir");

        echo json_encode([
            'status'  => 'ok',
            'message' => "Presensi $waktuSholat sudah tercatat sebelumnya untuk {$santri->nama} (scan diabaikan)",
            'data'    => [
                'santri_id'    => $santri->id,
                'nama'         => $santri->nama,
                'tanggal'      => $tanggal,
                'waktu_sholat' => $waktuSholat,
                'waktu_hadir'  => $existing->waktu_hadir,
                'action'       => 'skipped',
            ]
        ]);
        return;
    }

    if ($existing) {
        // Record ada tapi statusnya bukan "Hadir" (misal: "Alfa" dari auto-generate)
        // → Update ke "Hadir" karena santri ternyata hadir
        $existing->update([
            'waktu_hadir' => $waktuHadir,
            'status'      => 'Hadir',
            'photo_url'   => $photoUrl,
        ]);

        logWebhook("SUCCESS: UPDATED presensi (Alfa→Hadir) - santri_id={$santri->id}, nama={$santri->nama}, tanggal=$tanggal, sholat=$waktuSholat, waktu=$waktuHadir, verify=$verify, status_scan=$statusScan");

        echo json_encode([
            'status'  => 'ok',
            'message' => "Presensi $waktuSholat updated to Hadir for {$santri->nama}",
            'data'    => [
                'santri_id'    => $santri->id,
                'nama'         => $santri->nama,
                'tanggal'      => $tanggal,
                'waktu_sholat' => $waktuSholat,
                'waktu_hadir'  => $waktuHadir,
                'action'       => 'updated',
            ]
        ]);
        return;
    }

    // Belum ada record sama sekali → Buat baru
    $presensi = Presensi::create([
        'santri_id'    => $santri->id,
        'tanggal'      => $tanggal,
        'waktu_sholat' => $waktuSholat,
        'waktu_hadir'  => $waktuHadir,
        'status'       => 'Hadir',
        'photo_url'    => $photoUrl,
    ]);

    logWebhook("SUCCESS: CREATED presensi - santri_id={$santri->id}, nama={$santri->nama}, tanggal=$tanggal, sholat=$waktuSholat, waktu=$waktuHadir, verify=$verify, status_scan=$statusScan");

    echo json_encode([
        'status'  => 'ok',
        'message' => "Presensi $waktuSholat recorded for {$santri->nama}",
        'data'    => [
            'santri_id'    => $santri->id,
            'nama'         => $santri->nama,
            'tanggal'      => $tanggal,
            'waktu_sholat' => $waktuSholat,
            'waktu_hadir'  => $waktuHadir,
            'action'       => 'created',
        ]
    ]);

} catch (\Exception $e) {
    logWebhook("ERROR: Database error - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
}

// =====================================================================
// HANDLER: get_userinfo (data user dari mesin)
// =====================================================================
// LOGIKA: "Insert Ignore" — Jika PIN sudah ada di DB, SKIP.
//         Jika belum ada, buat santri baru.
// =====================================================================
function handleGetUserinfo(array $decoded): void
{
    $transId = $decoded['trans_id'] ?? 'unknown';
    $cloudId = $decoded['cloud_id'] ?? 'unknown';
    $data    = $decoded['data'] ?? null;
    
    // Skenario Error Handling: Jika data berisi string error (seperti "ERROR_NO_ID") atau bukan array/objek valid
    if (empty($data) || !is_array($data)) {
        logWebhook("INFO: get_userinfo skipped because data is not an array (e.g. ERROR_NO_ID). trans_id=$transId");
        
        // Increment counter error berurutan untuk deteksi ujung data santri
        Cache::increment('sync_consecutive_errors');
        
        echo json_encode([
            'status'  => 'ok',
            'message' => 'Error or empty data ignored successfully'
        ]);
        return;
    }
 
    if (!isset($data['pin'])) {
        // PIN tidak ada di mesin — abaikan, dan hitung sebagai error berurutan
        Cache::increment('sync_consecutive_errors');
        echo json_encode(['status' => 'ok', 'message' => 'PIN not found on device — skipped']);
        return;
    }

    // Jika data valid, reset counter error berurutan
    Cache::put('sync_consecutive_errors', 0, 300);

    $pin       = $data['pin'] ?? '-';
    $nameInput = trim($data['name'] ?? '');
    
    // Gunakan fallback jika nama kosong atau berisi tanda '-'
    if ($nameInput === '' || $nameInput === '-') {
        $displayName = "Nama Belum Diatur (PIN: " . $pin . ")";
    } else {
        $displayName = $nameInput;
    }
    
    $privilege = $data['privilege'] ?? '-';
    $finger    = $data['finger'] ?? '0';
    $face      = $data['face'] ?? '0';
    $password  = $data['password'] ?? '';
    $rfid      = $data['rfid'] ?? '';
    $vein      = $data['vein'] ?? '0';
    $template  = $data['template'] ?? '';

    // Map privilege code to label
    $privLabels = ['1' => 'User', '2' => 'Admin', '3' => 'Sub-admin'];
    $privLabel = $privLabels[$privilege] ?? "Unknown($privilege)";

    // Log detail lengkap
    logWebhook("USERINFO: trans_id=$transId, cloud_id=$cloudId, pin=$pin, name=$nameInput, privilege=$privLabel, finger=$finger, face=$face, rfid=" . ($rfid ?: '(kosong)') . ", vein=$vein, template_length=" . strlen($template));

    // ─── Pengecekan & Sinkronisasi Data (Upsert dengan updateOrCreate) ───
    try {
        // Tentukan email default untuk User (firstOrCreate)
        $cleanNameForEmail = ($nameInput === '' || $nameInput === '-') ? '' : $nameInput;
        $firstName = strtolower(explode(' ', trim($cleanNameForEmail))[0] ?? '');
        if ($firstName === '' || $firstName === '-') $firstName = 'santri' . $pin;
        $email = $firstName . '@thursina.id';

        // Buat atau cari User terkait
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $displayName,
                'password' => \Illuminate\Support\Facades\Hash::make('santri'),
                'role'     => 'santri',
            ]
        );

        // Ambil santri yang sudah ada jika ada (untuk pertahankan kelas & foto_referensi)
        $existingSantri = \App\Models\Santri::find($pin);
        $isUpdate = ($existingSantri !== null);

        // Upsert menggunakan updateOrCreate
        $santri = \App\Models\Santri::updateOrCreate(
            ['id' => $pin],
            [
                'user_id'        => $user->id,
                'nama'           => $displayName,
                'kelas'          => $isUpdate ? $existingSantri->kelas : 'Belum Diatur',
                'foto_referensi' => $isUpdate ? $existingSantri->foto_referensi : '',
                'finger_count'   => 0,
                'face_count'     => 1, // Otomatis diset ke status "Wajah" (1)
                'template'       => $template,
            ]
        );

        // Pastikan nama akun User yang terhubung juga sinkron/ter-update
        if ($santri->user && $santri->user->name !== $displayName) {
            $santri->user->update(['name' => $displayName]);
        }

        // AUTO-FOTO: Jika santri belum punya foto, coba ambil dari presensi terakhir
        if (empty($santri->foto_referensi) || $santri->foto_referensi === 'default.jpg') {
            $latestPhoto = \App\Models\Presensi::where('santri_id', $santri->id)
                ->whereNotNull('photo_url')
                ->where('photo_url', '!=', '')
                ->latest('updated_at')
                ->first();
            if ($latestPhoto) {
                $santri->update(['foto_referensi' => $latestPhoto->photo_url]);
                logWebhook("AUTO-PHOTO SYNC: Foto profil santri $pin diambil dari presensi terakhir");
            }
        }

        if ($isUpdate) {
            logWebhook("UPDATED: Nama santri PIN $pin diperbarui menjadi '$displayName'");
            echo json_encode([
                'status'  => 'ok',
                'message' => "Data santri PIN $pin berhasil diperbarui",
                'data'    => [
                    'trans_id'       => $transId,
                    'pin'            => $pin,
                    'action'         => 'updated',
                    'matched_santri' => [
                        'id'    => $santri->id,
                        'nama'  => $santri->nama,
                        'kelas' => $santri->kelas,
                    ],
                ],
            ]);
        } else {
            logWebhook("CREATED: Santri baru berhasil dibuat → santri_id={$santri->id}, nama={$santri->nama}, email=$email");
            echo json_encode([
                'status'  => 'ok',
                'message' => "Santri baru berhasil dibuat untuk PIN $pin ($displayName)",
                'data'    => [
                    'trans_id'       => $transId,
                    'pin'            => $pin,
                    'action'         => 'created',
                    'matched_santri' => [
                        'id'    => $santri->id,
                        'nama'  => $santri->nama,
                        'kelas' => $santri->kelas,
                    ],
                ],
            ]);
        }

    } catch (\Exception $e) {
        logWebhook("ERROR: Gagal memproses updateOrCreate untuk PIN $pin - " . $e->getMessage());
        echo json_encode([
            'status'  => 'error',
            'message' => "Gagal memproses data: " . $e->getMessage(),
            'data'    => ['pin' => $pin, 'action' => 'error'],
        ]);
    }
}