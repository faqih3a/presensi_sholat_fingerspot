<?php

/**
 * ====================================================================
 * API Request - Get Userinfo dari Mesin FingerSpot
 * ====================================================================
 * 
 * Mengirimkan perintah ke mesin untuk mengambil data userinfo.
 * Hasilnya akan dikirim oleh mesin via Webhook ke store.php
 * 
 * Usage:
 *   GET  /get_userinfo.php?pin=1           → ambil userinfo pin 1
 *   GET  /get_userinfo.php?pin=all         → ambil semua userinfo (pin 1-100)
 *   GET  /get_userinfo.php?pin=1&pin_end=5 → ambil userinfo pin 1 sampai 5
 * ====================================================================
 */

// ─── Config ─────────────────────────────────────────────────────────
$apiUrl        = 'https://developer.fingerspot.io/api/get_userinfo';
$apiToken      = 'DWJ7LY8ZJQ6CD5NN';
$cloudId       = 'S118001290';

// ─── Parse Parameters ───────────────────────────────────────────────
$pin      = $_GET['pin'] ?? null;
$pinEnd   = $_GET['pin_end'] ?? null;

header('Content-Type: application/json');

if (!$pin) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter "pin" diperlukan. Contoh: ?pin=1 atau ?pin=all atau ?pin=1&pin_end=5'
    ]);
    exit;
}

// ─── Helper: Kirim request ke API FingerSpot ────────────────────────
function requestUserinfo(string $apiUrl, string $apiToken, string $cloudId, string $pin): array
{
    // Server FingerSpot membatasi max integer 32-bit (2147483647)
    // Gunakan angka random di bawah limit tersebut
    $transId = (string) rand(100000, 999999999);
    
    $payload = json_encode([
        'trans_id' => $transId,
        'cloud_id' => $cloudId,
        'pin'      => $pin,
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'pin'      => $pin,
            'trans_id' => $transId,
            'success'  => false,
            'error'    => "cURL error: $error",
        ];
    }

    $decoded = json_decode($result, true);
    return [
        'pin'      => $pin,
        'trans_id' => $transId,
        'success'  => $decoded['success'] ?? false,
        'response' => $decoded,
    ];
}

// ─── Execute ────────────────────────────────────────────────────────
$results = [];

if ($pin === 'all') {
    // Ambil semua user (pin 1 sampai 10, tambahkan pin_end jika perlu lebih)
    $maxPin = (int)($pinEnd ?? 10);
    for ($i = 1; $i <= $maxPin; $i++) {
        $results[] = requestUserinfo($apiUrl, $apiToken, $cloudId, (string)$i);
        usleep(200000); // 200ms delay antar request agar tidak overload
    }
} elseif ($pinEnd) {
    // Range: pin sampai pin_end
    $start = (int)$pin;
    $end   = (int)$pinEnd;
    for ($i = $start; $i <= $end; $i++) {
        $results[] = requestUserinfo($apiUrl, $apiToken, $cloudId, (string)$i);
        usleep(200000);
    }
} else {
    // Single pin
    $results[] = requestUserinfo($apiUrl, $apiToken, $cloudId, $pin);
}

// ─── Output ─────────────────────────────────────────────────────────
$successCount = count(array_filter($results, fn($r) => $r['success']));

echo json_encode([
    'status'        => 'ok',
    'message'       => "Sent $successCount/" . count($results) . " get_userinfo commands. Data akan dikirim mesin via webhook ke store.php",
    'total_sent'    => count($results),
    'total_success' => $successCount,
    'results'       => $results,
], JSON_PRETTY_PRINT);
