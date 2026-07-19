<?php

namespace App\Actions\Dashboard;

use App\Models\Izin;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\User;
use Carbon\Carbon;

/**
 * Aksi: Mengambil Log Aktivitas Terbaru untuk Dashboard
 *
 * Class ini bertanggung jawab mengumpulkan dan menggabungkan 4 jenis
 * aktivitas terbaru dari berbagai sumber data:
 * 1. Perizinan (Izin yang diajukan/disetujui/ditolak)
 * 2. Ketidakhadiran (Record Alfa)
 * 3. Pendaftaran Santri Baru
 * 4. Pendaftaran Staf/Admin Baru
 *
 * Semua aktivitas digabungkan menjadi satu koleksi, diurutkan berdasarkan
 * waktu terbaru, lalu dibatasi 5 item teratas untuk ditampilkan di widget
 * "Aktivitas Terbaru" di dashboard.
 *
 * Optimasi Query:
 * - Menggunakan eager loading `with('user.santri')` pada query Izin.
 * - Setiap sumber dibatasi `take(15)` sebelum digabung, sehingga
 *   tidak mengambil seluruh tabel.
 *
 * @see \App\Http\Controllers\DashboardController::index()
 */
class FetchRecentActivitiesAction
{
    /**
     * Menjalankan aksi pengambilan aktivitas terbaru.
     *
     * @param  int  $limit  Jumlah maksimum aktivitas yang dikembalikan (default: 5).
     * @return \Illuminate\Support\Collection  Koleksi objek aktivitas yang sudah diurutkan.
     */
    public function execute(int $limit = 5)
    {
        $izins      = $this->fetchIzinActivities();
        $alfas      = $this->fetchAlfaActivities();
        $newSantris = $this->fetchNewSantriActivities();
        $newStaffs  = $this->fetchNewStaffActivities();

        return collect()
            ->concat($izins)
            ->concat($alfas)
            ->concat($newSantris)
            ->concat($newStaffs)
            ->sortByDesc(fn ($activity) => $activity->scan_time)
            ->take($limit);
    }

    /**
     * Mengambil aktivitas perizinan (Izin) terbaru.
     *
     * @return \Illuminate\Support\Collection
     */
    private function fetchIzinActivities()
    {
        return Izin::with('user.santri')
            ->latest('updated_at')
            ->take(15)
            ->get()
            ->map(function ($izin) {
                $user = $izin->user;
                $santri = $user ? $user->santri : null;

                $name   = $santri ? $santri->nama : ($user ? $user->name : 'Tanpa Nama');
                $detail = $santri ? 'Kelas ' . $santri->kelas : ($user ? ucfirst($user->role) : 'Santri');
                $avatar = ($santri && $santri->foto_referensi) ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;

                if ($user && $user->avatar && !$avatar) {
                    $avatar = asset('storage/avatars/' . $user->avatar);
                }

                $status = $izin->status;
                $color  = 'warning';
                $icon   = 'bi-file-earmark-text-fill';
                $msg    = "Mengajukan Izin {$izin->jenis_izin}";

                if ($status === 'Disetujui') {
                    $color = 'success';
                    $icon  = 'bi-check-circle-fill';
                    $msg   = "Izin {$izin->jenis_izin} Disetujui";
                } elseif ($status === 'Ditolak') {
                    $color = 'danger';
                    $icon  = 'bi-x-circle-fill';
                    $msg   = "Izin {$izin->jenis_izin} Ditolak";
                }

                return (object) [
                    'name'             => $name,
                    'detail'           => $detail,
                    'avatar'           => $avatar,
                    'scan_time'        => $izin->updated_at,
                    'verify_icon'      => $icon,
                    'verify_method'    => 'Perizinan',
                    'status_scan_label' => $msg,
                    'color'            => $color,
                ];
            });
    }

    /**
     * Mengambil aktivitas ketidakhadiran (Alfa) terbaru.
     *
     * @return \Illuminate\Support\Collection
     */
    private function fetchAlfaActivities()
    {
        return Presensi::with('santri')
            ->whereIn('status', ['Alfa', 'Alpha'])
            ->latest('updated_at')
            ->take(15)
            ->get()
            ->map(function ($presensi) {
                $santri = $presensi->santri;
                $name   = $santri ? $santri->nama : 'PIN ' . $presensi->santri_id;
                $detail = $santri ? 'Kelas ' . $santri->kelas : 'Santri';
                $avatar = ($santri && $santri->foto_referensi) ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;

                return (object) [
                    'name'             => $name,
                    'detail'           => $detail,
                    'avatar'           => $avatar,
                    'scan_time'        => $presensi->updated_at ?? Carbon::parse($presensi->tanggal . ' 18:00:00'),
                    'verify_icon'      => 'bi-x-circle-fill',
                    'verify_method'    => 'Ketidakhadiran',
                    'status_scan_label' => "Alfa Sholat {$presensi->waktu_sholat}",
                    'color'            => 'danger',
                ];
            });
    }

    /**
     * Mengambil aktivitas pendaftaran santri baru.
     *
     * @return \Illuminate\Support\Collection
     */
    private function fetchNewSantriActivities()
    {
        return Santri::latest('created_at')
            ->take(15)
            ->get()
            ->map(function ($santri) {
                $avatar = $santri->foto_referensi ? asset('storage/santri_fotos/' . $santri->foto_referensi) : null;

                return (object) [
                    'name'             => $santri->nama,
                    'detail'           => 'Kelas ' . $santri->kelas,
                    'avatar'           => $avatar,
                    'scan_time'        => $santri->created_at,
                    'verify_icon'      => 'bi-person-plus-fill',
                    'verify_method'    => 'Santri Baru',
                    'status_scan_label' => 'Santri terdaftar aktif',
                    'color'            => 'primary',
                ];
            });
    }

    /**
     * Mengambil aktivitas pendaftaran staf/admin baru.
     *
     * @return \Illuminate\Support\Collection
     */
    private function fetchNewStaffActivities()
    {
        return User::whereIn('role', ['ustadz', 'admin'])
            ->latest('created_at')
            ->take(15)
            ->get()
            ->map(function ($user) {
                $detail = ucfirst($user->role);
                $avatar = $user->avatar ? asset('storage/avatars/' . $user->avatar) : null;

                return (object) [
                    'name'             => $user->name,
                    'detail'           => $detail,
                    'avatar'           => $avatar,
                    'scan_time'        => $user->created_at,
                    'verify_icon'      => 'bi-person-badge-fill',
                    'verify_method'    => 'Pengurus Baru',
                    'status_scan_label' => "Akun {$detail} terdaftar",
                    'color'            => 'info',
                ];
            });
    }
}
