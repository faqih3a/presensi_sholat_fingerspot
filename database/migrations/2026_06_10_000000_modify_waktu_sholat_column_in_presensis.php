<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ubah kolom waktu_sholat dari ENUM ke STRING
     * agar bisa menerima nilai 'Tes' untuk presensi diluar waktu sholat.
     */
    public function up(): void
    {
        // Ubah ENUM menjadi VARCHAR agar fleksibel menerima 'Tes' dan nilai lainnya
        DB::statement("ALTER TABLE presensis MODIFY COLUMN waktu_sholat VARCHAR(20) NOT NULL");
    }

    /**
     * Kembalikan ke ENUM jika di-rollback.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE presensis MODIFY COLUMN waktu_sholat ENUM('Subuh','Dzuhur','Ashar','Maghrib','Isya') NOT NULL");
    }
};
