<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Izin;
use App\Actions\Izin\CreateIzinAction;
use App\Actions\Izin\ApproveIzinAction;
use App\Actions\Izin\RejectIzinAction;
use App\Traits\DateAndPrayerHelper;

/**
 * Controller untuk manajemen Perizinan Santri.
 *
 * Controller ini mengikuti prinsip "Thin Controller" — hanya bertugas:
 * 1. Menerima dan memvalidasi HTTP Request.
 * 2. Mendelegasikan logika bisnis ke Action Class yang sesuai.
 * 3. Mengembalikan HTTP Response (view atau redirect).
 *
 * @see \App\Actions\Izin\CreateIzinAction
 * @see \App\Actions\Izin\ApproveIzinAction
 * @see \App\Actions\Izin\RejectIzinAction
 */
class IzinController extends Controller
{
    use DateAndPrayerHelper;

    /**
     * Menampilkan daftar izin milik user yang sedang login.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $izins = Izin::where('user_id', auth()->id())->latest()->get();
        return view('izin.index', compact('izins'));
    }

    /**
     * Menampilkan form pengajuan izin baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('izin.create');
    }

    /**
     * Menyimpan permohonan izin baru.
     *
     * Validasi dilakukan di sini, lalu logika penyimpanan dan
     * notifikasi WA didelegasikan ke CreateIzinAction.
     *
     * @param  \Illuminate\Http\Request               $request
     * @param  \App\Actions\Izin\CreateIzinAction      $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, CreateIzinAction $action)
    {
        $validated = $request->validate([
            'jenis_izin'      => 'required|in:Sakit,Izin,Kegiatan Luar',
            'waktu_sholat'    => 'nullable|string|in:Full Day,Subuh,Dzuhur,Ashar,Maghrib,Isya',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string',
            'lampiran'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Sertakan file lampiran jika ada
        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $request->file('lampiran');
        }

        $action->execute($validated, auth()->id());

        return redirect()->route('izin.index')->with('success', 'Permohonan izin berhasil diajukan.');
    }

    /**
     * Menampilkan halaman manajemen izin untuk Admin/Asatidz.
     *
     * Menampilkan daftar izin yang jatuh dalam rentang tanggal yang dipilih,
     * dengan navigasi prev/next berdasarkan mode (day/week/month).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function manage(Request $request)
    {
        // Allowed for both Admin and Asatidz (pengurus masjid)
        if (!in_array(auth()->user()->role, ['admin', 'asatidz'])) {
            abort(403);
        }

        $resolvedDates = $this->resolveDateRange($request);
        $mode          = $resolvedDates['mode'];
        $ref_date      = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $refDate = \Carbon\Carbon::parse($ref_date, 'Asia/Jakarta');
        if ($mode === 'week') {
            $prev_date    = $refDate->copy()->subWeek()->format('Y-m-d');
            $next_date    = $refDate->copy()->addWeek()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } elseif ($mode === 'month') {
            $prev_date    = $refDate->copy()->subMonth()->format('Y-m-d');
            $next_date    = $refDate->copy()->addMonth()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } else {
            $prev_date    = $refDate->copy()->subDay()->format('Y-m-d');
            $next_date    = $refDate->copy()->addDay()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        }

        $izins = Izin::with('user.santri')
            ->where(function ($query) use ($tanggal_mulai, $tanggal_akhir) {
                $query->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_akhir])
                    ->orWhereBetween('tanggal_selesai', [$tanggal_mulai, $tanggal_akhir])
                    ->orWhere(function ($q) use ($tanggal_mulai, $tanggal_akhir) {
                        $q->where('tanggal_mulai', '<=', $tanggal_mulai)
                            ->where('tanggal_selesai', '>=', $tanggal_akhir);
                    });
            })
            ->latest()
            ->get();

        return view('izin.manage', compact(
            'izins', 'tanggal_mulai', 'tanggal_akhir',
            'mode', 'ref_date', 'prev_date', 'next_date', 'display_date'
        ));
    }

    /**
     * Memperbarui status permohonan izin (Disetujui/Ditolak).
     *
     * Mendelegasikan ke ApproveIzinAction atau RejectIzinAction
     * berdasarkan nilai status yang dikirim.
     *
     * @param  \Illuminate\Http\Request               $request
     * @param  \App\Models\Izin                       $izin
     * @param  \App\Actions\Izin\ApproveIzinAction     $approveAction
     * @param  \App\Actions\Izin\RejectIzinAction      $rejectAction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(
        Request $request,
        Izin $izin,
        ApproveIzinAction $approveAction,
        RejectIzinAction $rejectAction
    ) {
        if (!in_array(auth()->user()->role, ['admin', 'asatidz'])) {
            abort(403);
        }

        $validated = $request->validate([
            'status'           => 'required|in:Disetujui,Ditolak',
            'keterangan_admin' => 'nullable|string',
        ]);

        if ($validated['status'] === 'Disetujui') {
            $approveAction->execute($izin, $validated['keterangan_admin'] ?? null);
        } else {
            $rejectAction->execute($izin, $validated['keterangan_admin'] ?? null);
        }

        return redirect()->back()->with('success', 'Status permohonan izin berhasil diperbarui.');
    }
}
