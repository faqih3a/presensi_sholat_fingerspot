<?php

namespace App\Actions\Santri;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Aksi: Ambil Info Pengguna dari Mesin Fingerspot (Asinkron)
 *
 * Mengirimkan perintah ke Fingerspot Cloud API untuk meminta mesin
 * mengirimkan data pengguna yang terdaftar. Data aktual akan dikirim
 * secara asinkron ke webhook (store.php) sebagai callback `get_userinfo`.
 *
 * PRINSIP: Data di mesin = Data REAL (sumber kebenaran).
 * Sistem hanya menerima dan menyesuaikan dengan data yang ada di mesin.
 *
 * API Endpoint: POST https://developer.fingerspot.io/api/get_userinfo
 * Request Body: { trans_id, cloud_id, pin }
 * Response: { success: boolean, trans_id: string } (konfirmasi saja)
 * Callback: Data aktual dikirim via webhook ke URL yang dikonfigurasi
 *
 * @see https://developer.fingerspot.io/api/get_userinfo
 * @see \App\Actions\Santri\FindOrCreateSantriAction (handler callback)
 * @see public/store.php handleGetUserinfo() (webhook receiver)
 */
class FetchUserInfoFromMesinAction
{
    private string $apiUrl = 'https://developer.fingerspot.io/api/get_userinfo';
    private string $apiToken;
    private string $cloudId;

    public function __construct()
    {
        $this->apiToken = config('services.fingerspot.token');
        $this->cloudId  = config('services.fingerspot.cloud_id');
    }

    /**
     * Kirim perintah get_userinfo untuk satu PIN spesifik.
     *
     * @param  string|int  $pin  PIN pengguna di mesin.
     * @return array{success: bool, trans_id: string, message: string}
     */
    public function fetchSingle($pin): array
    {
        $transId = $this->generateTransId();

        $response = Http::withHeaders($this->getHeaders())
            ->timeout(10)
            ->post($this->apiUrl, [
                'trans_id' => $transId,
                'cloud_id' => $this->cloudId,
                'pin'      => (string) $pin,
            ]);

        if (!$response->successful()) {
            Log::warning("FETCH-USERINFO: Failed for PIN={$pin}, HTTP {$response->status()}");
            return [
                'success'  => false,
                'trans_id' => $transId,
                'pin'      => (string) $pin,
                'message'  => 'Gagal menghubungi Fingerspot Cloud API (HTTP ' . $response->status() . ')',
            ];
        }

        $result = $response->json();
        $apiSuccess = $result['success'] ?? false;

        if (!$apiSuccess) {
            Log::warning("FETCH-USERINFO: API rejected for PIN={$pin}", $result);
            return [
                'success'  => false,
                'trans_id' => $transId,
                'pin'      => (string) $pin,
                'message'  => 'API menolak permintaan: ' . ($result['message'] ?? 'Unknown error'),
            ];
        }

        Log::info("FETCH-USERINFO: Command sent for PIN={$pin}, trans_id={$transId}");
        return [
            'success'  => true,
            'trans_id' => $transId,
            'pin'      => (string) $pin,
            'message'  => "Perintah get_userinfo untuk PIN {$pin} berhasil dikirim. Data akan diterima via webhook.",
        ];
    }

    /**
     * Kirim perintah get_userinfo untuk banyak PIN sekaligus.
     * Menggunakan delay antar request agar tidak overload mesin.
     *
     * @param  array  $pins  Daftar PIN yang ingin diambil datanya.
     * @param  int    $delayMs  Delay antar request dalam milidetik (default: 200ms).
     * @return array{success: bool, message: string, sent: int, failed: int, results: array}
     */
    public function fetchMultiple(array $pins, int $delayMs = 200): array
    {
        $results = [];
        $sent = 0;
        $failed = 0;

        foreach ($pins as $pin) {
            $result = $this->fetchSingle($pin);
            $results[] = $result;

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }

            // Delay antar request agar tidak overload
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $total = $sent + $failed;

        return [
            'success' => $sent > 0,
            'message' => "Berhasil mengirim {$sent}/{$total} perintah get_userinfo. Data akan dikirim mesin via webhook.",
            'sent'    => $sent,
            'failed'  => $failed,
            'total'   => count($pins),
            'results' => $results,
        ];
    }

    /**
     * Kirim perintah get_userinfo untuk range PIN (misal PIN 1-150).
     *
     * @param  int  $startPin  PIN awal.
     * @param  int  $endPin    PIN akhir.
     * @param  int  $delayMs   Delay antar request dalam milidetik.
     * @return array
     */
    public function fetchRange(int $startPin, int $endPin, int $delayMs = 200): array
    {
        $pins = range($startPin, $endPin);
        return $this->fetchMultiple(array_map('strval', $pins), $delayMs);
    }

    /**
     * Generate trans_id unik yang aman untuk Fingerspot API.
     * Server Fingerspot membatasi max integer 32-bit (2147483647).
     *
     * @return string
     */
    private function generateTransId(): string
    {
        return (string) rand(100000, 999999999);
    }

    /**
     * Headers standar untuk Fingerspot Cloud API.
     *
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
        ];
    }
}
