<?php

namespace App\Actions\Attendance;

use App\Models\Presensi;
use App\Traits\DateAndPrayerHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Aksi: Mengambil Data Presensi Tes (di luar waktu sholat)
 *
 * Class ini bertanggung jawab untuk mengambil data presensi tes
 * berdasarkan rentang tanggal, filter status, dan pencarian nama.
 * Juga mengolah navigasi tanggal (prev/next) untuk pagination temporal.
 *
 * @see \App\Http\Controllers\TesController::index()
 */
class FetchTesDataAction
{
    use DateAndPrayerHelper;

    /**
     * Menjalankan aksi pengambilan data tes presensi.
     *
     * @param  \Illuminate\Http\Request  $request  HTTP request dengan parameter filter.
     * @return array  Data lengkap untuk view tes.index.
     */
    public function execute(Request $request): array
    {
        $resolvedDates = $this->resolveDateRange($request);
        $mode          = $resolvedDates['mode'];
        $ref_date      = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $nav = $this->resolveNavigation($mode, $ref_date, $tanggal_mulai);

        $search = $request->input('search');
        $status = $request->input('status');

        // Build query: when() pattern
        $presensis = Presensi::with('santri')
            ->where('waktu_sholat', 'Tes')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])

            // Filter status: exact match
            ->when($status, fn($q, $v) => $q->where('status', $v))

            // Search nama santri: LIKE partial match
            ->when($search, fn($q, $v) => $q->whereHas('santri',
                fn($sq) => $sq->where('nama', 'like', "%{$v}%")
            ))

            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc')
            ->paginate(25)
            ->withQueryString();

        $tesEnabled = Cache::get('tes_page_enabled', true);

        return [
            'presensis'     => $presensis,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_akhir' => $tanggal_akhir,
            'mode'          => $mode,
            'ref_date'      => $ref_date,
            'status'        => $status,
            'search'        => $search,
            'tesEnabled'    => $tesEnabled,
        ] + $nav;
    }
}
