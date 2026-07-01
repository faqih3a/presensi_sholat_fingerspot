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

        $search = $request->get('search');
        $status = $request->get('status');

        // Ambil data presensi Tes saja
        $query = Presensi::with('santri')
            ->where('waktu_sholat', 'Tes')
            ->whereBetween('tanggal', [$tanggal_mulai, $tanggal_akhir])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_hadir', 'desc');

        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->whereHas('santri', function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%');
            });
        }

        $presensis = $query->get();

        $tesEnabled = Cache::get('tes_page_enabled', true);

        return [
            'presensis'     => $presensis,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_akhir' => $tanggal_akhir,
            'mode'          => $mode,
            'ref_date'      => $ref_date,
            'prev_date'     => $nav['prev_date'],
            'next_date'     => $nav['next_date'],
            'display_date'  => $nav['display_date'],
            'status'        => $status,
            'search'        => $search,
            'tesEnabled'    => $tesEnabled,
        ];
    }
}
