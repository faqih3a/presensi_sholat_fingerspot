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
// Logika bisnis telah dipindahkan ke: App\Actions\Presensi\StorePresensiAction
// File ini hanya menangani: validasi payload, logging, dan response format.
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

    logWebhook("ATTLOG: pin=$pin, scan=$scanStr, verify=$verify, status_scan=$statusScan");

    // Delegasi seluruh logika bisnis ke StorePresensiAction
    try {
        $action = app(\App\Actions\Presensi\StorePresensiAction::class);
        $result = $action->executeFromWebhook([
            'pin'         => $pin,
            'scan'        => $scanStr,
            'verify'      => $verify,
            'status_scan' => $statusScan,
            'photo_url'   => $data['photo_url'] ?? null,
        ]);

        $httpCode = $result['http_code'] ?? 200;
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }

        $action_taken = $result['data']['action'] ?? 'processed';
        logWebhook("RESULT: pin=$pin, action=$action_taken, message={$result['message']}");

        echo json_encode([
            'status'  => $result['status'],
            'message' => $result['message'],
            'data'    => $result['data'],
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

    // ─── Delegasi ke FindOrCreateSantriAction (Concurrency-Safe) ───
    // Menggunakan Action Class yang sama dengan jalur attlog (Metode 2),
    // sehingga TIDAK MUNGKIN terjadi duplikasi data meskipun kedua jalur
    // berjalan bersamaan.
    try {
        $action = app(\App\Actions\Santri\FindOrCreateSantriAction::class);
        $result = $action->execute(
            pin:       $pin,
            name:      $nameInput ?: null,
            photoUrl:  null, // get_userinfo tidak membawa foto scan
            biometric: [
                'finger'   => $finger,
                'face'     => $face,
                'template' => $template,
            ]
        );

        $santri = $result['santri'];
        $actionTaken = $result['action']; // 'found', 'created', atau 'updated'

        // AUTO-FOTO: Jika santri belum punya foto, coba ambil dari presensi terakhir
        // (foto ini mungkin sudah ada jika Metode 2 / attlog lebih dulu berjalan)
        if ($santri && (empty($santri->foto_referensi) || $santri->foto_referensi === 'default.jpg')) {
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

        logWebhook("RESULT: PIN $pin action=$actionTaken, message={$result['message']}");

        echo json_encode([
            'status'  => 'ok',
            'message' => $result['message'],
            'data'    => [
                'trans_id'       => $transId,
                'pin'            => $pin,
                'action'         => $actionTaken,
                'matched_santri' => $santri ? [
                    'id'    => $santri->id,
                    'nama'  => $santri->nama,
                    'kelas' => $santri->kelas,
                ] : null,
            ],
        ]);

    } catch (\Exception $e) {
        logWebhook("ERROR: Gagal memproses data untuk PIN $pin - " . $e->getMessage());
        echo json_encode([
            'status'  => 'error',
            'message' => "Gagal memproses data: " . $e->getMessage(),
            'data'    => ['pin' => $pin, 'action' => 'error'],
        ]);
    }
}