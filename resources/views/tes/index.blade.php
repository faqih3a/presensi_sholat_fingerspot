@extends('layouts.app')
@section('title', 'Tes Presensi')
@push('styles')
<style>
.tes-card{border-radius:1rem;border:1px solid #edf2f9;background:#fff;transition:all .3s ease}
.tes-card:hover{box-shadow:0 8px 25px rgba(0,0,0,.08)}
.tes-header{background:linear-gradient(310deg,#198754 0%,#2dc57b 100%);color:#fff;border-radius:1rem 1rem 0 0;padding:1.5rem}
.form-section{padding:1.5rem}
.tes-input{border:1px solid #edf2f9;border-radius:.75rem;padding:.6rem 1rem;font-size:.9rem;transition:all .2s}
.tes-input:focus{border-color:#198754;box-shadow:0 0 0 .2rem rgba(25,135,84,.15)}
.btn-tes{background:linear-gradient(310deg,#198754 0%,#2dc57b 100%);border:none;color:#fff;border-radius:.75rem;padding:.6rem 1.5rem;font-weight:600;transition:all .2s}
.btn-tes:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(25,135,84,.3);color:#fff}
.record-row{border-bottom:1px solid #f0f0f0;padding:.75rem 0;transition:background .2s}
.record-row:hover{background:#f8fdf9}
.record-row:last-child{border-bottom:none}
.empty-state{padding:3rem;text-align:center}
.empty-state i{font-size:3rem;color:#dee2e6}
body.dark-mode .tes-card{background:#1e1e1e;border-color:#333}
body.dark-mode .tes-input{background:#2c2c2c;border-color:#444;color:#f8f9fa}
body.dark-mode .record-row{border-bottom-color:#333}
body.dark-mode .record-row:hover{background:#2c2c2c}
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Tes Presensi</h1>
        <p class="text-muted mb-0">Catat kehadiran diluar waktu sholat</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    {{-- Form Input --}}
    <div class="col-lg-5">
        <div class="tes-card">
            <div class="tes-header">
                <h5 class="fw-bold mb-1"><i class="bi bi-clipboard-plus me-2"></i>Input Presensi Tes</h5>
                <p class="mb-0 small opacity-75">Catat kehadiran santri diluar jadwal sholat</p>
            </div>
            <div class="form-section">
                <form action="{{ route('tes.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Pilih Santri</label>
                        <select name="santri_id" class="form-select tes-input" required>
                            <option value="">-- Pilih Santri --</option>
                            @foreach($santris as $santri)
                            <option value="{{ $santri->id }}">{{ $santri->nama }} ({{ $santri->kelas }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control tes-input" value="{{ $today }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Waktu Hadir</label>
                        <input type="time" name="waktu_hadir" class="form-control tes-input" value="{{ now()->timezone('Asia/Jakarta')->format('H:i') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Keterangan (Opsional)</label>
                        <input type="text" name="keterangan" class="form-control tes-input" placeholder="Contoh: Ujian, Latihan, dll">
                    </div>
                    <button type="submit" class="btn btn-tes w-100"><i class="bi bi-check2-circle me-2"></i>Simpan Presensi</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="col-lg-7">
        <div class="tes-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">Riwayat Presensi Tes</h5>
                    <p class="text-muted small mb-0">Data kehadiran diluar waktu sholat</p>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">{{ $presensis->total() }} Record</span>
            </div>

            {{-- Filter --}}
            <form action="{{ route('tes.index') }}" method="GET" class="d-flex gap-2 mb-3 no-loader">
                <input type="date" name="tanggal" class="form-control form-control-sm tes-input" value="{{ request('tanggal') }}" style="max-width:180px">
                <input type="text" name="search" class="form-control form-control-sm tes-input" placeholder="Cari nama..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-success rounded-3"><i class="bi bi-search"></i></button>
                @if(request('tanggal') || request('search'))
                <a href="{{ route('tes.index') }}" class="btn btn-sm btn-outline-secondary rounded-3"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>

            {{-- Records --}}
            @forelse($presensis as $p)
            <div class="record-row d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    @if($p->santri && $p->santri->foto_referensi)
                    <img src="{{ asset('storage/santri_fotos/'.$p->santri->foto_referensi) }}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover">
                    @else
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px"><i class="bi bi-person-fill"></i></div>
                    @endif
                    <div>
                        <div class="fw-semibold text-dark small">{{ $p->santri->nama ?? 'Unknown' }}</div>
                        <div class="text-muted" style="font-size:.75rem">
                            <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($p->tanggal)->format('d M Y') }}
                            <span class="mx-1">•</span>
                            <i class="bi bi-clock me-1"></i>{{ $p->waktu_hadir }}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success bg-opacity-10 text-success small">Hadir</span>
                    <form action="{{ route('tes.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash3"></i></button>
                    </form>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-clipboard-x d-block mb-3"></i>
                <p class="text-muted mb-0">Belum ada data presensi tes.</p>
            </div>
            @endforelse

            @if($presensis->hasPages())
            <div class="mt-3 d-flex justify-content-center">{{ $presensis->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
