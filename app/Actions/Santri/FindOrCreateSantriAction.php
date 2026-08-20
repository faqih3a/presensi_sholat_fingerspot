<?php

namespace App\Actions\Santri;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Aksi: Temukan atau Buat Santri dari PIN Mesin (Atomic)
 *
 * PRINSIP: Data mesin = Data REAL (sumber kebenaran).
 *
 * Class ini adalah SATU-SATUNYA pintu masuk untuk membuat/memperbarui
 * Santri dari data mesin Fingerspot. Data biometrik dari mesin SELALU
 * menimpa data lokal di database, karena mesin adalah sumber kebenaran
 * untuk: sidik jari, wajah, dan template biometrik.
 *
 * Digunakan oleh tiga jalur:
 *
 * 1. **Metode 1 (Sync/Pull)**: Dipanggil saat webhook `get_userinfo` diterima
 *    setelah admin menekan tombol "Sinkronisasi".
 * 2. **Metode 2 (First Scan/Push)**: Dipanggil saat webhook `attlog` diterima
 *    dan PIN belum dikenal di database.
 * 3. **Metode 3 (Single Refresh)**: Dipanggil saat admin menekan tombol
 *    "Refresh dari Mesin" untuk santri tertentu.
 *
 * Pencegahan Bentrok (Concurrency Safety):
 * - Menggunakan `firstOrCreate()` untuk User → atomic, no duplicate email.
 * - Menggunakan `updateOrCreate()` untuk Santri → atomic, no duplicate PIN.
 * - Wrapped dalam `DB::transaction()` untuk konsistensi.
 *
 * @see \App\Actions\Presensi\StorePresensiAction  (Metode 2 - consumer)
 * @see public/store.php handleGetUserinfo()       (Metode 1 - consumer)
 * @see \App\Actions\Santri\FetchUserInfoFromMesinAction (Metode 3 - trigger)
 */
