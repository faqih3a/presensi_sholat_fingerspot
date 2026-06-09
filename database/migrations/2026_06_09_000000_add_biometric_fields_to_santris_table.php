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
        Schema::table('santris', function (Blueprint $table) {
            $table->integer('finger_count')->default(0)->after('foto_referensi');
            $table->integer('face_count')->default(0)->after('finger_count');
            $table->longText('template')->nullable()->after('face_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropColumn(['finger_count', 'face_count', 'template']);
        });
    }
};
