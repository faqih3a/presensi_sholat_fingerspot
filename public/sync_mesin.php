<?php

/**
 * ====================================================================
 * Sinkronisasi Mesin - Async Background Processing
 * ====================================================================
 * 
 * Endpoint ini menggantikan alur lama yang synchronous (get_userinfo.php)
 * dengan pendekatan yang lebih efisien:
 * 
 * 1. FASE KIRIM: Mengirim perintah get_userinfo ke API Fingerspot
 *    menggunakan cURL Multi (parallel, 10 request sekaligus)
 * 2. PROGRESS TRACKING: Menyimpan progress ke file JSON agar frontend
 *    bisa polling status secara real-time
 * 
 * Endpoints:
 *   POST /sync_mesin.php?action=start     → Mulai sinkronisasi
 *   GET  /sync_mesin.php?action=status    → Cek progress
 *   POST /sync_mesin.php?action=cancel    → Batalkan proses
 * ====================================================================
 */

// ─── Bootstrap Laravel ──────────────────────────────────────────────
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Carbon\Carbon;

// ─── Config ─────────────────────────────────────────────────────────
$apiUrl        = 'https://developer.fingerspot.io/api/get_userinfo';
$apiToken      = 'DWJ7LY8ZJQ6CD5NN';
$cloudId       = 'S118001290';
$maxPin        = 150;
$batchSize     = 10;  // Jumlah request paralel per batch
$progressFile  = __DIR__ . '/../storage/logs/sync_progress.json';

header('Content-Type: application/json');

// ─── Helper: Update Progress ────────────────────────────────────────
function updateProgress(string $file, array $data): void
{
    $data['updated_at'] = Carbon::now('Asia/Jakarta')->toISOString();
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

// ─── Helper: Read Progress ──────────────────────────────────────────
function readProgress(string $file): ?array
{
    if (!file_exists($file)) return null;
    $content = @file_get_contents($file);
    if (!$content) return null;
    return json_decode($content, true);
}

// ─── Route by Action ────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

if ($action === 'status') {
    // ─── STATUS: Return current progress ────────────────────────────
    $progress = readProgress($progressFile);
    if (!$progress) {
        echo json_encode([
            'status'   => 'idle',
            'message'  => 'Belum ada proses sinkronisasi yang berjalan.',
        ]);
    } else {
        echo json_encode($progress);
    }
    $kernel->terminate($request, new Illuminate\Http\Response());
    exit;
}

if ($action === 'cancel') {
    // ─── CANCEL: Mark as cancelled ──────────────────────────────────
    $progress = readProgress($progressFile);
    if ($progress && $progress['status'] === 'running') {
        $progress['status'] = 'cancelled';
        $progress['message'] = 'Sinkronisasi dibatalkan oleh user.';
        updateProgress($progressFile, $progress);
    }
    echo json_encode(['status' => 'ok', 'message' => 'Cancelled']);
    $kernel->terminate($request, new Illuminate\Http\Response());
    exit;
}

if ($action !== 'start') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Action tidak valid. Gunakan: start, status, cancel']);
    $kernel->terminate($request, new Illuminate\Http\Response());
    exit;
}

// ─── START: Cek apakah sudah ada proses yang berjalan ───────────────
$existing = readProgress($progressFile);
if ($existing && $existing['status'] === 'running') {
    // Cek apakah proses lama sudah stuck (lebih dari 5 menit)
    $lastUpdate = Carbon::parse($existing['updated_at']);
    if ($lastUpdate->diffInMinutes(Carbon::now('Asia/Jakarta')) < 5) {
        echo json_encode([
            'status'  => 'already_running',
            'message' => 'Proses sinkronisasi sedang berjalan. Silakan tunggu.',
            'progress' => $existing,
        ]);
        $kernel->terminate($request, new Illuminate\Http\Response());
        exit;
    }
    // Jika stuck lebih dari 5 menit, override
}

// ─── START: Mulai Proses Sinkronisasi ───────────────────────────────
set_time_limit(300); // 5 menit max
ignore_user_abort(true); // Lanjutkan meskipun browser disconnect

// Kirim response "accepted" segera ke browser
$initialResponse = json_encode([
    'status'  => 'started',
    'message' => "Sinkronisasi dimulai untuk PIN 1-$maxPin. Pantau progress via ?action=status",
]);

// Flush response ke browser agar tidak menunggu
if (function_exists('fastcgi_finish_request')) {
    // Untuk PHP-FPM (Nginx)
    echo $initialResponse;
    session_write_close();
    fastcgi_finish_request();
} else {
    // Untuk Apache/mod_php (XAMPP)
    ob_end_clean();
    header('Connection: close');
    header('Content-Length: ' . strlen($initialResponse));
    echo $initialResponse;
    flush();
    if (function_exists('ob_end_flush')) {
        @ob_end_flush();
    }
    // Apache mungkin masih menunggu, tapi script tetap berjalan
}

