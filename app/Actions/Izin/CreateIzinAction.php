<?php

namespace App\Actions\Izin;

use App\Models\Izin;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

/**
 * Aksi: Membuat Permohonan Izin Baru
 *
 * Class ini bertanggung jawab untuk menyimpan permohonan izin baru
 * ke database, termasuk menangani upload file lampiran dan mengirim
 * notifikasi WhatsApp ke seluruh Ustadz yang terdaftar.
 *
 * Alur Proses:
 * 1. Menyimpan file lampiran ke storage (jika ada).
 * 2. Membuat record Izin di database.
 * 3. Mengirim notifikasi WA ke semua Ustadz yang punya nomor WA.
 *
 * @see \App\Http\Controllers\IzinController::store()
 */
class CreateIzinAction
{
    /**
     * Menjalankan aksi pembuatan permohonan izin baru.
     *
     * @param  array  $validatedData  Data yang sudah divalidasi oleh controller. Berisi:
     *   - 'jenis_izin'       (string): Jenis izin (Sakit/Izin/Kegiatan Luar).
     *   - 'waktu_sholat'     (string|null): Waktu sholat spesifik atau 'Full Day'.
     *   - 'tanggal_mulai'    (string): Tanggal mulai izin (Y-m-d).
     *   - 'tanggal_selesai'  (string): Tanggal selesai izin (Y-m-d).
     *   - 'keterangan'       (string): Alasan/keterangan izin.
     *   - 'lampiran'         (\Illuminate\Http\UploadedFile|null): File lampiran opsional.
     * @param  int    $userId  ID user yang mengajukan izin.
     * @return \App\Models\Izin  Instance Izin yang baru dibuat.
     */
    public function execute(array $validatedData, int $userId): Izin
    {
        // 1. Siapkan data untuk insert
        $data = [
            'user_id'          => $userId,
            'jenis_izin'       => $validatedData['jenis_izin'],
            'waktu_sholat'     => $validatedData['waktu_sholat'] ?? null,
            'tanggal_mulai'    => $validatedData['tanggal_mulai'],
            'tanggal_selesai'  => $validatedData['tanggal_selesai'],
            'keterangan'       => $validatedData['keterangan'],
        ];

        // 2. Simpan lampiran jika ada
        if (isset($validatedData['lampiran']) && $validatedData['lampiran'] !== null) {
            $data['lampiran'] = $validatedData['lampiran']->store('lampiran_izin', 'public');
        }

        // 3. Buat record izin
        $izin = Izin::create($data);

        // 4. Kirim notifikasi WA ke Ustadz
        $this->notifyUstadz($izin);

        return $izin;
    }

    /**
     * Mengirim notifikasi WhatsApp ke semua Ustadz yang terdaftar.
     *
     * Proses ini bersifat fire-and-forget — kegagalan pengiriman WA
     * tidak akan menggagalkan proses pembuatan izin.
     *
     * @param  \App\Models\Izin  $izin  Record izin yang baru dibuat.
     * @return void
     */
    private function notifyUstadz(Izin $izin): void
    {
        try {
            $ustadz = User::where('role', 'ustadz')
                ->whereNotNull('wa_number')
                ->get();

            if ($ustadz->count() > 0) {
                $message = WhatsAppService::formatIzinNotification($izin);
                foreach ($ustadz as $pengurus) {
                    WhatsAppService::sendMessage($pengurus->wa_number, $message);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WA notification: ' . $e->getMessage());
        }
    }
}
