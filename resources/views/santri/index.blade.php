@extends('layouts.app')

@section('title', 'Data Santri')

@push('styles')
<style>
    .card-stats {
        border-radius: 8px;
        border: 1px solid var(--color-border);
        background: var(--color-surface);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-stats:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(30,29,27,0.06);
    }
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        color: var(--color-muted);
        padding: 0.85rem 1rem;
    }
    .table td {
        padding: 0.9rem 1rem;
        color: var(--color-text);
        font-size: 0.875rem;
    }
    .avatar-sm {
        width: 48px;
        height: 48px;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, 0.2);
    }
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    /* Registration Modal Styles */
    .preview-container {
        max-width: 100%;
        margin: 0 auto;
        position: relative;
        border-radius: 0.75rem;
        overflow: hidden;
        background: #f8f9fa;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #image-preview {
        width: 100%;
        display: none;
        border-radius: 0.75rem;
    }
    .preview-container canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    body.dark-mode .table td, body.dark-mode .table th {
        border-bottom-color: #333;
    }
    body.dark-mode .table-light {
        background-color: #2c2c2c !important;
    }
    body.dark-mode .avatar-sm {
        border-color: #444;
    }
    body.dark-mode .preview-container {
        background: #2c2c2c;
    }

    /* Edit modal styles */
    .edit-preview-container {
        max-width: 100%;
        margin: 0 auto;
        position: relative;
        border-radius: 0.75rem;
        overflow: hidden;
        background: #f8f9fa;
        min-height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #dee2e6;
    }
    .edit-preview-container img {
        width: 100%;
        border-radius: 0.75rem;
    }
    body.dark-mode .edit-preview-container {
        background: #2c2c2c;
        border-color: #444;
    }

    /* ─── Sync Overlay ────────────────────────────────────── */
    .sync-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        animation: syncFadeIn 0.3s ease;
    }
    .sync-overlay.active { display: flex; }

    @keyframes syncFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .sync-card {
        background: #fff;
        border-radius: 1.25rem;
        padding: 2rem 2.5rem;
        max-width: 480px;
        width: 90%;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        text-align: center;
        animation: syncSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes syncSlideUp {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .sync-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }
    .sync-icon.spinning i {
        animation: syncSpin 1.2s linear infinite;
    }
    @keyframes syncSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .sync-progress-track {
        height: 8px;
        background: #e9ecef;
        border-radius: 999px;
        overflow: hidden;
        margin: 1.25rem 0;
    }
    .sync-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #198754, #2dc57b, #198754);
        background-size: 200% 100%;
        border-radius: 999px;
        transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        animation: syncShimmer 2s ease infinite;
    }
    @keyframes syncShimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .sync-phase {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 1rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
    }
    .sync-phase.sending { background: rgba(13,110,253,0.1); color: #0d6efd; }
    .sync-phase.waiting { background: rgba(255,193,7,0.15); color: #997404; }
    .sync-phase.finalizing { background: rgba(25,135,84,0.1); color: #198754; }
    .sync-phase.done { background: rgba(25,135,84,0.15); color: #198754; }
    .sync-phase.error { background: rgba(220,53,69,0.1); color: #dc3545; }

    .sync-stats {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    .sync-stat {
        text-align: center;
    }
    .sync-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1;
    }
    .sync-stat-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #67748e;
        font-weight: 600;
        margin-top: 0.25rem;
    }

    body.dark-mode .sync-card {
        background: #1e1e1e;
        color: #f8f9fa;
    }
    body.dark-mode .sync-progress-track {
        background: #333;
    }
    body.dark-mode .sync-stat-label {
        color: #adb5bd;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold">Data Santri</h1>
            <p class="text-muted small mb-0">Kelola daftar santri yang terdaftar dalam sistem presensi wajah.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" id="btn-sync-mesin" class="btn btn-light border px-4 py-2 fw-medium" onclick="syncMesin()">
                <i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Mesin
            </button>
            <button type="button" class="btn btn-solid px-4 py-2" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="bi bi-person-plus-fill me-2"></i> Tambah Santri
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 0.75rem;">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 0.75rem;">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>{{ session('error') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card card-stats p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.06em;">Total Santri</span>
                    <span class="h3 fw-bold mb-0" style="color: var(--color-text);">
                        {{ $totalSantri ?? (method_exists($santris, 'total') ? $santris->total() : count($santris)) }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; border-radius: 8px; background: rgba(42,107,79,0.08); color: var(--color-accent);">
                    <i class="bi bi-people-fill" style="font-size: 1.1rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-stats overflow-hidden">
        <div class="card-header py-3 px-4 border-bottom d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"
             style="background: var(--color-surface); border-color: var(--color-border) !important;">
            <div>
                <h6 class="m-0 fw-bold" style="color: var(--color-text); font-family: var(--font-display);">
                    <i class="bi bi-people-fill me-2" style="color: var(--color-accent);"></i>Daftar Santri
                </h6>
                <div class="small mt-1" style="color: var(--color-muted); font-size: 0.78rem;">
                    @if(request('search') || request('kelas'))
                        {{ method_exists($santris, 'total') ? $santris->total() : count($santris) }} dari {{ $totalSantri ?? '' }} Santri (terfilter)
                    @else
                        {{ method_exists($santris, 'total') ? $santris->total() : count($santris) }} Santri Terdaftar
                    @endif
                </div>
            </div>

            <form action="{{ route('santri.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto" id="filter-kelas-form">
                <!-- Dropdown Filter Kelas -->
                <div class="premium-select-wrapper" style="min-width: 150px;">
                    <button class="premium-select-btn dropdown-toggle" type="button" id="filterKelasDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 36px; font-size: 0.82rem;">
                        <span id="selected-filter-kelas-text">{{ request('kelas') ?: 'Semua Kelas' }}</span>
                        <i class="bi bi-chevron-down" style="font-size: 0.7rem; color: var(--color-muted);"></i>
                    </button>
                    <ul class="dropdown-menu shadow border-0" aria-labelledby="filterKelasDropdown" style="width: 100%;">
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('')">Semua Kelas</a></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == 'Belum Diatur' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('Belum Diatur')"><i class="bi bi-exclamation-circle text-warning me-1"></i> Belum Diatur</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '7 MTs' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('7 MTs')">7 MTs</a></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '8 MTs' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('8 MTs')">8 MTs</a></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '9 MTs' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('9 MTs')">9 MTs</a></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '10 MA' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('10 MA')">10 MA</a></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '11 MA' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('11 MA')">11 MA</a></li>
                        <li><a class="dropdown-item py-2 {{ request('kelas') == '12 MA' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectFilterKelas('12 MA')">12 MA</a></li>
                    </ul>
                    <input type="hidden" name="kelas" id="filter_kelas_input" value="{{ request('kelas') }}">
                </div>

                <!-- Input Search -->
                <div class="d-flex align-items-center gap-1" style="min-width: 220px; max-width: 260px;">
                    <div class="input-group" style="height: 36px;">
                        <span class="input-group-text" style="background: var(--color-surface); border: 1px solid var(--color-border); border-right: none; border-radius: 6px 0 0 6px; color: var(--color-muted); padding: 0 0.6rem;">
                            <i class="bi bi-search" style="font-size: 0.8rem;"></i>
                        </span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama santri..."
                               value="{{ request('search') }}"
                               style="border-left: none; border-radius: 0 6px 6px 0; font-size: 0.82rem; height: 36px; border-color: var(--color-border);">
                        <button type="submit" style="display:none;"></button>
                    </div>
                    @if(request('search') || request('kelas'))
                        <a href="{{ route('santri.index') }}"
                           class="d-flex align-items-center justify-content-center flex-shrink-0"
                           style="width: 36px; height: 36px; border: 1px solid var(--color-border); border-radius: 6px; color: var(--color-muted); text-decoration: none; background: var(--color-surface); transition: all 0.15s ease;"
                           title="Reset Filter"
                           onmouseover="this.style.background='var(--color-accent-light)'; this.style.color='var(--color-accent)'"
                           onmouseout="this.style.background='var(--color-surface)'; this.style.color='var(--color-muted)'">
                            <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" width="80">No</th>
                            <th>Foto</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas</th>
                            <th>Biometrik</th>
                            <th>Waktu Daftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($santris as $index => $santri)
                        <tr>
                            <td class="text-center">
                                <span class="text-muted fw-bold small">{{ method_exists($santris, 'currentPage') ? ($santris->currentPage() - 1) * $santris->perPage() + $loop->iteration : $loop->iteration }}</span>
                            </td>
                            <td>
                                @if($santri->display_photo)
                                    <a href="{{ $santri->display_photo }}" target="_blank" title="Lihat Foto Penuh">
                                        <img src="{{ $santri->display_photo }}" alt="{{ $santri->nama }}" class="avatar-sm rounded-circle object-fit-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="avatar-sm bg-light text-secondary rounded-circle align-items-center justify-content-center border" style="display: none;">
                                            <i class="bi bi-person fs-5"></i>
                                        </div>
                                    </a>
                                @else
                                    <div class="avatar-sm bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center border">
                                        <i class="bi bi-person fs-5"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $santri->nama }}</div>
                                <div class="small text-muted">{{ $santri->user->email ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-soft-success px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    {{ $santri->kelas }}
                                </span>
                            </td>
                            <td>
                                @if($santri->face_count > 0 || $santri->finger_count > 0)
                                    <div class="d-flex gap-1 flex-wrap">
                                        @if($santri->face_count > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" title="Wajah Tersimpan">
                                                <i class="bi bi-person-bounding-box me-1"></i> Wajah
                                            </span>
                                        @endif
                                        @if($santri->finger_count > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" title="Jari Tersimpan">
                                                <i class="bi bi-fingerprint me-1"></i> Jari ({{ $santri->finger_count }})
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                        <i class="bi bi-x-circle me-1"></i> Belum Rekam
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark small">{{ $santri->created_at->format('d M Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $santri->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="action-btn bg-info bg-opacity-10 text-info border-0 btn-edit-santri" title="Edit"
                                        data-id="{{ $santri->id }}"
                                        data-nama="{{ $santri->nama }}"
                                        data-kelas="{{ $santri->kelas }}"
                                        data-foto="{{ $santri->display_photo }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('santri.destroy', $santri) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data santri ini? Semua data presensi terkait juga akan dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn bg-danger bg-opacity-10 text-danger border-0" title="Hapus">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-person-exclamation text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="mt-3 fw-bold text-dark">Belum Ada Data</h5>
                                    <p class="text-muted mb-4">Silakan daftarkan santri baru untuk mulai menggunakan sistem presensi.</p>
                                    <button type="button" class="btn btn-gradient-success px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#registerModal">
                                        <i class="bi bi-plus-lg me-1"></i> Daftarkan Santri Pertama
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(count($santris) > 0)
        <div class="card-footer border-top py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3"
             style="background: var(--color-surface); border-color: var(--color-border) !important;">
            <div class="small" style="color: var(--color-muted);">
                @if(method_exists($santris, 'firstItem'))
                    Menampilkan <strong style="color: var(--color-text);">{{ $santris->firstItem() }}</strong>
                    &ndash;
                    <strong style="color: var(--color-text);">{{ $santris->lastItem() }}</strong>
                    dari <strong style="color: var(--color-text);">{{ $santris->total() }}</strong> santri
                    @if(request('search') || request('kelas'))
                        <span class="fw-semibold">(terfilter)</span>
                    @endif
                @else
                    Menampilkan <strong style="color: var(--color-text);">{{ count($santris) }}</strong> santri
                @endif
            </div>
            @if(method_exists($santris, 'links'))
                <div>
                    {{ $santris->links() }}
                </div>
            @endif
        </div>
        @endif
    </div>
</div>

<!-- Modal Registrasi Santri -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                <div class="text-center w-100">
                    <h4 class="fw-bold text-dark mb-1" id="registerModalLabel">
                        <i class="bi bi-person-plus-fill me-2 text-success"></i>Registrasi Santri Baru
                    </h4>
                    <p class="text-muted small">Lengkapi data di bawah untuk mendaftarkan santri ke sistem.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modal-alert-container"></div>
                
                <form id="register-form" action="{{ route('santri.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama" class="form-label fw-bold small text-muted text-uppercase">Nama Lengkap</label>
                                <input type="text" class="form-control py-2" id="nama" name="nama" required placeholder="Ahmad Al-Faqih">
                            </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold small text-muted text-uppercase">Kelas</label>
                                 <div class="premium-select-wrapper">
                                     <button class="premium-select-btn dropdown-toggle py-2" type="button" id="kelasDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                         <span id="selected-kelas-text">-- Pilih Kelas --</span>
                                         <i class="bi bi-chevron-down small text-muted"></i>
                                     </button>
                                     <ul class="dropdown-menu shadow border-0" aria-labelledby="kelasDropdown">
                                         <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectKelas('7 MTs')">7 MTs</a></li>
                                         <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectKelas('8 MTs')">8 MTs</a></li>
                                         <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectKelas('9 MTs')">9 MTs</a></li>
                                         <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectKelas('10 MA')">10 MA</a></li>
                                         <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectKelas('11 MA')">11 MA</a></li>
                                         <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectKelas('12 MA')">12 MA</a></li>
                                     </ul>
                                     <input type="hidden" name="kelas" id="kelas_input" required>
                                 </div>
                             </div>

                             <script>
                                 function selectKelas(val) {
                                     document.getElementById('kelas_input').value = val;
                                     document.getElementById('selected-kelas-text').innerText = val;
                                     
                                     // Update active state
                                     const items = document.querySelectorAll('#kelasDropdown + .dropdown-menu .dropdown-item');
                                     items.forEach(item => {
                                         if (item.innerText === val) {
                                             item.classList.add('active');
                                         } else {
                                             item.classList.remove('active');
                                         }
                                     });
                                 }
                             </script>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold small text-muted text-uppercase">Email</label>
                                <input type="email" class="form-control py-2" id="email" name="email" required placeholder="santri@thursina.id">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold small text-muted text-uppercase">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control py-2" id="password" name="password" required minlength="5">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="foto_referensi" class="form-label fw-bold small text-muted text-uppercase">Foto Profil</label>
                                <input type="file" class="form-control py-2" id="foto_referensi" name="foto_referensi" accept="image/jpeg, image/png, image/jpg" required>
                            </div>
                            <div class="preview-container mb-2" id="preview-wrapper">
                                <img id="image-preview" src="#" alt="Preview" />
                                <div id="preview-placeholder" class="text-center text-muted">
                                    <i class="bi bi-camera fs-1 d-block mb-2"></i>
                                    <span class="small">Belum ada foto dipilih</span>
                                </div>
                            </div>
                            <div id="extraction-status" class="text-center mt-2 small d-none"></div>
                        </div>
                    </div>



                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" id="submit-btn" class="btn btn-solid flex-grow-1 py-2" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i>Simpan Data Santri
                        </button>
                        <button type="button" class="btn btn-light px-4 py-2 fw-medium text-muted" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Sync Progress Overlay -->
<div class="sync-overlay" id="syncOverlay">
    <div class="sync-card">
        <div class="sync-icon spinning" id="syncIcon" style="background: rgba(25,135,84,0.1); color: #198754;">
            <i class="bi bi-arrow-repeat"></i>
        </div>
        <h5 class="fw-bold mb-1" id="syncTitle">Sinkronisasi Mesin</h5>
        <div class="sync-phase sending" id="syncPhase">
            <i class="bi bi-broadcast"></i> <span id="syncPhaseText">Mengirim perintah...</span>
        </div>
        <p class="text-muted small mb-0" id="syncMessage">Memulai proses sinkronisasi...</p>

        <div class="sync-progress-track">
            <div class="sync-progress-bar" id="syncProgressBar" style="width: 0%;"></div>
        </div>
        <div class="small text-muted fw-bold" id="syncProgressText">0 / 150</div>

        <div class="sync-stats" id="syncStats" style="display: none;">
            <div class="sync-stat">
                <div class="sync-stat-value text-success" id="statSuccess">0</div>
                <div class="sync-stat-label">Berhasil</div>
            </div>
            <div class="sync-stat">
                <div class="sync-stat-value text-danger" id="statFailed">0</div>
                <div class="sync-stat-label">Gagal</div>
            </div>
            <div class="sync-stat">
                <div class="sync-stat-value text-primary" id="statTotal">0</div>
                <div class="sync-stat-label">Total DB</div>
            </div>
        </div>

        <div class="mt-3" id="syncActions">
            <button type="button" class="btn btn-sm btn-outline-danger px-4 rounded-pill" id="syncCancelBtn" onclick="cancelSync()">
                <i class="bi bi-x-lg me-1"></i> Batalkan
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit Santri (Single Reusable) -->
<div class="modal fade" id="editSantriModal" tabindex="-1" aria-labelledby="editSantriModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                <div class="text-center w-100">
                    <h4 class="fw-bold text-dark mb-1" id="editSantriModalLabel">
                        <i class="bi bi-pencil-square text-info me-2"></i>Edit Profil Santri
                    </h4>
                    <p class="text-muted small">Perbarui informasi dasar dan foto profil santri.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editSantriForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nama Lengkap</label>
                                <input type="text" name="nama" id="edit_nama" class="form-control py-2" required placeholder="Masukkan nama lengkap">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Kelas</label>
                                <div class="premium-select-wrapper">
                                    <button class="premium-select-btn dropdown-toggle py-2" type="button" id="editKelasDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 40px;">
                                        <span id="edit-selected-kelas-text">-- Pilih Kelas --</span>
                                        <i class="bi bi-chevron-down small text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow border-0" aria-labelledby="editKelasDropdown" style="width: 100%;">
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectEditKelas('7 MTs')">7 MTs</a></li>
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectEditKelas('8 MTs')">8 MTs</a></li>
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectEditKelas('9 MTs')">9 MTs</a></li>
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectEditKelas('10 MA')">10 MA</a></li>
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectEditKelas('11 MA')">11 MA</a></li>
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="selectEditKelas('12 MA')">12 MA</a></li>
                                    </ul>
                                    <input type="hidden" name="kelas" id="edit_kelas_input" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Ganti Foto Profil (Opsional)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-camera text-muted"></i></span>
                                    <input type="file" class="form-control border-start-0" id="edit_foto_referensi" name="foto_referensi" accept="image/jpeg, image/png, image/jpg">
                                </div>
                                <div class="form-text small mt-2">Kosongkan jika tidak ingin mengubah foto profil.</div>
                            </div>
                            <div class="mb-3">
                                <div class="edit-preview-container shadow-sm">
                                    <img id="edit-foto-preview" src="#" alt="Preview" style="display: none;" />
                                    <div id="edit-no-photo" class="text-center text-muted small">Belum ada foto profil</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-solid flex-grow-1 py-2">
                            <i class="bi bi-check-circle-fill me-2"></i>Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-light px-4 py-2 fw-medium text-muted" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const fotoInput = document.getElementById('foto_referensi');
    const imagePreview = document.getElementById('image-preview');
    const previewPlaceholder = document.getElementById('preview-placeholder');
    const extractionStatus = document.getElementById('extraction-status');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('register-form');
    const alertContainer = document.getElementById('modal-alert-container');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const registerModal = document.getElementById('registerModal');

    // Reset Modal on Close
    registerModal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        imagePreview.style.display = 'none';
        imagePreview.src = '#';
        previewPlaceholder.classList.remove('d-none');
        extractionStatus.classList.add('d-none');
        extractionStatus.innerHTML = '';
        submitBtn.disabled = true;
        alertContainer.innerHTML = '';
        passwordInput.setAttribute('type', 'password');
        togglePassword.innerHTML = '<i class="bi bi-eye"></i>';
    });

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });

    fotoInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) {
            submitBtn.disabled = true;
            return;
        }

        // Show image preview
        const imgUrl = URL.createObjectURL(file);
        imagePreview.src = imgUrl;
        imagePreview.style.display = 'block';
        previewPlaceholder.classList.add('d-none');
        
        extractionStatus.classList.remove('d-none');
        extractionStatus.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Foto profil terpilih!';
        extractionStatus.className = 'text-success mt-2 small fw-bold';
        submitBtn.disabled = false;
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (submitBtn.disabled) return;

        const formData = new FormData(form);
        submitBtn.disabled = true;
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Menyimpan...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                location.reload(); 
            } else {
                let errorMsg = result.message || 'Gagal menyimpan data.';
                if (result.errors) {
                    errorMsg += '<ul class="mb-0 mt-2">';
                    for (const key in result.errors) {
                        errorMsg += `<li>${result.errors[key][0]}</li>`;
                    }
                    errorMsg += '</ul>';
                }
                showAlert('danger', errorMsg);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        } catch (error) {
            console.error(error);
            showAlert('danger', 'Terjadi kesalahan sistem.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    function showAlert(type, message) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show border-0" role="alert" style="border-radius: 0.75rem;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    // ─── Sinkronisasi Mesin (Async + Progress Polling) ──────────────────
    // ─── Sinkronisasi Mesin (Smart Sync) ────────────────────────────────
    // Elemen-elemen overlay
    const syncOverlay     = document.getElementById('syncOverlay');
    const syncIcon        = document.getElementById('syncIcon');
    const syncTitle       = document.getElementById('syncTitle');
    const syncPhase       = document.getElementById('syncPhase');
    const syncPhaseText   = document.getElementById('syncPhaseText');
    const syncMessage     = document.getElementById('syncMessage');
    const syncProgressBar = document.getElementById('syncProgressBar');
    const syncProgressText = document.getElementById('syncProgressText');
    const syncStats       = document.getElementById('syncStats');
    const syncActions     = document.getElementById('syncActions');
    const syncCancelBtn   = document.getElementById('syncCancelBtn');

    async function syncMesin() {
        const btn = document.getElementById('btn-sync-mesin');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

        // Tampilkan overlay
        showSyncOverlay();

        try {
            const response = await fetch('{{ route("santri.sync-mesin") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();

            if (result.success) {
                // Selesai
                syncProgressBar.style.width = '100%';
                syncProgressText.textContent = `${result.count} PIN`;
                onSyncComplete(result);
            } else {
                showSyncError(result.message || 'Gagal sinkronisasi.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Mesin';
            }
        } catch (error) {
            console.error('Sync error:', error);
            showSyncError('Terjadi kesalahan jaringan saat sinkronisasi.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Mesin';
        }
    }

    function showSyncOverlay() {
        // Reset ke state awal
        syncIcon.className = 'sync-icon spinning';
        syncIcon.style.background = 'rgba(25,135,84,0.1)';
        syncIcon.style.color = '#198754';
        syncIcon.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
        syncTitle.textContent = 'Sinkronisasi Mesin';
        syncPhase.className = 'sync-phase sending';
        syncPhaseText.textContent = 'Menghubungkan...';
        syncMessage.textContent = 'Menarik daftar PIN aktif dan mengirimkan perintah sinkronisasi ke mesin absensi...';
        syncProgressBar.style.width = '40%';
        syncProgressText.textContent = 'Menghubungkan';
        syncStats.style.display = 'none';
        if (syncCancelBtn) syncCancelBtn.style.display = 'none';
        syncActions.innerHTML = '';
        syncOverlay.classList.add('active');
    }

    function onSyncComplete(result) {
        // Ubah ikon jadi centang
        syncIcon.className = 'sync-icon';
        syncIcon.style.background = 'rgba(25,135,84,0.15)';
        syncIcon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
        syncTitle.textContent = 'Sinkronisasi Berhasil!';
        syncPhase.className = 'sync-phase done';
        syncPhaseText.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Selesai';
        syncMessage.textContent = result.message;

        // Ganti tombol jadi tombol tutup
        syncActions.innerHTML = `
            <button type="button" class="btn btn-sm btn-gradient-success px-4 rounded-pill fw-bold" onclick="closeSyncOverlay()">
                <i class="bi bi-check-lg me-1"></i> Selesai
            </button>
        `;

        // Refresh tabel data di background
        refreshSantriTable();

        // Reset tombol sync
        const btn = document.getElementById('btn-sync-mesin');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Mesin';
    }

    function showSyncError(message) {
        syncIcon.className = 'sync-icon';
        syncIcon.style.background = 'rgba(220,53,69,0.1)';
        syncIcon.style.color = '#dc3545';
        syncIcon.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
        syncTitle.textContent = 'Gagal Sinkronisasi';
        syncPhase.className = 'sync-phase error';
        syncPhaseText.innerHTML = '<i class="bi bi-x-circle me-1"></i> Error';
        syncMessage.textContent = message;
        syncProgressBar.style.width = '0%';
        syncProgressText.textContent = 'Gagal';

        syncActions.innerHTML = `
            <button type="button" class="btn btn-sm btn-light px-4 rounded-pill fw-bold" onclick="closeSyncOverlay()">
                Tutup
            </button>
        `;
    }

    function closeSyncOverlay() {
        syncOverlay.classList.remove('active');
    }

    // ─── AJAX Table Refresh ─────────────────────────────────────────────
    async function refreshSantriTable() {
        try {
            // Ambil parameter filter yang sedang aktif
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search') || '';
            const kelas = urlParams.get('kelas') || '';
            const page = urlParams.get('page') || '1';

            const apiUrl = `{{ route('santri.api.list') }}?search=${encodeURIComponent(search)}&kelas=${encodeURIComponent(kelas)}&page=${page}`;
            const resp = await fetch(apiUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await resp.json();

            if (!result.success) return;

            // Update stat cards
            document.querySelectorAll('.card-stats .h3').forEach(el => {
                el.textContent = result.total_santri;
            });
            const subCount = document.querySelector('.card-header .small.text-muted');
            if (subCount) {
                subCount.textContent = result.pagination.total + ' Santri Terdaftar';
            }

            // Re-render table body
            const tbody = document.querySelector('table.table tbody');
            if (!tbody) return;

            if (result.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="py-4">
                                <i class="bi bi-person-exclamation text-muted" style="font-size: 4rem;"></i>
                                <h5 class="mt-3 fw-bold text-dark">Belum Ada Data</h5>
                                <p class="text-muted mb-4">Silakan daftarkan santri baru untuk mulai menggunakan sistem presensi.</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }

            const startNum = result.pagination.first_item || 1;
            tbody.innerHTML = result.data.map((s, i) => {
                const num = startNum + i;
                const fotoHtml = s.foto
                    ? `<a href="${s.foto}" target="_blank" title="Lihat Foto Penuh">
                         <img src="${s.foto}" alt="${s.nama}" class="avatar-sm rounded-circle object-fit-cover"
                              onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                         <div class="avatar-sm bg-light text-secondary rounded-circle align-items-center justify-content-center border" style="display: none;">
                             <i class="bi bi-person fs-5"></i>
                         </div>
                       </a>`
                    : `<div class="avatar-sm bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center border">
                           <i class="bi bi-person fs-5"></i>
                       </div>`;

                let bioHtml = '';
                if (s.face_count > 0 || s.finger_count > 0) {
                    bioHtml = '<div class="d-flex gap-1 flex-wrap">';
                    if (s.face_count > 0) {
                        bioHtml += '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" title="Wajah Tersimpan"><i class="bi bi-person-bounding-box me-1"></i> Wajah</span>';
                    }
                    if (s.finger_count > 0) {
                        bioHtml += `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" title="Jari Tersimpan"><i class="bi bi-fingerprint me-1"></i> Jari (${s.finger_count})</span>`;
                    }
                    bioHtml += '</div>';
                } else {
                    bioHtml = '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><i class="bi bi-x-circle me-1"></i> Belum Rekam</span>';
                }

                return `<tr>
                    <td class="text-center"><span class="text-muted fw-bold small">${num}</span></td>
                    <td>${fotoHtml}</td>
                    <td>
                        <div class="fw-bold text-dark">${s.nama}</div>
                        <div class="small text-muted">${s.email}</div>
                    </td>
                    <td><span class="badge badge-soft-success px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">${s.kelas}</span></td>
                    <td>${bioHtml}</td>
                    <td>
                        <div class="text-dark small">${s.created_at}</div>
                        <div class="text-muted small" style="font-size: 0.75rem;">${s.created_time} WIB</div>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            <a href="${s.edit_url}" class="action-btn bg-info bg-opacity-10 text-info" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="${s.delete_url}" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data santri ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn bg-danger bg-opacity-10 text-danger border-0" title="Hapus">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>`;
            }).join('');

            // Update footer pagination text
            const footerText = document.querySelector('.card-footer .small.text-muted');
            if (footerText && result.pagination) {
                const p = result.pagination;
                footerText.textContent = `Menampilkan ${p.first_item} sampai ${p.last_item} dari ${p.total} data santri.`;
            }

        } catch (error) {
            console.error('Table refresh error:', error);
        }
    }

    function selectFilterKelas(val) {
        document.getElementById('filter_kelas_input').value = val;
        document.getElementById('selected-filter-kelas-text').innerText = val ? val : 'Semua Kelas';
        document.getElementById('filter-kelas-form').submit();
    }

    // ─── Edit Modal: Kelas Dropdown ──────────────────────────────────────
    function selectEditKelas(val) {
        document.getElementById('edit_kelas_input').value = val;
        document.getElementById('edit-selected-kelas-text').innerText = val;
        
        const items = document.querySelectorAll('#editKelasDropdown + .dropdown-menu .dropdown-item');
        items.forEach(item => {
            if (item.innerText === val) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // ─── Edit Modal: Populate & Open ────────────────────────────────────
    const editSantriForm = document.getElementById('editSantriForm');
    const editSantriModal = document.getElementById('editSantriModal');
    const editFotoInput = document.getElementById('edit_foto_referensi');
    const editFotoPreview = document.getElementById('edit-foto-preview');
    const editNoPhoto = document.getElementById('edit-no-photo');

    document.querySelectorAll('.btn-edit-santri').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const kelas = this.dataset.kelas;
            const foto = this.dataset.foto;

            // Set form action dynamically
            editSantriForm.action = `/santri/${id}`;

            // Populate fields
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kelas_input').value = kelas;
            document.getElementById('edit-selected-kelas-text').innerText = kelas || '-- Pilih Kelas --';

            // Update active state in kelas dropdown
            const kelasItems = document.querySelectorAll('#editKelasDropdown + .dropdown-menu .dropdown-item');
            kelasItems.forEach(item => {
                if (item.innerText === kelas) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });

            // Reset file input
            editFotoInput.value = '';

            // Show current photo or placeholder
            if (foto) {
                editFotoPreview.src = foto;
                editFotoPreview.style.display = 'block';
                editNoPhoto.style.display = 'none';
            } else {
                editFotoPreview.style.display = 'none';
                editNoPhoto.style.display = 'block';
            }

            // Open the modal
            const bsModal = new bootstrap.Modal(editSantriModal);
            bsModal.show();
        });
    });

    // ─── Edit Modal: Image Preview ───────────────────────────────────────
    if (editFotoInput) {
        editFotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const imgUrl = URL.createObjectURL(file);
            editFotoPreview.src = imgUrl;
            editFotoPreview.style.display = 'block';
            editNoPhoto.style.display = 'none';
        });
    }

    // ─── Edit Modal: Reset on close ──────────────────────────────────────
    if (editSantriModal) {
        editSantriModal.addEventListener('hidden.bs.modal', function() {
            editFotoInput.value = '';
        });
    }

    // No ongoing sync check needed as sync is synchronous now
</script>
@endpush
