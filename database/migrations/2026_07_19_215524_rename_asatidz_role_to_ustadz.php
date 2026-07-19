<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Mengubah ENUM value role 'asatidz' menjadi 'ustadz'
 *
 * PENTING: Migration ini AMAN — tidak menghapus data apapun.
 * Hanya mengubah definisi ENUM dan meng-update nilai yang sudah ada.
 *
 * Langkah:
 * 1. Ubah ENUM agar menerima kedua value ('asatidz' DAN 'ustadz') secara bersamaan.
 * 2. Update semua row yang punya role 'asatidz' menjadi 'ustadz'.
 * 3. Ubah ENUM agar hanya menerima value baru ('ustadz'), hapus 'asatidz'.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Tambahkan 'ustadz' ke ENUM (sementara dua-duanya valid)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asatidz', 'ustadz', 'santri') NOT NULL DEFAULT 'santri'");

        // Step 2: Migrasi data — update semua 'asatidz' menjadi 'ustadz'
        DB::table('users')->where('role', 'asatidz')->update(['role' => 'ustadz']);

        // Step 3: Hapus 'asatidz' dari ENUM (finalkan)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'ustadz', 'santri') NOT NULL DEFAULT 'santri'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Tambahkan kembali 'asatidz' ke ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asatidz', 'ustadz', 'santri') NOT NULL DEFAULT 'santri'");

        // Step 2: Rollback data — kembalikan 'ustadz' menjadi 'asatidz'
        DB::table('users')->where('role', 'ustadz')->update(['role' => 'asatidz']);

        // Step 3: Hapus 'ustadz' dari ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asatidz', 'santri') NOT NULL DEFAULT 'santri'");
    }
};
