<?php

namespace App\Actions\Presensi;

use App\Models\Presensi;
use App\Models\Santri;
use App\Models\User;
use App\Traits\DateAndPrayerHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Aksi: Menyimpan Data Presensi Baru (Webhook Attlog & Manual)
 *
 * Class ini menangani seluruh logika penyimpanan presensi dari dua sumber:
 * 1. **Webhook Attlog** — data scan realtime dari mesin Fingerspot.
 * 2. **Manual Admin** — input status presensi langsung oleh admin.
 *
 * Fitur Utama:
 * - **First Scan Wins**: Jika santri sudah berstatus "Hadir" pada waktu
 *   sholat tersebut, scan berikutnya akan diabaikan (tidak overwrite).
 * - **Auto-Create Santri**: Jika PIN dari mesin belum dikenali, otomatis
 *   membuat akun User + record Santri sementara.
 * - **Auto-Capture Foto**: Jika santri belum punya foto profil, ambil
 *   dari photo_url hasil scan.
 * - **Alfa→Hadir Upgrade**: Jika record Alfa sudah ada (dari auto-generate),
 *   ubah ke Hadir saat santri ternyata scan.
 *
 * @see \App\Http\Controllers\PresensiController::updateStatus()
 * @see public/store.php (webhook handler)
 */
class StorePresensiAction
{
    use DateAndPrayerHelper;

    /** @var string API token Fingerspot Cloud (dari config/services.php). */
    private string $apiToken;

    /** @var string Cloud ID mesin Fingerspot (dari config/services.php). */
    private string $cloudId;

    public function __construct()
    {
        $this->apiToken = config('services.fingerspot.token');
        $this->cloudId  = config('services.fingerspot.cloud_id');
    }

    /**
     * Menyimpan data presensi dari webhook attlog mesin Fingerspot.
     *
     * Alur Proses:
     * 1. Resolve santri dari PIN (auto-create jika belum ada).
     * 2. Auto-capture foto profil dari scan jika belum ada.
     * 3. Tentukan waktu sholat dari jam scan.
     * 4. Simpan dengan logika First Scan Wins.
     *
     * @param  array  $data  Data attlog dari mesin. Berisi:
     *   - 'pin'        (string): PIN santri di mesin.
     *   - 'scan'       (string): Datetime scan (Y-m-d H:i:s).
     *   - 'verify'     (string|null): Metode verifikasi (finger/face).
     *   - 'status_scan' (string|null): Status scan dari mesin.
     *   - 'photo_url'  (string|null): URL foto saat scan.
     * @return array  Hasil proses: ['status', 'message', 'data', 'http_code'].
     */
    public function executeFromWebhook(array $data): array
    {
        $pin       = $data['pin'];
        $scanStr   = $data['scan'];
        $photoUrl  = $data['photo_url'] ?? null;

        // 1. Parse waktu scan
        try {
            $scanTime = Carbon::parse($scanStr, 'Asia/Jakarta');
        } catch (\Exception $e) {
            return $this->result('error', "Invalid scan datetime: $scanStr", [], 400);
        }

        $tanggal    = $scanTime->format('Y-m-d');
        $waktuHadir = $scanTime->format('H:i:s');

        // 2. Resolve santri dari PIN (auto-create jika perlu)
        $santriResult = $this->resolveOrCreateSantri($pin, $photoUrl);
        if (!$santriResult['success']) {
            return $this->result('error', $santriResult['message'], [], 500);
        }
        $santri = $santriResult['santri'];

        // 3. Auto-capture foto profil jika belum ada
        if (!empty($photoUrl) && (empty($santri->foto_referensi) || $santri->foto_referensi === 'default.jpg')) {
            $santri->foto_referensi = $photoUrl;
            $santri->save();
            Log::info("AUTO-PHOTO: Foto profil otomatis untuk santri {$santri->id}");
        }

        // 4. Tentukan waktu sholat
        $jadwal      = $this->getJadwalSholat($scanTime);
        $waktuSholat = $this->determineWaktuSholat($scanTime, $jadwal);

        if (!$waktuSholat) {
            $tesEnabled = Cache::get('tes_page_enabled', true);
            if (!$tesEnabled) {
                return $this->result('ok', 'Scan outside prayer window ignored (testing disabled).', [], 200);
            }
            $waktuSholat = 'Tes';
        }

        // 5. Simpan ke database (First Scan Wins)
        return $this->storeWithFirstScanWins($santri, $tanggal, $waktuSholat, $waktuHadir, $photoUrl);
    }