class FindOrCreateSantriAction
{
    /**
     * Temukan atau buat Santri berdasarkan PIN dari mesin.
     *
     * @param  string|int   $pin          PIN dari mesin Fingerspot (= santris.id).
     * @param  string|null  $name         Nama santri dari mesin (null jika belum diketahui).
     * @param  string|null  $photoUrl     URL foto dari scan/webhook (opsional).
     * @param  array        $biometric    Data biometrik opsional: ['finger', 'face', 'template'].
     * @return array{success: bool, santri: ?Santri, action: string, message: string}
     *
     * `action` bernilai: 'found', 'created', atau 'updated'.
     */
    public function execute(
        $pin,
        ?string $name = null,
        ?string $photoUrl = null,
        array $biometric = []
    ): array {
        try {
            return DB::transaction(function () use ($pin, $name, $photoUrl, $biometric) {
                $pin = (int) $pin;

                // --- Resolve display name ---
                $displayName = $this->resolveDisplayName($name, $pin);

                // --- Generate email unik berdasarkan PIN (bukan nama) ---
                // Menggunakan PIN sebagai basis email agar konsisten antara Metode 1 & 2.
                $email = 'santri' . $pin . '@thursina.id';

                // --- Atomic: Buat atau temukan User ---
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $displayName,
                        'password' => Hash::make('santri'),
                        'role'     => 'santri',
                    ]
                );

                // --- Cek apakah Santri sudah ada ---
                $existingSantri = Santri::find($pin);

                if ($existingSantri) {
                    // Santri sudah ada — update jika ada data baru dari mesin
                    $updates = $this->buildUpdatePayload(
                        $existingSantri, $displayName, $name, $photoUrl, $biometric
                    );

                    if (!empty($updates)) {
                        $existingSantri->update($updates);
                        Log::info("UPSERT-SANTRI: Updated santri PIN={$pin}, fields=" . implode(',', array_keys($updates)));

                        // Sinkronkan nama User jika nama santri berubah
                        if (isset($updates['nama']) && $user->name !== $updates['nama']) {
                            $user->update(['name' => $updates['nama']]);
                        }

                        return [
                            'success' => true,
                            'santri'  => $existingSantri->fresh(),
                            'action'  => 'updated',
                            'message' => "Santri PIN {$pin} diperbarui.",
                        ];
                    }

                    return [
                        'success' => true,
                        'santri'  => $existingSantri,
                        'action'  => 'found',
                        'message' => "Santri PIN {$pin} sudah ada, tidak ada perubahan.",
                    ];
                }

                // --- Santri belum ada — buat baru ---
                $santri = new Santri();
                $santri->id             = $pin;
                $santri->user_id        = $user->id;
                $santri->nama           = $displayName;
                $santri->kelas          = 'Belum Diatur';
                $santri->foto_referensi = $photoUrl ?? '';
                $santri->finger_count   = (int) ($biometric['finger'] ?? 0);
                $santri->face_count     = (int) ($biometric['face'] ?? 1);
                $santri->template       = $biometric['template'] ?? null;
                $santri->save();

                Log::info("UPSERT-SANTRI: Created santri PIN={$pin}, nama={$displayName}, email={$email}");

                return [
                    'success' => true,
                    'santri'  => $santri,
                    'action'  => 'created',
                    'message' => "Santri baru PIN {$pin} berhasil didaftarkan.",
                ];
            });
        } catch (\Exception $e) {
            Log::error("UPSERT-SANTRI: Gagal untuk PIN={$pin} — " . $e->getMessage());
            return [
                'success' => false,
                'santri'  => null,
                'action'  => 'error',
                'message' => 'Gagal membuat/memperbarui santri: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve nama display dari input mesin.
     *
     * @param  string|null  $name  Nama dari mesin.
     * @param  int          $pin   PIN sebagai fallback.
     * @return string
     */
    private function resolveDisplayName(?string $name, int $pin): string
    {
        $cleaned = trim($name ?? '');

        if ($cleaned === '' || $cleaned === '-') {
            return "Nama Belum Diatur (PIN: {$pin})";
        }

        return $cleaned;
    }

    /**
     * Bangun payload update berdasarkan data dari mesin (source of truth).
     *
     * PRINSIP UTAMA: Data mesin = Data REAL.
     * Semua data biometrik dari mesin SELALU menimpa data lokal,
     * karena mesin adalah satu-satunya sumber kebenaran untuk:
     * - Jumlah sidik jari terdaftar (finger_count)
     * - Jumlah wajah terdaftar (face_count)
     * - Template biometrik
     *
     * Aturan:
     * - Nama: di-update jika mesin memberikan nama valid (bukan placeholder).
     * - Foto: di-update jika santri belum punya foto.
     * - Biometrik: SELALU di-update dari mesin (termasuk jika nilai 0,
     *   artinya data dihapus dari mesin).
     * - Kelas: TIDAK pernah di-overwrite oleh mesin (hanya admin yang bisa ubah).
     *
     * @param  Santri       $existing   Santri yang sudah ada.
     * @param  string       $newName    Nama display yang di-resolve.
     * @param  string|null  $rawName    Nama mentah dari mesin.
     * @param  string|null  $photoUrl   URL foto baru.
     * @param  array        $biometric  Data biometrik dari mesin.
     * @return array  Field yang perlu di-update (bisa kosong).
     */
    private function buildUpdatePayload(
        Santri $existing,
        string $newName,
        ?string $rawName,
        ?string $photoUrl,
        array $biometric
    ): array {
        $updates = [];

        // Update nama hanya jika mesin memberikan nama valid (bukan placeholder)
        $rawCleaned = trim($rawName ?? '');
        $isPlaceholder = str_starts_with($newName, 'Nama Belum Diatur');

        if (!$isPlaceholder && $existing->nama !== $newName) {
            $updates['nama'] = $newName;
        }

        // Update foto hanya jika santri belum punya foto profil
        if (
            !empty($photoUrl) &&
            (empty($existing->foto_referensi) || $existing->foto_referensi === 'default.jpg')
        ) {
            $updates['foto_referensi'] = $photoUrl;
        }

        // ─── BIOMETRIK: Mesin = Sumber Kebenaran ───────────────────
        // Data biometrik dari mesin SELALU menimpa data lokal.
        // Jika mesin mengirim face=0, artinya wajah sudah dihapus dari mesin.
        // Jika mesin mengirim finger=0, artinya sidik jari sudah dihapus.
        // Sistem HARUS mencerminkan kondisi aktual mesin.
        if (isset($biometric['face'])) {
            $faceCount = (int) $biometric['face'];
            if ($existing->face_count !== $faceCount) {
                $updates['face_count'] = $faceCount;
            }
        }
        if (isset($biometric['finger'])) {
            $fingerCount = (int) $biometric['finger'];
            if ($existing->finger_count !== $fingerCount) {
                $updates['finger_count'] = $fingerCount;
            }
        }
        if (array_key_exists('template', $biometric)) {
            $template = $biometric['template'] ?? null;
            if ($existing->template !== $template) {
                $updates['template'] = $template;
            }
        }

        return $updates;
    }
}
