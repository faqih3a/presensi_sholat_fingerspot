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

        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => 'Santri ' . $pin,
                'password' => \Illuminate\Support\Facades\Hash::make('santri'),
                'role'     => 'santri',
            ]
        );

        $santri = new Santri();
        $santri->id             = $pin;
        $santri->user_id        = $user->id;
        $santri->nama           = 'Santri ' . $pin;
        $santri->kelas          = 'Belum Diatur';
        $santri->foto_referensi = '';
        $santri->finger_count   = 0;
        $santri->face_count     = 1; // Otomatis tipe wajah
        $santri->save();

        logWebhook("AUTO-CREATE: Santri baru dari attlog - pin=$pin, email=$email");
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
            'status'      => $waktuSholat === 'Tes' ? 'Tes' : 'Hadir',
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
        // PIN tidak ada di mesin — abaikan, tidak perlu log
        echo json_encode(['status' => 'ok', 'message' => 'PIN not found on device — skipped']);
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

    try {
        if (!$santri) {
            // Buat email dari kata pertama nama (lowercase) + @thursina.id
            $firstName = strtolower(explode(' ', trim($name))[0] ?? 'santri');
            if ($firstName === '-' || $firstName === '') $firstName = 'santri' . $pin;
            $email = $firstName . '@thursina.id';

            // Buat user akun default (firstOrCreate untuk hindari duplikat email)
            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'password' => \Illuminate\Support\Facades\Hash::make('santri'),
                    'role'     => 'santri',
                ]
            );

            // Buat santri baru (paksa ID sama dengan PIN dari mesin)
            $santri = new \App\Models\Santri();
            $santri->id             = $pin;
            $santri->user_id        = $user->id;
            $santri->nama           = $name;
            $santri->kelas          = 'Belum Diatur'; // Dibiarkan kosong/Belum Diatur untuk update manual
            $santri->foto_referensi = '';
            $santri->finger_count   = 0; // Hardcode tipe jari ke 0
            $santri->face_count     = 1; // Hardcode tipe wajah ke 1
            $santri->template       = $template;
            $santri->save();

            $dbStatus = "AUTO-CREATED → santri_id={$santri->id}, db_name={$santri->nama}";
        } else {
            // Santri sudah terdaftar di web — update nama dan biometrik jika berbeda
            $updated = false;

            // Generate new email based on first word of the name
            $firstName = strtolower(explode(' ', trim($name))[0] ?? 'santri');
            if ($firstName === '-' || $firstName === '') $firstName = 'santri' . $pin;
            $newEmail = $firstName . '@thursina.id';

            if ($name !== '-' && ($santri->nama !== $name || ($santri->user && $santri->user->email !== $newEmail))) {
                $santri->nama = $name;
                
                // Update user name and email as well
                if ($santri->user) {
                    $santri->user->name = $name;
                    $santri->user->email = $newEmail;
                    $santri->user->save();
                }
                $updated = true;
            }

            // Selalu pastikan tipe biometrik wajah di-hardcode
            if ($santri->face_count !== 1 || $santri->finger_count !== 0 || $santri->template !== $template) {
                $santri->finger_count = 0;
                $santri->face_count = 1;
                $santri->template = $template;
                $updated = true;
            }

            if ($updated) {
                $santri->save();
                $dbStatus = "UPDATED → santri_id={$santri->id}, db_name={$santri->nama}";
            } else {
                $dbStatus = "SUDAH TERDAFTAR → santri_id={$santri->id}, db_name={$santri->nama} (dilewati)";
            }
        }
    } catch (\Exception $e) {
        logWebhook("USERINFO DB ERROR: pin=$pin - " . $e->getMessage());
        $dbStatus = "DB ERROR: " . $e->getMessage();
    }

    // AUTO-FOTO: Jika santri belum punya foto, ambil dari data presensi terakhir
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