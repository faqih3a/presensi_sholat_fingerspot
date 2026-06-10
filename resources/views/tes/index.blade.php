@extends('layouts.app')
@section('title', 'Tes Presensi')
@push('styles')
<style>
.btn-gradient-success{background:linear-gradient(310deg,#198754 0%,#2dc57b 100%);border:none;color:#fff;box-shadow:0 4px 7px -1px rgba(0,0,0,.11),0 2px 4px -1px rgba(0,0,0,.07);transition:all .15s ease-in}
.btn-gradient-success:hover{transform:scale(1.02);color:#fff}
.card-stats{border-radius:1rem;border:none;box-shadow:0 .125rem .25rem rgba(0,0,0,.05)}
.table th{font-weight:700;text-transform:uppercase;font-size:.75rem;letter-spacing:.05em;color:#67748e;padding:1rem}
.table td{padding:1rem;color:#67748e;font-size:.875rem}
.badge-soft{font-weight:700;padding:.5rem 1rem;border-radius:2rem;font-size:.75rem}
.badge-soft-success{background-color:rgba(25,135,84,.1);color:#198754;border:1px solid rgba(25,135,84,.2)}
.badge-soft-danger{background-color:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2)}
.badge-soft-info{background-color:rgba(58,176,255,.1);color:#3ab0ff;border:1px solid rgba(58,176,255,.2)}
.badge-soft-warning{background-color:rgba(255,193,7,.1);color:#d4a017;border:1px solid rgba(255,193,7,.2)}
.btn-white{background-color:#fff;color:#67748e;border-color:#edf2f9;box-shadow:0 2px 4px rgba(0,0,0,.02);transition:all .2s}
.btn-white:hover{background-color:#f8f9fa;border-color:#d1d9e6;color:#333}
.live-indicator{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:600;color:#198754;padding:.3rem .75rem;border-radius:2rem;background:rgba(25,135,84,.08);border:1px solid rgba(25,135,84,.15)}
.live-dot{width:8px;height:8px;border-radius:50%;background-color:#198754;animation:livePulse 1.5s ease-in-out infinite}
@keyframes livePulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(25,135,84,.4)}50%{opacity:.6;box-shadow:0 0 0 6px rgba(25,135,84,0)}}
body.dark-mode .table td,body.dark-mode .table th{border-bottom-color:#333}
body.dark-mode .btn-white{background-color:#2c2c2c;border-color:#444;color:#adb5bd}
body.dark-mode .btn-white:hover{background-color:#333;color:#fff}
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Tes Presensi</h1>
        <p class="text-muted mb-0">Data presensi yang diambil diluar rentang waktu sholat.</p>
    </div>
    @include('partials.date-filter')
</div>

<div class="card card-stats mb-4 border-0 {{ $tesEnabled ? 'bg-light' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' }}">
    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle p-3 {{ $tesEnabled ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-20 text-danger' }}">
                <i class="bi {{ $tesEnabled ? 'bi-toggle-on' : 'bi-toggle-off' }} fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1 {{ $tesEnabled ? 'text-dark' : 'text-danger' }}">Pencatatan Presensi Diluar Sholat (Tes)</h5>
                <p class="mb-0 small {{ $tesEnabled ? 'text-muted' : 'text-danger text-opacity-75' }}">
                    @if($tesEnabled)
                        <strong>Status: AKTIF</strong>. Seluruh scan dari mesin di luar waktu sholat resmi akan dicatat sebagai data 'Tes'.
                    @else
                        <strong>Status: NONAKTIF</strong>. Scan di luar waktu sholat akan diabaikan oleh sistem (tidak dimasukkan ke database).
                    @endif
                </p>
            </div>
        </div>
        <form action="{{ route('tes.toggle') }}" method="POST" class="m-0 no-loader">
            @csrf
            <input type="hidden" name="enabled" value="{{ $tesEnabled ? '0' : '1' }}">
            <button type="submit" class="btn {{ $tesEnabled ? 'btn-danger' : 'btn-success' }} px-4 py-2 rounded-3 fw-bold shadow-sm">
                <i class="bi {{ $tesEnabled ? 'bi-power' : 'bi-play-fill' }} me-1"></i>
                {{ $tesEnabled ? 'Nonaktifkan Pencatatan' : 'Aktifkan Pencatatan' }}
            </button>
        </form>
    </div>
</div>

<div class="card card-stats mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clipboard-data text-success me-2"></i>Data Presensi Tes</h6>
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span>LIVE</span>
            </div>
        </div>
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
            <form id="filterForm" action="{{ route('tes.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-3 m-0 no-loader">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="ref_date" value="{{ $ref_date }}">

                <x-filter-dropdown
                    label="Status"
                    name="status"
                    selected="{{ request('status') }}"
                    :options="[
                        '' => 'Semua Status',
                        'Tes' => 'Tes',
                        'Hadir' => 'Hadir',
                        'Alfa' => 'Alpha',
                    ]"
                    form-id="filterForm"
                    button-style="border-radius: 0.75rem; min-width: 120px; background: #fff;"
                />
            </form>
            <a href="{{ route('tes.export', request()->query()) }}" class="btn btn-gradient-success btn-sm px-3 fw-bold" data-no-loader="true">
                <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="bg-light">
                    <tr>
                        <th>Nama Santri</th>
                        <th class="text-center" width="100">Foto Scan</th>
                        <th>Kelas</th>
                        <th>Keterangan</th>
                        <th>Waktu Presensi</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presensis as $presensi)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $presensi->santri->nama ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            @if($presensi->photo_url)
                                <a href="{{ $presensi->photo_url }}" target="_blank" title="Buka foto asli">
                                    <img src="{{ $presensi->photo_url }}" alt="Scan" class="rounded border shadow-sm" style="width:45px;height:45px;object-fit:cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div class="small text-muted" style="display:none"><i class="bi bi-image"></i> Lihat</div>
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>{{ $presensi->santri->kelas ?? '-' }}</td>
                        <td>
                            <span class="badge badge-soft badge-soft-warning">
                                <i class="bi bi-clock-history me-1 small"></i> Tes (Diluar Sholat)
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($presensi->waktu_hadir)
                                    <div class="fw-bold text-dark me-2">{{ \Carbon\Carbon::parse($presensi->waktu_hadir)->format('H:i') }}</div>
                                @else
                                    <div class="fw-bold text-danger me-2">-</div>
                                @endif
                                <div class="small text-muted border-start ps-2">{{ \Carbon\Carbon::parse($presensi->tanggal)->format('d M Y') }}</div>
                            </div>
                        </td>
                        <td class="text-center">
                            @if($presensi->status == 'Alfa')
                                <span class="badge badge-soft badge-soft-danger px-4">Alpha</span>
                            @elseif($presensi->status == 'Tes')
                                <span class="badge badge-soft badge-soft-warning px-4">Tes</span>
                            @else
                                <span class="badge badge-soft badge-soft-success px-4">Hadir</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <form action="{{ route('tes.destroy', $presensi->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?')" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Hapus">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                <h6 class="fw-bold">Belum Ada Data Presensi Tes</h6>
                                <p class="small mb-0">Data presensi diluar waktu sholat akan muncul di sini setelah santri melakukan scan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(count($presensis) > 0)
    <div class="card-footer bg-white border-top py-3 text-center text-md-start">
        <div class="small text-muted">
            Menampilkan {{ count($presensis) }} data presensi tes.
        </div>
    </div>
    @endif
</div>
@endsection
