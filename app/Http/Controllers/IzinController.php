<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Izin;
use App\Actions\Izin\CreateIzinAction;
use App\Actions\Izin\UpdateIzinStatusAction;
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
 * @see \App\Actions\Izin\UpdateIzinStatusAction
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
     * Menampilkan halaman manajemen izin untuk Admin/Ustadz.
     *
     * Menampilkan daftar izin yang jatuh dalam rentang tanggal yang dipilih,
     * dengan navigasi prev/next berdasarkan mode (day/week/month).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function manage(Request $request)
    {
        $resolvedDates = $this->resolveDateRange($request);
        $mode          = $resolvedDates['mode'];
        $ref_date      = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $nav = $this->resolveNavigation($mode, $ref_date, $tanggal_mulai);
        $prev_date    = $nav['prev_date'];
        $next_date    = $nav['next_date'];
        $display_date = $nav['display_date'];

        // ── Tangkap parameter filter ──
        $search    = $request->input('search');
        $status    = $request->input('status');       // Pending / Disetujui / Ditolak
        $jenisIzin = $request->input('jenis_izin');   // Sakit / Izin / Kegiatan Luar

        // ── Build query: when() pattern ──
        $izins = Izin::with('user.santri')
            ->where(function ($query) use ($tanggal_mulai, $tanggal_akhir) {
                $query->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_akhir])
                    ->orWhereBetween('tanggal_selesai', [$tanggal_mulai, $tanggal_akhir])
                    ->orWhere(function ($q) use ($tanggal_mulai, $tanggal_akhir) {
                        $q->where('tanggal_mulai', '<=', $tanggal_mulai)
                            ->where('tanggal_selesai', '>=', $tanggal_akhir);
                    });
            })

            // Search nama user: LIKE partial match
            ->when($search, fn($q, $v) => $q->whereHas('user',
                fn($uq) => $uq->where('name', 'like', "%{$v}%")
            ))

            // Filter status izin: exact match
            ->when($status, fn($q, $v) => $q->where('status', $v))

            // Filter jenis izin: exact match
            ->when($jenisIzin, fn($q, $v) => $q->where('jenis_izin', $v))

            ->latest()
            ->paginate(15)
            ->withQueryString();

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
    public function updateStatus(Request $request, Izin $izin, UpdateIzinStatusAction $action)
    {
        $validated = $request->validate([
            'status'           => 'required|in:Disetujui,Ditolak',
            'keterangan_admin' => 'nullable|string',
        ]);

        $action->execute($izin, $validated['status'], $validated['keterangan_admin'] ?? null);

        return redirect()->back()->with('success', 'Status permohonan izin berhasil diperbarui.');
    }
}