// ─── Background: Init Progress ─────────────────────────────────────
$progress = [
    'status'         => 'running',
    'phase'          => 'sending_commands',
    'message'        => 'Mengirim perintah ke mesin...',
    'total_pins'     => $maxPin,
    'sent'           => 0,
    'success'        => 0,
    'failed'         => 0,
    'processed'      => 0,
    'started_at'     => Carbon::now('Asia/Jakarta')->toISOString(),
    'new_santri'     => 0,
    'updated_santri' => 0,
];
updateProgress($progressFile, $progress);

// Reset counter error berurutan di awal sinkronisasi
\Illuminate\Support\Facades\Cache::put('sync_consecutive_errors', 0, 300);

// ─── Background: Kirim perintah get_userinfo dalam batch ────────────
$allResults = [];

for ($batchStart = 1; $batchStart <= $maxPin; $batchStart += $batchSize) {
    // Cek apakah dideteksi 5+ ERROR_NO_ID berturut-turut dari webhook
    $consecutiveErrors = \Illuminate\Support\Facades\Cache::get('sync_consecutive_errors', 0);
    if ($consecutiveErrors >= 5) {
        break; // Hentikan sinkronisasi karena sudah mencapai batas akhir user terdaftar
    }

    // Cek apakah dibatalkan
    $currentProgress = readProgress($progressFile);
    if ($currentProgress && $currentProgress['status'] === 'cancelled') {
        break;
    }

    $batchEnd = min($batchStart + $batchSize - 1, $maxPin);
    $multiHandle = curl_multi_init();
    $handles = [];

    // Buat semua cURL handles untuk batch ini
    for ($pin = $batchStart; $pin <= $batchEnd; $pin++) {
        $transId = (string) rand(100000, 999999999);
        $payload = json_encode([
            'trans_id' => $transId,
            'cloud_id' => $cloudId,
            'pin'      => (string)$pin,
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        curl_multi_add_handle($multiHandle, $ch);
        $handles[$pin] = ['handle' => $ch, 'trans_id' => $transId];
    }

    // Execute semua request dalam batch secara paralel
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle, 0.5);
    } while ($running > 0);

    // Proses hasil batch
    foreach ($handles as $pin => $info) {
        $ch = $info['handle'];
        $result = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        $decoded = json_decode($result, true);
        $success = !$error && ($decoded['success'] ?? false);

        $allResults[] = [
            'pin'      => $pin,
            'trans_id' => $info['trans_id'],
            'success'  => $success,
        ];

        if ($success) {
            $progress['success']++;
        } else {
            $progress['failed']++;
        }
        $progress['sent']++;

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);

    // Update progress setelah setiap batch
    $progress['message'] = "Mengirim perintah ke mesin... ({$progress['sent']}/{$maxPin})";
    $progress['phase'] = 'sending_commands';
    updateProgress($progressFile, $progress);

    // Delay kecil antar batch agar tidak overload API
    usleep(300000); // 300ms
}

// ─── Background: Fase 2 - Tunggu webhook & verifikasi data ─────────
// Cek apakah dibatalkan
$currentProgress = readProgress($progressFile);
if ($currentProgress && $currentProgress['status'] === 'cancelled') {
    updateProgress($progressFile, $currentProgress);
    $kernel->terminate($request, new Illuminate\Http\Response());
    exit;
}

$progress['phase'] = 'waiting_webhook';
$progress['message'] = 'Perintah terkirim! Menunggu mesin mengirim data via webhook...';
updateProgress($progressFile, $progress);

// Tunggu beberapa detik agar webhook sempat diproses oleh mesin
sleep(8);

// ─── Background: Fase 3 - Hitung hasil akhir ───────────────────────
$progress['phase'] = 'finalizing';
$progress['message'] = 'Memverifikasi data...';
updateProgress($progressFile, $progress);

// Hitung jumlah santri di database sekarang
$totalSantri = \App\Models\Santri::count();

$progress['status']  = 'completed';
$progress['phase']   = 'done';
$progress['message'] = "Sinkronisasi selesai! {$progress['success']} perintah berhasil dikirim dari {$maxPin} PIN. Total santri di database: {$totalSantri}.";
$progress['total_santri_db'] = $totalSantri;
$progress['completed_at'] = Carbon::now('Asia/Jakarta')->toISOString();
updateProgress($progressFile, $progress);

$kernel->terminate($request, new Illuminate\Http\Response());
exit;