    /**
     * Menyimpan/update status presensi secara manual oleh admin.
     *
     * Menggunakan updateOrCreate sehingga bisa membuat record baru
     * atau mengupdate record yang sudah ada.
     *
     * @param  array  $data  Data tervalidasi. Berisi:
     *   - 'santri_id'    (int): ID santri.
     *   - 'tanggal'      (string): Tanggal presensi (Y-m-d).
     *   - 'waktu_sholat' (string): Waktu sholat.
     *   - 'status'       (string): Status (Hadir/Izin/Alfa).
     * @return \App\Models\Presensi  Instance presensi yang dibuat/diupdate.
     */
    public function executeManual(array $data): Presensi
    {
        return Presensi::updateOrCreate(
            [
                'santri_id'    => $data['santri_id'],
                'tanggal'      => $data['tanggal'],
                'waktu_sholat' => $data['waktu_sholat'],
            ],
            [
                'status'      => $data['status'],
                'waktu_hadir' => $data['status'] === 'Hadir' ? Carbon::now()->format('H:i') : null,
            ]
        );
    }

    /**
     * Resolve santri dari PIN atau auto-create jika belum ada.
     *
     * @param  string|int   $pin       PIN dari mesin.
     * @param  string|null  $photoUrl  URL foto dari scan (opsional).
     * @return array  ['success' => bool, 'santri' => Santri|null, 'message' => string].
     */
    private function resolveOrCreateSantri($pin, ?string $photoUrl): array
    {
        $santri = Santri::find($pin);

        if ($santri) {
            return ['success' => true, 'santri' => $santri, 'message' => 'found'];
        }

        try {
            $email       = strtolower('santri' . $pin) . '@thursina.id';
            $displayName = "Nama Belum Diatur (PIN: {$pin})";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $displayName,
                    'password' => Hash::make('santri'),
                    'role'     => 'santri',
                ]
            );

            $santri = new Santri();
            $santri->id             = $pin;
            $santri->user_id        = $user->id;
            $santri->nama           = $displayName;
            $santri->kelas          = 'Belum Diatur';
            $santri->foto_referensi = '';
            $santri->finger_count   = 0;
            $santri->face_count     = 1;
            $santri->save();

            Log::info("AUTO-CREATE: Santri baru dari attlog - pin=$pin, email=$email");

            // Fire & forget: trigger get_userinfo untuk ambil nama
            $this->fireAndForgetUserInfo($pin);

