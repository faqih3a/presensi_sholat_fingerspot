<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('presensis', 'photo_url')) {
            Schema::table('presensis', function (Blueprint $table) {
                $table->string('photo_url', 500)->nullable()->after('status');
            });
        }

        // Alter enum via DB statement to avoid DBAL dependency issue on changing enums
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asatidz', 'santri') NOT NULL DEFAULT 'santri'");
        
        // Update any existing 'super_admin' to 'admin'
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('presensis', 'photo_url')) {
            Schema::table('presensis', function (Blueprint $table) {
                $table->dropColumn('photo_url');
            });
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'asatidz', 'santri') NOT NULL DEFAULT 'santri'");
        
        // Revert 'admin' to 'super_admin'
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
    }
};
