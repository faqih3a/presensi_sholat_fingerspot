<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah index performa untuk mempercepat query filter.
 *
 * Index dirancang berdasarkan audit pola query yang paling sering digunakan:
 * 
 * presensis:
 * - [santri_id, tanggal]       → Dashboard stats: countByStatus(), fetchAbsentSantris()
 * - [tanggal, status]          → Dashboard stats: whereBetween + where status (paling sering)
 * - [santri_id, tanggal, status] → SantriDashboard: filter per santri + periode + status
 * - updated_at                 → latestScans polling, RecentActivities sort
 *
 * santris:
 * - nama                       → Search LIKE '%keyword%' di Kehadiran & Santri list
 * - kelas                      → Filter exact match kelas
 *
 * izins:
 * - [user_id, status]          → Dashboard: fullDayIzinSantriIds subquery
 *
 * users:
 * - role                       → RecentActivities: whereIn('role', [...])
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            // Composite index: query paling umum di dashboard stats
            // Covers: WHERE tanggal BETWEEN ... AND status = ...
            $table->index(['tanggal', 'status'], 'idx_presensis_tanggal_status');

            // Composite index: query per santri + tanggal (SantriDashboard, latestScans)
            $table->index(['santri_id', 'tanggal'], 'idx_presensis_santri_tanggal');

            // Triple composite: santri dashboard filtered by status
            $table->index(['santri_id', 'tanggal', 'status'], 'idx_presensis_santri_tanggal_status');

            // updated_at: used by latestScans polling & RecentActivities ordering
            $table->index('updated_at', 'idx_presensis_updated_at');
        });

        Schema::table('santris', function (Blueprint $table) {
            // nama: search LIKE di FetchPresensiDataAction, SantriController
            $table->index('nama', 'idx_santris_nama');

            // kelas: exact match filter
            $table->index('kelas', 'idx_santris_kelas');
        });

        Schema::table('izins', function (Blueprint $table) {
            // Composite: dashboard subquery WHERE user_id IN (...) AND status = 'Disetujui'
            $table->index(['user_id', 'status'], 'idx_izins_user_status');

            // updated_at: RecentActivities ordering
            $table->index('updated_at', 'idx_izins_updated_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // role: RecentActivities whereIn('role', ['ustadz', 'admin'])
            $table->index('role', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropIndex('idx_presensis_tanggal_status');
            $table->dropIndex('idx_presensis_santri_tanggal');
            $table->dropIndex('idx_presensis_santri_tanggal_status');
            $table->dropIndex('idx_presensis_updated_at');
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->dropIndex('idx_santris_nama');
            $table->dropIndex('idx_santris_kelas');
        });

        Schema::table('izins', function (Blueprint $table) {
            $table->dropIndex('idx_izins_user_status');
            $table->dropIndex('idx_izins_updated_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
        });
    }
};
