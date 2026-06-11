<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->index('tanggal');
            $table->index('status');
            $table->index('waktu_sholat');
            $table->index(['tanggal', 'waktu_sholat', 'status']);
        });

        Schema::table('izins', function (Blueprint $table) {
            $table->index('status');
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['status']);
            $table->dropIndex(['waktu_sholat']);
            $table->dropIndex(['tanggal', 'waktu_sholat', 'status']);
        });

        Schema::table('izins', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
};