            return ['success' => true, 'santri' => $santri, 'message' => 'created'];
        } catch (\Exception $e) {
            Log::error("Gagal auto-create santri pin=$pin - " . $e->getMessage());
            return ['success' => false, 'santri' => null, 'message' => 'Gagal membuat santri: ' . $e->getMessage()];
        }
    }



    /**
     * Simpan presensi dengan logika First Scan Wins.
     *
     * Aturan:
     * - Tes: selalu create baru (tidak ada constraint unik).
     * - Sholat + sudah Hadir: SKIP (first scan wins).
     * - Sholat + status Alfa: UPDATE ke Hadir.
     * - Sholat + belum ada record: CREATE baru.
     *
     * @param  \App\Models\Santri  $santri       Santri yang melakukan scan.
     * @param  string              $tanggal      Tanggal scan (Y-m-d).
     * @param  string              $waktuSholat  Waktu sholat yang terdeteksi.
     * @param  string              $waktuHadir   Jam hadir (H:i:s).
     * @param  string|null         $photoUrl     URL foto scan.
     * @return array  Hasil proses.
     */
    private function storeWithFirstScanWins(Santri $santri, string $tanggal, string $waktuSholat, string $waktuHadir, ?string $photoUrl): array
    {
        // Khusus Tes: langsung create baru setiap scan
        if ($waktuSholat === 'Tes') {
            Presensi::create([
                'santri_id'   => $santri->id,
                'tanggal'     => $tanggal,
                'waktu_sholat' => $waktuSholat,
                'waktu_hadir' => $waktuHadir,
                'status'      => 'Tes',
                'photo_url'   => $photoUrl,
            ]);

            return $this->result('ok', "Presensi Tes recorded for {$santri->nama}", [
                'santri_id' => $santri->id, 'nama' => $santri->nama,
                'tanggal' => $tanggal, 'waktu_sholat' => $waktuSholat,
                'waktu_hadir' => $waktuHadir, 'action' => 'created',
            ]);
        }

        // Cek record existing
        $existing = Presensi::where('santri_id', $santri->id)
            ->where('tanggal', $tanggal)
            ->where('waktu_sholat', $waktuSholat)
            ->first();

        // Sudah Hadir → SKIP
        if ($existing && $existing->status === 'Hadir') {
            return $this->result('ok', "Presensi $waktuSholat sudah tercatat (scan diabaikan)", [
                'santri_id' => $santri->id, 'nama' => $santri->nama,
                'tanggal' => $tanggal, 'waktu_sholat' => $waktuSholat,
                'waktu_hadir' => $existing->waktu_hadir, 'action' => 'skipped',
            ]);
        }

        // Ada record non-Hadir (Alfa) → UPDATE ke Hadir
        if ($existing) {
            $existing->update([
                'waktu_hadir' => $waktuHadir,
                'status'      => 'Hadir',
                'photo_url'   => $photoUrl,
            ]);

            return $this->result('ok', "Presensi $waktuSholat updated to Hadir for {$santri->nama}", [
                'santri_id' => $santri->id, 'nama' => $santri->nama,
                'tanggal' => $tanggal, 'waktu_sholat' => $waktuSholat,
                'waktu_hadir' => $waktuHadir, 'action' => 'updated',
            ]);
        }

        // Belum ada record → CREATE baru
        Presensi::create([
            'santri_id'   => $santri->id,
            'tanggal'     => $tanggal,
            'waktu_sholat' => $waktuSholat,
            'waktu_hadir' => $waktuHadir,
            'status'      => 'Hadir',
            'photo_url'   => $photoUrl,
        ]);

        return $this->result('ok', "Presensi $waktuSholat recorded for {$santri->nama}", [
            'santri_id' => $santri->id, 'nama' => $santri->nama,
            'tanggal' => $tanggal, 'waktu_sholat' => $waktuSholat,
            'waktu_hadir' => $waktuHadir, 'action' => 'created',
        ]);
    }

    /**
     * Tentukan waktu sholat berdasarkan jam scan dan jadwal.
     *
     * Rentang presensi: 30 menit sebelum adzan s/d 10 menit setelah adzan.
     *
     * @param  \Carbon\Carbon  $scanTime  Waktu scan.
     * @param  array           $jadwal    Jadwal sholat dari API.
     * @return string|null  Nama waktu sholat, atau null jika di luar rentang.
     */
    private function determineWaktuSholat(Carbon $scanTime, array $jadwal): ?string
    {
        $date = $scanTime->format('Y-m-d');

        $ranges = [
            'Subuh'   => ['key' => 'Fajr'],
            'Dzuhur'  => ['key' => 'Dhuhr'],
            'Ashar'   => ['key' => 'Asr'],
            'Maghrib' => ['key' => 'Maghrib'],
            'Isya'    => ['key' => 'Isha'],
        ];

        foreach ($ranges as $sholat => $config) {
            if (!isset($jadwal[$config['key']])) continue;

            $adzanTime = Carbon::parse($date . ' ' . $jadwal[$config['key']], 'Asia/Jakarta');
            $start     = $adzanTime->copy()->subMinutes(30);
            $end       = $adzanTime->copy()->addMinutes(10);

            if ($scanTime->between($start, $end)) {
                return $sholat;
            }
        }

        return null;
    }

    /**
     * Fire & forget: kirim request get_userinfo ke Fingerspot API.
     *
     * @param  string|int  $pin  PIN santri.
     * @return void
     */
    private function fireAndForgetUserInfo($pin): void
    {
        try {
            Http::timeout(1)->connectTimeout(1)->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post('https://developer.fingerspot.io/api/get_userinfo', [
                'trans_id' => (string) rand(100000, 999999999),
                'cloud_id' => $this->cloudId,
                'pin'      => (string) $pin,
            ]);
        } catch (\Exception $e) {
            Log::info("AUTO-FETCH-NAME: Request sent for PIN $pin (fire & forget)");
        }
    }

    /**
     * Membuat array hasil standar untuk response webhook.
     *
     * @param  string  $status    Status: 'ok' atau 'error'.
     * @param  string  $message   Pesan deskriptif.
     * @param  array   $data      Data tambahan.
     * @param  int     $httpCode  HTTP status code (default: 200).
     * @return array
     */
    private function result(string $status, string $message, array $data = [], int $httpCode = 200): array
    {
        return [
            'status'    => $status,
            'message'   => $message,
            'data'      => $data,
            'http_code' => $httpCode,
        ];
    }
}
