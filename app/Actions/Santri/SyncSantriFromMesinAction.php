<?php

namespace App\Actions\Santri;

use App\Models\Santri;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;

/**
 * Aksi: Sinkronisasi Data Santri dari Mesin Fingerspot
 *
 * PRINSIP: Data mesin = Data REAL (sumber kebenaran).
 *
 * Alur sinkronisasi:
 * 1. GET: Tarik daftar semua PIN dari mesin via `get_userlist`
 * 2. COMPARE: Bandingkan PIN mesin dengan data santri di database
 * 3. SEND: Kirim perintah `get_userinfo` untuk setiap PIN di mesin
 *    → Data aktual dikirim asinkron via webhook ke store.php
 * 4. REPORT: Laporkan PIN yang ada di mesin vs yang ada di sistem
 *
 * Skenario:
 * - PIN ada di mesin, ada di DB → data akan di-update dari mesin (overwrite)
 * - PIN ada di mesin, TIDAK ada di DB → santri baru akan dibuat via webhook
 * - PIN ada di DB, TIDAK ada di mesin → ditandai sebagai "tidak aktif di mesin"
 *
 * @see \App\Http\Controllers\SantriController::syncMesin()
 * @see \App\Actions\Santri\FetchUserInfoFromMesinAction
 * @see \App\Actions\Santri\FindOrCreateSantriAction
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
     * @return array  ['success', 'message', 'count'?, 'pins'?, 'comparison'?]
     */
    public function execute(): array
    {
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
        ];

        // ─── Langkah 1: Tarik daftar PIN dari get_userlist ──────────
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

        // ─── Langkah 2: Ekstrak PIN valid dari mesin ────────────────
        $machinePins = array_filter(array_map(
            fn($item) => isset($item['pin']) ? (string)$item['pin']
                : (isset($item['user_id']) ? (string)$item['user_id']
                : (isset($item['emp_pin']) ? (string)$item['emp_pin'] : null)),
            $dataList
        ));

        if (empty($machinePins)) {
            return ['success' => true, 'message' => 'Tidak ada PIN pengguna yang terdaftar di mesin.', 'count' => 0];
        }

        // ─── Langkah 3: Bandingkan dengan data di database ──────────
        $dbSantriIds = Santri::pluck('id')->map(fn($id) => (string) $id)->toArray();
        $machinePinValues = array_values($machinePins);

        $comparison = [
            'on_machine_total'   => count($machinePinValues),
            'in_db_total'        => count($dbSantriIds),
            'in_both'            => array_values(array_intersect($machinePinValues, $dbSantriIds)),
            'only_on_machine'    => array_values(array_diff($machinePinValues, $dbSantriIds)),
            'only_in_db'         => array_values(array_diff($dbSantriIds, $machinePinValues)),
        ];

        Log::info("SYNC-MESIN: Machine has " . count($machinePinValues) . " users, DB has " . count($dbSantriIds) . " santri. " .
            "Both: " . count($comparison['in_both']) . ", " .
            "Only Machine: " . count($comparison['only_on_machine']) . ", " .
            "Only DB: " . count($comparison['only_in_db']));

        // ─── Langkah 4: Kirim get_userinfo paralel untuk setiap PIN ─
        // Mesin akan mengirimkan data aktual via webhook (asinkron).
        // FindOrCreateSantriAction akan memproses dan SELALU update
        // data dari mesin sebagai sumber kebenaran.
        Http::pool(function (Pool $pool) use ($machinePinValues, $headers) {
            foreach ($machinePinValues as $pin) {
                $pool->timeout(2)->connectTimeout(2)->withHeaders($headers)->post($this->userInfoUrl, [
                    'trans_id' => (string) rand(100000, 999999999),
                    'cloud_id' => $this->cloudId,
                    'pin'      => $pin,
                ]);
            }
        });

        // ─── Langkah 5: Tandai santri yang tidak ada di mesin ───────
        // Reset biometric data untuk santri yang hanya ada di DB
        // tapi tidak ada di mesin (data mesin = data real)
        if (!empty($comparison['only_in_db'])) {
            $resetCount = Santri::whereIn('id', $comparison['only_in_db'])
                ->where(function ($q) {
                    $q->where('finger_count', '>', 0)
                      ->orWhere('face_count', '>', 0);
                })
                ->update([
                    'finger_count' => 0,
                    'face_count'   => 0,
                    'template'     => null,
                ]);

            if ($resetCount > 0) {
                Log::info("SYNC-MESIN: Reset biometric data for {$resetCount} santri not found on machine: " .
                    implode(', ', $comparison['only_in_db']));
            }
        }

        return [
            'success'    => true,
            'message'    => 'Berhasil mengirim perintah sinkronisasi untuk ' . count($machinePinValues) . ' pengguna terdaftar di mesin. ' .
                (count($comparison['only_on_machine']) > 0
                    ? count($comparison['only_on_machine']) . ' pengguna baru akan ditambahkan. '
                    : '') .
                (count($comparison['only_in_db']) > 0
                    ? count($comparison['only_in_db']) . ' santri tidak ditemukan di mesin (biometrik direset).'
                    : ''),
            'count'      => count($machinePinValues),
            'pins'       => $machinePinValues,
            'comparison' => $comparison,
        ];
    }
}
