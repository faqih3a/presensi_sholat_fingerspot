<?php

namespace App\Actions\Dashboard;

use App\Models\Presensi;
use Carbon\Carbon;

/**
 * Aksi: Mengambil Data Chart Mingguan untuk Dashboard
 *
 * Menghitung data chart kehadiran 7 hari terakhir (bar chart)
 * dan chart range harian (line chart) dalam periode pilihan user.
 *
 * Optimasi: Satu query agregat per chart type.
 *
 * @see \App\Http\Controllers\DashboardController::index()
 */
class FetchWeeklyChartDataAction
{
    private const DAY_NAMES = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    /**
     * @param  string  $startDate  Tanggal awal periode (Y-m-d).
     * @param  string  $endDate    Tanggal akhir periode (Y-m-d).
     * @return array
     */
    public function execute(string $startDate, string $endDate): array
    {
        return array_merge(
            $this->buildWeeklyData(),
            $this->buildRangeChartData($startDate, $endDate)
        );
    }

    private function buildWeeklyData(): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $startWeekly = $now->copy()->subDays(6)->format('Y-m-d');
        $endWeekly = $now->format('Y-m-d');

        $weeklyCounts = Presensi::whereBetween('tanggal', [$startWeekly, $endWeekly])
            ->selectRaw('tanggal, status, COUNT(DISTINCT santri_id) as total')
            ->groupBy('tanggal', 'status')->get()->groupBy('tanggal');

        $weeklyLabels = $weeklyHadir = $weeklyIzin = $weeklyAlfa = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $dateStr = $d->format('Y-m-d');
            $weeklyLabels[] = $d->format('d M') . ' (' . self::DAY_NAMES[$d->dayOfWeek] . ')';
            $dayRecords = $weeklyCounts->get($dateStr) ?? collect();
            $h = $iz = $a = 0;
            foreach ($dayRecords as $rec) {
                $s = strtolower($rec->status);
                if ($s === 'hadir') $h += $rec->total;
                elseif ($s === 'izin' || $s === 'sakit') $iz += $rec->total;
                elseif (in_array($s, ['alfa', 'alpha'])) $a += $rec->total;
            }
            $weeklyHadir[] = $h;
            $weeklyIzin[] = $iz;
            $weeklyAlfa[] = $a;
        }

        $weeklyInsight = $this->generateWeeklyInsight($weeklyLabels, $weeklyHadir, $weeklyAlfa);
        return compact('weeklyLabels', 'weeklyHadir', 'weeklyIzin', 'weeklyAlfa', 'weeklyInsight');
    }

    private function buildRangeChartData(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate, 'Asia/Jakarta');
        $end = Carbon::parse($endDate, 'Asia/Jakarta');
        if ($start->diffInDays($end) > 31) $start = $end->copy()->subDays(30);

        $dailyCounts = Presensi::whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->selectRaw('tanggal, COUNT(DISTINCT santri_id) as total')
            ->groupBy('tanggal')->pluck('total', 'tanggal')->toArray();

        $chartLabels = $chartData = [];
        while ($start->lte($end)) {
            $chartLabels[] = $start->format('d M');
            $chartData[] = $dailyCounts[$start->format('Y-m-d')] ?? 0;
            $start->addDay();
        }
        return compact('chartLabels', 'chartData');
    }

    private function generateWeeklyInsight(array $labels, array $hadir, array $alfa): string
    {
        $maxHadirDay = $maxAlfaDay = '';
        $maxHadirVal = $maxAlfaVal = -1;
        for ($i = 0; $i < 7; $i++) {
            if ($hadir[$i] > $maxHadirVal) { $maxHadirVal = $hadir[$i]; $maxHadirDay = $labels[$i]; }
            if ($alfa[$i] > $maxAlfaVal) { $maxAlfaVal = $alfa[$i]; $maxAlfaDay = $labels[$i]; }
        }
        $insight = "Kehadiran terbanyak pada {$maxHadirDay} ({$maxHadirVal} santri).";
        $insight .= $maxAlfaVal > 0
            ? " Perlu perhatian pada {$maxAlfaDay} karena terdapat {$maxAlfaVal} santri alfa."
            : " Pekan ini tingkat kedisiplinan santri sangat baik.";
        return $insight;
    }
}
