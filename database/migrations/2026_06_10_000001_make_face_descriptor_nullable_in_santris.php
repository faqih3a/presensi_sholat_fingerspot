<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set default value untuk kolom face_descriptor agar tidak error saat insert santri baru.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE santris MODIFY COLUMN face_descriptor LONGTEXT NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE santris MODIFY COLUMN face_descriptor LONGTEXT NOT NULL");
    }
};
