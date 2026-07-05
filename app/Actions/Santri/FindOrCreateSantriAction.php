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
 * Class ini adalah SATU-SATUNYA pintu masuk untuk membuat Santri dari PIN mesin.
 * Digunakan oleh dua jalur pendaftaran:
 *
 * 1. **Metode 1 (Sync/Pull)**: Dipanggil saat webhook `get_userinfo` diterima
 *    setelah admin menekan tombol "Sinkronisasi".
 * 2. **Metode 2 (First Scan/Push)**: Dipanggil saat webhook `attlog` diterima
 *    dan PIN belum dikenal di database.
 *
 * Pencegahan Bentrok (Concurrency Safety):
 * - Menggunakan `firstOrCreate()` untuk User → atomic, no duplicate email.
 * - Menggunakan `updateOrCreate()` untuk Santri → atomic, no duplicate PIN.
 * - Wrapped dalam `DB::transaction()` untuk konsistensi.
 *
 * @see \App\Actions\Presensi\StorePresensiAction  (Metode 2 - consumer)
 * @see public/store.php handleGetUserinfo()       (Metode 1 - consumer)
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
     * Bangun payload update hanya untuk field yang benar-benar berubah.
     *
     * Aturan penting:
     * - Nama hanya di-update jika data mesin memberikan nama BARU yang valid
     *   (bukan placeholder "Nama Belum Diatur").
     * - Foto hanya di-update jika santri belum punya foto.
     * - Kelas TIDAK pernah di-overwrite oleh mesin (hanya admin yang bisa ubah).
     *
     * @param  Santri       $existing   Santri yang sudah ada.
     * @param  string       $newName    Nama display yang di-resolve.
     * @param  string|null  $rawName    Nama mentah dari mesin.
     * @param  string|null  $photoUrl   URL foto baru.
     * @param  array        $biometric  Data biometrik.
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

        // Update biometric counts jika ada
        if (isset($biometric['face']) && (int) $biometric['face'] > 0) {
            $updates['face_count'] = (int) $biometric['face'];
        }
        if (isset($biometric['finger']) && (int) $biometric['finger'] > 0) {
            $updates['finger_count'] = (int) $biometric['finger'];
        }
        if (!empty($biometric['template'])) {
            $updates['template'] = $biometric['template'];
        }

        return $updates;
    }
}
