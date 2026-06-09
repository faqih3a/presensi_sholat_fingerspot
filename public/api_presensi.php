<?php

/**
 * ====================================================================
 * API Presensi - Delete & Update Status
 * ====================================================================
 * 
 * Direct PHP endpoint to bypass Laravel route caching issues.
 * Handles delete and update-status for presensi records via AJAX.
 * 
 * Endpoints:
 *   POST /api_presensi.php?action=delete
 *   POST /api_presensi.php?action=update-status
 * ====================================================================
 */

// ─── Bootstrap Laravel ──────────────────────────────────────────────
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Presensi;
use Carbon\Carbon;

// ─── Auth Check ─────────────────────────────────────────────────────
$user = auth()->user();
if (!$user || !in_array($user->role, ['admin', 'asatidz'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ─── Get Action ─────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

// Read JSON body
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

header('Content-Type: application/json');

// ─── ACTION: delete ─────────────────────────────────────────────────
if ($action === 'delete') {
    $santriId = $data['santri_id'] ?? null;
    $tanggal = $data['tanggal'] ?? null;
    $waktuSholat = $data['waktu_sholat'] ?? null;

    if (!$santriId || !$tanggal || !$waktuSholat) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
        exit;
    }

    $deleted = Presensi::where('santri_id', $santriId)
        ->where('tanggal', $tanggal)
        ->where('waktu_sholat', $waktuSholat)
        ->delete();

    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'Data presensi berhasil dihapus.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
    }
    exit;
}

// ─── ACTION: update-status ──────────────────────────────────────────
if ($action === 'update-status') {
    $santriId = $data['santri_id'] ?? null;
    $tanggal = $data['tanggal'] ?? null;
    $waktuSholat = $data['waktu_sholat'] ?? null;
    $status = $data['status'] ?? null;

    if (!$santriId || !$tanggal || !$waktuSholat || !$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
        exit;
    }

    if (!in_array($status, ['Hadir', 'Izin', 'Alfa'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
        exit;
    }

    $presensi = Presensi::updateOrCreate([
        'santri_id' => $santriId,
        'tanggal' => $tanggal,
        'waktu_sholat' => $waktuSholat,
    ], [
        'status' => $status,
        'waktu_hadir' => $status === 'Hadir' ? Carbon::now('Asia/Jakarta')->format('H:i') : null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Status kehadiran berhasil diperbarui.',
        'data' => [
            'santri_id' => $presensi->santri_id,
            'tanggal' => $presensi->tanggal,
            'waktu_sholat' => $presensi->waktu_sholat,
            'status' => $presensi->status,
            'waktu_hadir' => $presensi->waktu_hadir,
        ],
    ]);
    exit;
}

// ─── Unknown Action ─────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);

$kernel->terminate($request, new Illuminate\Http\Response());
