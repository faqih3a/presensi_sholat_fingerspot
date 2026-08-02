<?php

namespace App\Console\Commands;

use App\Models\Izin;
use App\Models\Presensi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-old-data
                            {--dry-run : Tampilkan jumlah data yang akan dihapus tanpa benar-benar menghapus}
                            {--days= : Override jumlah hari retensi (default: 90)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus data presensi dan izin yang sudah lebih dari 90 hari (Data Retention Policy)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $overrideDays = $this->option('days');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     DATA RETENTION - PEMBERSIHAN BERKALA     ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        if ($isDryRun) {
            $this->warn('⚠  Mode DRY-RUN: Tidak ada data yang benar-benar dihapus.');
            $this->info('');
        }

        $totalDeleted = 0;

        // ─── 1. Prune Presensi ──────────────────────────────
        $totalDeleted += $this->pruneModel(
            model: new Presensi(),
            label: 'Presensi',
            dateColumn: 'tanggal',
            retentionDays: $overrideDays ?? Presensi::RETENTION_DAYS,
            isDryRun: $isDryRun,
        );

        // ─── 2. Prune Izin ─────────────────────────────────
        $totalDeleted += $this->pruneModel(
            model: new Izin(),
            label: 'Izin',
            dateColumn: 'created_at',
            retentionDays: $overrideDays ?? Izin::RETENTION_DAYS,
            isDryRun: $isDryRun,
        );

        // ─── Summary ───────────────────────────────────────
        $this->info('');
        $this->info('────────────────────────────────────────────────');

        if ($isDryRun) {
            $this->warn("📊 Total data yang AKAN dihapus: {$totalDeleted} records");
        } else {
            $this->info("✅ Total data dihapus: {$totalDeleted} records");
            Log::channel('single')->info("[Data Retention] Pembersihan selesai. Total dihapus: {$totalDeleted} records.");
        }

        $this->info('');

        return Command::SUCCESS;
    }

    /**
     * Prune a specific model's old data in chunks.
     */
    private function pruneModel(
        $model,
        string $label,
        string $dateColumn,
        int $retentionDays,
        bool $isDryRun,
    ): int {
        $cutoffDate = now()->subDays($retentionDays);

        $query = $model->newQuery()->where($dateColumn, '<=', $cutoffDate);
        $count = $query->count();

        $this->info("📋 {$label}:");
        $this->info("   Retensi     : {$retentionDays} hari");
        $this->info("   Cutoff date : {$cutoffDate->format('Y-m-d')}");
        $this->info("   Data lama   : {$count} records");

        if ($count === 0) {
            $this->info("   Status      : ✓ Tidak ada data yang perlu dihapus");
            $this->info('');
            return 0;
        }

        if ($isDryRun) {
            $this->warn("   Status      : ⚠ {$count} records AKAN dihapus (dry-run)");
            $this->info('');
            return $count;
        }

        // Hapus dalam chunk agar tidak membebani database
        $chunkSize = 1000;
        $deleted = 0;

        $this->output->write("   Menghapus   : ");

        do {
            $batch = $model->newQuery()
                ->where($dateColumn, '<=', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $deleted += $batch;
            $this->output->write(".");
        } while ($batch > 0);

        $this->info('');
        $this->info("   Status      : ✅ {$deleted} records berhasil dihapus");
        $this->info('');

        Log::channel('single')->info("[Data Retention] {$label}: {$deleted} records dihapus (cutoff: {$cutoffDate->format('Y-m-d')}).");

        return $deleted;
    }
}
