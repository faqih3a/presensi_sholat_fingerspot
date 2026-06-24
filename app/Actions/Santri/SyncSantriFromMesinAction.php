<?php

namespace App\Actions\Santri;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

/**
 * Aksi: Sinkronisasi Data Santri dari Mesin Fingerspot
 *
 * Class ini bertanggung jawab untuk menarik data pengguna yang terdaftar
 * di mesin absensi Fingerspot melalui Cloud API, lalu mengirimkan perintah
 * sinkronisasi info untuk setiap PIN yang ditemukan.
 *
 * Alur Proses:
 * 1. Memanggil API `get_userlist` untuk mendapatkan daftar PIN pengguna.
 * 2. Mengekstrak PIN valid dari response.
 * 3. Mengirim request `get_userinfo` secara paralel (HTTP Pool) untuk setiap PIN.
 *
 * Konfigurasi API:
 * - API URL: https://developer.fingerspot.io/api/
 * - Autentikasi: Bearer Token
 * - Cloud ID: Identifier unik mesin
 *
 * @see \App\Http\Controllers\SantriController::syncMesin()
 */
class SyncSantriFromMesinAction
{
    /** @var string URL endpoint untuk mengambil daftar user dari mesin. */
    private string $userListUrl = 'https://developer.fingerspot.io/api/get_userlist';

    /** @var string URL endpoint untuk mengambil info detail user dari mesin. */
    private string $userInfoUrl = 'https://developer.fingerspot.io/api/get_userinfo';

    /** @var string API token untuk autentikasi ke Fingerspot Cloud. */
    private string $apiToken = 'DWJ7LY8ZJQ6CD5NN';

    /** @var string Cloud ID mesin Fingerspot yang terdaftar. */
    private string $cloudId = 'S118001290';

    /**
     * Menjalankan aksi sinkronisasi data santri dari mesin Fingerspot.
     *
     * @return array  Array asosiatif berisi:
     *   - 'success' (bool): Status keberhasilan proses.
     *   - 'message' (string): Pesan deskriptif hasil proses.
     *   - 'count' (int): Jumlah PIN yang berhasil disinkronisasi (opsional).
     *   - 'pins' (array): Daftar PIN yang ditemukan (opsional).
     *
     * @throws \Exception  Jika terjadi kesalahan koneksi atau proses.
     */
    public function execute(): array
    {
        // Langkah 1: Tarik daftar PIN valid menggunakan get_userlist
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
        ])->post($this->userListUrl, [
            'trans_id' => (string) rand(100000, 999999999),
            'cloud_id' => $this->cloudId,
        ]);

        if (!$response->successful() || !$response->json('success')) {
            $errMessage = $response->json('message') ?? 'Gagal menghubungi Fingerspot Cloud API.';
            return [
                'success' => false,
                'message' => 'Langkah 1 Gagal: ' . $errMessage,
                'status'  => 400,
            ];
        }

        // Langkah 2: Ekstrak PIN valid dari daftar user
        $dataList = $response->json('data') ?? [];
        if (!is_array($dataList)) {
            return [
                'success' => false,
                'message' => 'Langkah 1 Gagal: Format data respons tidak valid.',
                'status'  => 400,
            ];
        }

        $pins = $this->extractPins($dataList);

        if (empty($pins)) {
            return [
                'success' => true,
                'message' => 'Tidak ada PIN pengguna yang terdaftar di mesin.',
                'count'   => 0,
            ];
        }

        // Langkah 3: Kirim get_userinfo secara paralel untuk setiap PIN
        $this->syncUserInfo($pins);

        return [
            'success' => true,
            'message' => 'Berhasil mengirim perintah sinkronisasi untuk ' . count($pins) . ' santri terdaftar di mesin.',
            'count'   => count($pins),
            'pins'    => $pins,
        ];
    }

    /**
     * Mengekstrak daftar PIN valid dari data response API.
     *
     * @param  array  $dataList  Array data user dari response get_userlist.
     * @return array  Array berisi PIN (string) yang valid.
     */
    private function extractPins(array $dataList): array
    {
        $pins = [];

        foreach ($dataList as $item) {
            $pin = $item['pin'] ?? $item['user_id'] ?? $item['emp_pin'] ?? null;
            if ($pin !== null) {
                $pins[] = (string) $pin;
            }
        }

        return $pins;
    }

    /**
     * Mengirim request get_userinfo secara paralel menggunakan HTTP Pool.
     *
     * @param  array  $pins  Daftar PIN yang akan disinkronisasi.
     * @return void
     */
    private function syncUserInfo(array $pins): void
    {
        Http::pool(function (Pool $pool) use ($pins) {
            foreach ($pins as $pin) {
                $pool->timeout(2)->connectTimeout(2)->withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ])->post($this->userInfoUrl, [
                    'trans_id' => (string) rand(100000, 999999999),
                    'cloud_id' => $this->cloudId,
                    'pin'      => (string) $pin,
                ]);
            }
        });
    }
}
