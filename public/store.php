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
            $response = Http::timeout(5)->get('https://api.aladhan.com/v1/timingsByAddress', [
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

    // Definisi rentang waktu setiap sholat:
    // Mulai = waktu adzan, Selesai = waktu adzan sholat berikutnya
    // Untuk Isya, batas akhir = tengah malam (23:59)
    // Untuk Subuh, batas awal = 03:00 (sebelum adzan subuh juga dihitung)
    $ranges = [
        'Subuh'   => [
            'start' => Carbon::parse($date . ' 03:00', 'Asia/Jakarta'),
            'end'   => Carbon::parse($date . ' ' . ($jadwal['Sunrise'] ?? '06:00'), 'Asia/Jakarta'),
        ],
        'Dzuhur'  => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Dhuhr'], 'Asia/Jakarta'),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Asr'], 'Asia/Jakarta'),
        ],
        'Ashar'   => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Asr'], 'Asia/Jakarta'),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta'),
        ],
        'Maghrib' => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Maghrib'], 'Asia/Jakarta'),
            'end'   => Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta'),
        ],
        'Isya'    => [
            'start' => Carbon::parse($date . ' ' . $jadwal['Isha'], 'Asia/Jakarta'),
            'end'   => Carbon::parse($date . ' 23:59', 'Asia/Jakarta'),
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
    logWebhook("WARNING: Santri not found for pin=$pin");
    echo json_encode(['status' => 'ok', 'message' => "Santri with pin $pin not found, data logged"]);
    exit;
}

// ─── Tentukan Waktu Sholat ──────────────────────────────────────────
$jadwal = getJadwalSholat($scanTime);
$waktuSholat = determineWaktuSholat($scanTime, $jadwal);

if (!$waktuSholat) {
    logWebhook("WARNING: Scan time $scanStr tidak masuk rentang waktu sholat manapun (pin=$pin)");
    echo json_encode(['status' => 'ok', 'message' => 'Scan time outside prayer time range, data logged']);
    exit;
}

// ─── Simpan ke Database ─────────────────────────────────────────────
// updateOrCreate: jika sudah ada record untuk santri+tanggal+waktu_sholat yang sama,
// maka update (misal santri scan ulang). Jika belum ada, buat baru.
try {
    $presensi = Presensi::updateOrCreate(
        [
            'santri_id'    => $santri->id,
            'tanggal'      => $tanggal,
            'waktu_sholat' => $waktuSholat,
        ],
        [
            'waktu_hadir' => $waktuHadir,
            'status'      => 'Hadir',
            'photo_url'   => $photoUrl,
        ]
    );

    $action = $presensi->wasRecentlyCreated ? 'CREATED' : 'UPDATED';
    logWebhook("SUCCESS: $action presensi - santri_id={$santri->id}, nama={$santri->nama}, tanggal=$tanggal, sholat=$waktuSholat, waktu=$waktuHadir, verify=$verify, status_scan=$statusScan");

    echo json_encode([
        'status'  => 'ok',
        'message' => "Presensi $waktuSholat recorded for {$santri->nama}",
        'data'    => [
            'santri_id'    => $santri->id,
            'nama'         => $santri->nama,
            'tanggal'      => $tanggal,
            'waktu_sholat' => $waktuSholat,
            'waktu_hadir'  => $waktuHadir,
            'action'       => strtolower($action),
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
function handleGetUserinfo(array $decoded): void
{
    $transId = $decoded['trans_id'] ?? 'unknown';
    $cloudId = $decoded['cloud_id'] ?? 'unknown';
    $data    = $decoded['data'] ?? null;

    if (!$data || !isset($data['pin'])) {
        logWebhook("USERINFO ERROR: Missing data or pin (trans_id=$transId)");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing userinfo data']);
        return;
    }

    $pin       = $data['pin'] ?? '-';
    $name      = $data['name'] ?? '-';
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
    logWebhook("USERINFO: trans_id=$transId, cloud_id=$cloudId, pin=$pin, name=$name, privilege=$privLabel, finger=$finger, face=$face, rfid=" . ($rfid ?: '(kosong)') . ", vein=$vein, template_length=" . strlen($template));

    // Cek apakah pin ini ada di database santri
    $santri = \App\Models\Santri::find($pin);
    $dbStatus = "NOT FOUND in database";

    if ($santri) {
        // Simpan data biometrik ke database
        $santri->update([
            'finger_count' => (int) $finger,
            'face_count'   => (int) $face,
            'template'     => $template,
        ]);
        $dbStatus = "MATCHED → santri_id={$santri->id}, db_name={$santri->nama} (Biometrik Disimpan)";
    }
    
    logWebhook("USERINFO MATCH: pin=$pin → $dbStatus");

    echo json_encode([
        'status'  => 'ok',
        'message' => "Userinfo received for pin $pin ($name)",
        'data'    => [
            'trans_id'     => $transId,
            'cloud_id'     => $cloudId,
            'pin'          => $pin,
            'name'         => $name,
            'privilege'    => $privLabel,
            'finger_count' => $finger,
            'face_count'   => $face,
            'has_rfid'     => !empty($rfid),
            'has_password' => !empty($password),
            'vein_count'   => $vein,
            'matched_santri' => $santri ? [
                'id'   => $santri->id,
                'nama' => $santri->nama,
                'kelas' => $santri->kelas,
            ] : null,
        ],
    ]);
}