<?php

namespace App\Actions\Santri;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

/**
 * Aksi: Sinkronisasi Data Santri dari Mesin Fingerspot
 *
 * Alur: get_userlist → ekstrak PIN → kirim get_userinfo paralel.
 *
 * @see \App\Http\Controllers\SantriController::syncMesin()
 */
class SyncSantriFromMesinAction
{
    private string $userListUrl = 'https://developer.fingerspot.io/api/get_userlist';
    private string $userInfoUrl = 'https://developer.fingerspot.io/api/get_userinfo';
    private string $apiToken;
    private string $cloudId;

    public function __construct()
    {
        $this->apiToken = config('services.fingerspot.token');
        $this->cloudId  = config('services.fingerspot.cloud_id');
    }

    /**
     * Menjalankan aksi sinkronisasi data santri dari mesin Fingerspot.
     *
     * @return array  ['success', 'message', 'count'?, 'pins'?]
     */
    public function execute(): array
    {
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
        ];

        // Langkah 1: Tarik daftar PIN dari get_userlist
        $response = Http::withHeaders($headers)->post($this->userListUrl, [
            'trans_id' => (string) rand(100000, 999999999),
            'cloud_id' => $this->cloudId,
        ]);

        if (!$response->successful() || !$response->json('success')) {
            return [
                'success' => false,
                'message' => 'Langkah 1 Gagal: ' . ($response->json('message') ?? 'Gagal menghubungi Fingerspot Cloud API.'),
                'status'  => 400,
            ];
        }

        $dataList = $response->json('data') ?? [];

        if (!is_array($dataList)) {
            return ['success' => false, 'message' => 'Langkah 1 Gagal: Format data respons tidak valid.', 'status' => 400];
        }

        // Langkah 2: Ekstrak PIN valid
        $pins = array_filter(array_map(
            fn($item) => isset($item['pin']) ? (string)$item['pin']
                : (isset($item['user_id']) ? (string)$item['user_id']
                : (isset($item['emp_pin']) ? (string)$item['emp_pin'] : null)),
            $dataList
        ));

        if (empty($pins)) {
            return ['success' => true, 'message' => 'Tidak ada PIN pengguna yang terdaftar di mesin.', 'count' => 0];
        }

        // Langkah 3: Kirim get_userinfo paralel untuk setiap PIN
        Http::pool(function (Pool $pool) use ($pins, $headers) {
            foreach ($pins as $pin) {
                $pool->timeout(2)->connectTimeout(2)->withHeaders($headers)->post($this->userInfoUrl, [
                    'trans_id' => (string) rand(100000, 999999999),
                    'cloud_id' => $this->cloudId,
                    'pin'      => $pin,
                ]);
            }
        });

        return [
            'success' => true,
            'message' => 'Berhasil mengirim perintah sinkronisasi untuk ' . count($pins) . ' santri terdaftar di mesin.',
            'count'   => count($pins),
            'pins'    => array_values($pins),
        ];
    }
}
