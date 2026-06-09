<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Izin;
use Illuminate\Support\Facades\Storage;

class IzinController extends Controller
{
    public function index()
    {
        $izins = Izin::where('user_id', auth()->id())->latest()->get();
        return view('izin.index', compact('izins'));
    }

    public function create()
    {
        return view('izin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_izin' => 'required|in:Sakit,Izin,Kegiatan Luar',
            'waktu_sholat' => 'nullable|string|in:Full Day,Subuh,Dzuhur,Ashar,Maghrib,Isya',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'required|string',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only(['jenis_izin', 'waktu_sholat', 'tanggal_mulai', 'tanggal_selesai', 'keterangan']);
        $data['user_id'] = auth()->id();

        if ($request->hasFile('lampiran')) {
            $path = $request->file('lampiran')->store('lampiran_izin', 'public');
            $data['lampiran'] = $path;
        }

        $izin = Izin::create($data);

        // Send WhatsApp Notification to Asatidz
        try {
            $asatidz = \App\Models\User::where('role', 'asatidz')
                                      ->whereNotNull('wa_number')
                                      ->get();
            
            if ($asatidz->count() > 0) {
                $message = \App\Services\WhatsAppService::formatIzinNotification($izin);
                foreach ($asatidz as $ustadz) {
                    \App\Services\WhatsAppService::sendMessage($ustadz->wa_number, $message);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send WA notification: ' . $e->getMessage());
        }

        return redirect()->route('izin.index')->with('success', 'Permohonan izin berhasil diajukan.');
    }

    public function manage(Request $request)
    {
        // Allowed for both Admin and Asatidz (pengurus masjid)
        if (!in_array(auth()->user()->role, ['admin', 'asatidz'])) {
            abort(403);
        }

        $resolvedDates = $this->resolveDateRange($request);
        $mode = $resolvedDates['mode'];
        $ref_date = $resolvedDates['ref_date'];
        $tanggal_mulai = $resolvedDates['tanggal_mulai'];
        $tanggal_akhir = $resolvedDates['tanggal_akhir'];

        $refDate = \Carbon\Carbon::parse($ref_date, 'Asia/Jakarta');
        if ($mode === 'week') {
            $prev_date = $refDate->copy()->subWeek()->format('Y-m-d');
            $next_date = $refDate->copy()->addWeek()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } elseif ($mode === 'month') {
            $prev_date = $refDate->copy()->subMonth()->format('Y-m-d');
            $next_date = $refDate->copy()->addMonth()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        } else {
            $prev_date = $refDate->copy()->subDay()->format('Y-m-d');
            $next_date = $refDate->copy()->addDay()->format('Y-m-d');
            $display_date = $this->formatIndonesianDate($tanggal_mulai, 'month');
        }

        $izins = Izin::with('user.santri')
                    ->where(function($query) use ($tanggal_mulai, $tanggal_akhir) {
                        $query->whereBetween('tanggal_mulai', [$tanggal_mulai, $tanggal_akhir])
                              ->orWhereBetween('tanggal_selesai', [$tanggal_mulai, $tanggal_akhir])
                              ->orWhere(function($q) use ($tanggal_mulai, $tanggal_akhir) {
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

    public function updateStatus(Request $request, Izin $izin)
    {
        if (!in_array(auth()->user()->role, ['admin', 'asatidz'])) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:Disetujui,Ditolak',
            'keterangan_admin' => 'nullable|string',
        ]);

        $izin->update([
            'status' => $request->status,
            'keterangan_admin' => $request->keterangan_admin,
        ]);

        return redirect()->back()->with('success', 'Status permohonan izin berhasil diperbarui.');
    }

    private function resolveDateRange(Request $request)
    {
        $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');
        
        if ($request->has('mode') || $request->has('ref_date')) {
            $mode = $request->get('mode', 'day');
            $refDateStr = $request->get('ref_date', $today);
            
            try {
                $refDate = \Carbon\Carbon::parse($refDateStr, 'Asia/Jakarta');
            } catch (\Exception $e) {
                $refDate = \Carbon\Carbon::now('Asia/Jakarta');
            }
            
            if ($mode === 'week') {
                $tanggal_mulai = $refDate->copy()->startOfWeek()->format('Y-m-d');
                $tanggal_akhir = $refDate->copy()->endOfWeek()->format('Y-m-d');
            } elseif ($mode === 'month') {
                $tanggal_mulai = $refDate->copy()->startOfMonth()->format('Y-m-d');
                $tanggal_akhir = $refDate->copy()->endOfMonth()->format('Y-m-d');
            } else {
                $mode = 'day';
                $tanggal_mulai = $refDate->format('Y-m-d');
                $tanggal_akhir = $refDate->format('Y-m-d');
            }
        } else {
            $tanggal_mulai = $request->get('tanggal_mulai', $today);
            $tanggal_akhir = $request->get('tanggal_akhir', $today);
            
            if ($tanggal_mulai === $tanggal_akhir) {
                $mode = 'day';
                $refDateStr = $tanggal_mulai;
            } else {
                $mode = 'day';
                $refDateStr = $tanggal_mulai;
            }
        }
        
        return [
            'mode' => $mode,
            'ref_date' => $refDateStr,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_akhir' => $tanggal_akhir,
        ];
    }

    private function formatIndonesianDate($date, $format = 'day')
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $day = $carbonDate->format('j');
        $month = $months[$carbonDate->month];
        $year = $carbonDate->format('Y');
        
        if ($format === 'month') {
            return "$month $year";
        }
        
        return "$day $month $year";
    }
}
