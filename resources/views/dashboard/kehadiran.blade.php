@extends('layouts.app')

@section('title', 'Kehadiran Sholat')

@push('styles')
<style>
    .btn-gradient-success {
        background: linear-gradient(310deg, #198754 0%, #2dc57b 100%);
        border: none;
        color: #fff;
        box-shadow: 0 4px 7px -1px rgba(0,0,0,0.11), 0 2px 4px -1px rgba(0,0,0,0.07);
        transition: all 0.15s ease-in;
    }
    .btn-gradient-success:hover {
        transform: scale(1.02);
        color: #fff;
    }
    .card-stats {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
    }
    .table th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: #67748e;
        padding: 1rem;
    }
    .table td {
        padding: 1rem;
        color: #67748e;
        font-size: 0.875rem;
    }
    .badge-soft {
        font-weight: 700;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.75rem;
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, 0.2);
    }
    .badge-soft-danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    .badge-soft-info {
        background-color: rgba(58, 176, 255, 0.1);
        color: #3ab0ff;
        border: 1px solid rgba(58, 176, 255, 0.2);
    }
    .filter-select {
        background-color: #f8f9fa;
        border: 1px solid #edf2f9;
        border-radius: 0.5rem;
        padding: 0.4rem 2rem 0.4rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4d5157;
    }
    .filter-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }
    
    .btn-white {
        background-color: #fff;
        color: #67748e;
        border-color: #edf2f9;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.2s;
    }
    .btn-white:hover {
        background-color: #f8f9fa;
        border-color: #d1d9e6;
        color: #333;
    }
    
    body.dark-mode .table td, body.dark-mode .table th {
        border-bottom-color: #333;
    }
    body.dark-mode .btn-white {
        background-color: #2c2c2c;
        border-color: #444;
        color: #adb5bd;
    }
    body.dark-mode .btn-white:hover {
        background-color: #333;
        color: #fff;
    }
    body.dark-mode .filter-select {
        background-color: #2c2c2c;
        border-color: #444;
        color: #adb5bd;
    }

    /* ─── Live Scan Indicator ─────────────────────────────── */
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #198754;
        padding: 0.3rem 0.75rem;
        border-radius: 2rem;
        background: rgba(25, 135, 84, 0.08);
        border: 1px solid rgba(25, 135, 84, 0.15);
    }
    .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #198754;
        animation: livePulse 1.5s ease-in-out infinite;
    }
    @keyframes livePulse {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
        50% { opacity: 0.6; box-shadow: 0 0 0 6px rgba(25, 135, 84, 0); }
    }



    /* ─── New row highlight animation ────────────────────── */
    .row-new-entry {
        animation: rowHighlight 2s ease-out;
    }
    @keyframes rowHighlight {
        0% { background-color: rgba(25, 135, 84, 0.15); }
        100% { background-color: transparent; }
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Rekap Kehadiran Sholat</h1>
        <p class="text-muted mb-0">Pantau detail kehadiran sholat berjamaah santri secara keseluruhan.</p>
    </div>
    @include('partials.date-filter')
</div>

<div class="card card-stats mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-table text-success me-2"></i>Data Rekap Kehadiran</h6>
            <div class="live-indicator" id="liveIndicator" title="Auto-refresh aktif, mendeteksi scan baru secara realtime">
                <div class="live-dot"></div>
                <span>LIVE</span>
            </div>
            <!-- Bulk Delete Button (Hidden by default) -->
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm d-none" onclick="bulkDeletePresensi()">
                <i class="bi bi-trash-fill me-1"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
            </button>
        </div>
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
            <form id="filterForm" action="{{ route('dashboard.kehadiran') }}" method="GET" class="d-flex flex-wrap align-items-center gap-3 m-0 no-loader">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="ref_date" value="{{ $ref_date }}">
                <input type="hidden" name="tanggal_mulai" value="{{ $tanggal_mulai }}">
                <input type="hidden" name="tanggal_akhir" value="{{ $tanggal_akhir }}">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                
                <!-- Custom Dropdown Sholat -->
                <x-filter-dropdown 
                    label="Sholat" 
                    name="waktu_sholat" 
                    selected="{{ request('waktu_sholat') }}" 
                    :options="[
                        '' => 'Semua Waktu',
                        'Subuh' => 'Subuh',
                        'Dzuhur' => 'Dzuhur',
                        'Ashar' => 'Ashar',
                        'Maghrib' => 'Maghrib',
                        'Isya' => 'Isya'
                    ]"
                    form-id="filterForm"
                />

                <!-- Custom Dropdown Status -->
                <x-filter-dropdown 
                    label="Status" 
                    name="status" 
                    selected="{{ request('status') }}" 
                    :options="[
                        '' => 'Semua Status',
                        'Hadir' => 'Hadir',
                        'Alfa' => 'Alpha',
                        'Izin' => 'Izin'
                    ]"
                    form-id="filterForm"
                    button-style="border-radius: 0.75rem; min-width: 120px; background: #fff;"
                />

                <!-- Custom Dropdown Kelas -->
                <x-filter-dropdown 
                    label="Kelas" 
                    name="kelas" 
                    selected="{{ request('kelas') }}" 
                    :options="[
                        '' => 'Semua Kelas',
                        '7 MTs' => '7 MTs',
                        '8 MTs' => '8 MTs',
                        '9 MTs' => '9 MTs',
                        '10 MA' => '10 MA',
                        '11 MA' => '11 MA',
                        '12 MA' => '12 MA'
                    ]"
                    form-id="filterForm"
                    button-style="border-radius: 0.75rem; min-width: 120px; background: #fff;"
                />

                @if(request('search') || request('waktu_sholat') || request('status') || request('kelas'))
                    <a href="{{ route('dashboard.kehadiran', ['mode' => $mode, 'ref_date' => $ref_date]) }}"
                       class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 d-flex align-items-center gap-1"
                       title="Reset semua filter">
                        <i class="bi bi-x-lg" style="font-size: 0.7rem;"></i>
                        <span style="font-size: 0.75rem; font-weight: 600;">Reset</span>
                    </a>
                @endif
            </form>
            <a href="{{ route('dashboard.kehadiran.export', request()->query()) }}" class="btn btn-gradient-success btn-sm px-3 fw-bold" data-no-loader="true">
                <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
            </a>
        </div>
    </div>

    @if(request('search') || request('waktu_sholat') || request('status') || request('kelas'))
    <div class="px-4 py-2 border-bottom d-flex flex-wrap align-items-center gap-2" style="background: var(--color-accent-light);">
        <span class="small fw-bold" style="color: var(--color-accent); font-size: 0.75rem;"><i class="bi bi-funnel-fill me-1"></i>Filter aktif:</span>
        @if(request('search'))
            <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border); font-size: 0.72rem;">
                <i class="bi bi-search me-1"></i>"{{ request('search') }}"
                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="text-muted ms-1" style="text-decoration: none;">&times;</a>
            </span>
        @endif
        @if(request('waktu_sholat'))
            <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border); font-size: 0.72rem;">
                <i class="bi bi-clock me-1"></i>{{ request('waktu_sholat') }}
                <a href="{{ request()->fullUrlWithQuery(['waktu_sholat' => null]) }}" class="text-muted ms-1" style="text-decoration: none;">&times;</a>
            </span>
        @endif
        @if(request('status'))
            <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border); font-size: 0.72rem;">
                <i class="bi bi-check-circle me-1"></i>{{ request('status') == 'Alfa' ? 'Alpha' : request('status') }}
                <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="text-muted ms-1" style="text-decoration: none;">&times;</a>
            </span>
        @endif
        @if(request('kelas'))
            <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background: var(--color-surface); color: var(--color-text); border: 1px solid var(--color-border); font-size: 0.72rem;">
                <i class="bi bi-mortarboard me-1"></i>{{ request('kelas') }}
                <a href="{{ request()->fullUrlWithQuery(['kelas' => null]) }}" class="text-muted ms-1" style="text-decoration: none;">&times;</a>
            </span>
        @endif
    </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="kehadiranTable">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center" width="40">
                            <div class="form-check m-0 d-inline-block">
                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                            </div>
                        </th>
                        <th>Nama Santri</th>
                        <th class="text-center" width="100">Foto Scan</th>
                        <th>Kelas</th>
                        <th>Waktu Sholat</th>
                        <th>Waktu Presensi</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kehadiranTbody">
                    @forelse($presensis as $presensi)
                    <tr data-presensi-id="{{ $presensi->id ?? '' }}" data-santri-id="{{ $presensi->santri_id }}" data-tanggal="{{ $presensi->tanggal }}" data-sholat="{{ $presensi->waktu_sholat }}">
                        <td class="text-center">
                            <div class="form-check m-0 d-inline-block">
                                <input class="form-check-input row-checkbox" type="checkbox" value="{{ $presensi->id ?? '' }}">
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $presensi->santri->nama }}</div>
                        </td>
                        <td class="text-center">
                            @if($presensi->photo_url)
                                <a href="{{ $presensi->photo_url }}" target="_blank" title="Buka foto asli">
                                    <img src="{{ $presensi->photo_url }}" alt="Scan" class="rounded border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div class="small text-muted" style="display: none;"><i class="bi bi-image"></i> Lihat</div>
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>{{ $presensi->santri->kelas }}</td>
                        <td>
                            <span class="badge badge-soft badge-soft-info">
                                @if(in_array($presensi->waktu_sholat, ['Dzuhur', 'Ashar']))
                                    <i class="bi bi-sun-fill me-1 small"></i>
                                @else
                                    <i class="bi bi-moon-stars-fill me-1 small"></i>
                                @endif
                                {{ $presensi->waktu_sholat }}
                            </span>
                        </td>
                        <td class="waktu-hadir-cell">
                            <div class="d-flex align-items-center">
                                @if($presensi->waktu_hadir)
                                    <div class="fw-bold text-dark me-2">{{ \Carbon\Carbon::parse($presensi->waktu_hadir)->format('H:i') }}</div>
                                @else
                                    <div class="fw-bold text-danger me-2">-</div>
                                @endif
                                <div class="small text-muted border-start ps-2">{{ \Carbon\Carbon::parse($presensi->tanggal)->format('d M Y') }}</div>
                            </div>
                        </td>
                        <td class="text-center status-cell">
                            @if($presensi->status == 'Alfa')
                                <span class="badge badge-soft badge-soft-danger px-4">Alpha</span>
                            @elseif($presensi->status == 'Izin')
                                <span class="badge badge-soft badge-soft-info px-4">Izin</span>
                            @else
                                <span class="badge badge-soft badge-soft-success px-4">Hadir</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Edit Status" onclick="editStatus('{{ $presensi->santri_id }}', '{{ $presensi->tanggal }}', '{{ $presensi->waktu_sholat }}', '{{ $presensi->status }}')">
                                    <i class="bi bi-pencil-square text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Hapus" onclick="deletePresensi('{{ $presensi->santri_id }}', '{{ $presensi->tanggal }}', '{{ $presensi->waktu_sholat }}')">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="8" class="text-center py-5">
                            <div class="py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                <h6 class="fw-bold">Belum Ada Data Presensi</h6>
                                <p class="small mb-0">Data kehadiran akan muncul di sini setelah santri melakukan scan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card List View -->
        <div class="d-md-none p-3" id="kehadiranCardList">
            @forelse($presensis as $presensi)
                <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 bg-white" data-presensi-id="{{ $presensi->id ?? '' }}" data-santri-id="{{ $presensi->santri_id }}" data-tanggal="{{ $presensi->tanggal }}" data-sholat="{{ $presensi->waktu_sholat }}">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Left: Scan Photo / Profile Photo -->
                        <div class="flex-shrink-0">
                            @if($presensi->photo_url)
                                <a href="{{ $presensi->photo_url }}" target="_blank" title="Buka foto asli">
                                    <img src="{{ $presensi->photo_url }}" alt="Scan" class="rounded border shadow-sm object-fit-cover" style="width: 50px; height: 50px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div class="avatar bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; display: none; font-size: 1.2rem;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                </a>
                            @else
                                <div class="avatar bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Right: Details -->
                        <div class="flex-grow-1 min-w-0">
                            <!-- Kanan Atas: Nama & Kelas -->
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div>
                                    <span class="fw-bold text-dark d-block text-truncate" style="font-size: 0.95rem;">{{ $presensi->santri->nama }}</span>
                                    <span class="text-muted small">Kelas: {{ $presensi->santri->kelas }}</span>
                                </div>
                                <span class="badge badge-soft status-badge
                                    @if($presensi->status == 'Alfa') badge-soft-danger
                                    @elseif($presensi->status == 'Izin') badge-soft-info
                                    @else badge-soft-success @endif 
                                    px-3.5 py-1.5 rounded-pill fw-bold" style="font-size: 0.75rem;">
                                    {{ $presensi->status == 'Alfa' ? 'Alpha' : $presensi->status }}
                                </span>
                            </div>

                            <!-- Kanan Bawah: Waktu Sholat & Presensi -->
                            <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge badge-soft badge-soft-info py-0.5 px-2" style="font-size: 0.7rem;">
                                        @if(in_array($presensi->waktu_sholat, ['Dzuhur', 'Ashar']))
                                            <i class="bi bi-sun-fill me-1 small"></i>
                                        @else
                                            <i class="bi bi-moon-stars-fill me-1 small"></i>
                                        @endif
                                        {{ $presensi->waktu_sholat }}
                                    </span>
                                    <span class="text-muted small waktu-text" style="font-size: 0.75rem;">
                                        @if($presensi->waktu_hadir)
                                            <strong class="waktu-val">{{ \Carbon\Carbon::parse($presensi->waktu_hadir)->format('H:i') }}</strong>
                                        @else
                                            <strong class="text-danger waktu-val">-</strong>
                                        @endif
                                        <span class="tanggal-val">• {{ \Carbon\Carbon::parse($presensi->tanggal)->format('d M') }}</span>
                                    </span>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Edit Status" onclick="editStatus('{{ $presensi->santri_id }}', '{{ $presensi->tanggal }}', '{{ $presensi->waktu_sholat }}', '{{ $presensi->status }}')">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Hapus" onclick="deletePresensi('{{ $presensi->santri_id }}', '{{ $presensi->tanggal }}', '{{ $presensi->waktu_sholat }}')">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border-0 shadow-sm rounded-4 py-5 text-center bg-white">
                    <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50 text-muted"></i>
                    <h6 class="fw-bold">Belum Ada Data Presensi</h6>
                    <p class="small mb-0 text-muted">Data kehadiran akan muncul di sini setelah santri melakukan scan.</p>
                </div>
            @endforelse
        </div>
    </div>
    @if(count($presensis) > 0)
    <div class="card-footer bg-white border-top py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="small text-muted" id="recordCount">
            @if(method_exists($presensis, 'firstItem'))
                Menampilkan <strong>{{ $presensis->firstItem() }}</strong>
                &ndash;
                <strong>{{ $presensis->lastItem() }}</strong>
                dari <strong>{{ $presensis->total() }}</strong> data
                @if(request('search') || request('waktu_sholat') || request('status') || request('kelas'))
                    <span class="fw-semibold">(terfilter)</span>
                @endif
            @else
                Menampilkan <strong>{{ count($presensis) }}</strong> data rekaman kehadiran
            @endif
        </div>
        @if(method_exists($presensis, 'links'))
            <div>{{ $presensis->links() }}</div>
        @endif
    </div>
    @endif
</div>



@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Realtime Polling Configuration ────────────────────────
    const POLL_INTERVAL = 10000; // 10 detik
    let lastPollTime = new Date().toISOString();
    let knownIds = new Set();

    // Collect existing presensi IDs from the table
    document.querySelectorAll('#kehadiranTbody tr[data-presensi-id]').forEach(row => {
        const id = row.getAttribute('data-presensi-id');
        if (id) knownIds.add(parseInt(id));
    });


    // ─── Insert New Row into Table / Card List ──────────────────
    function insertNewRow(scan) {
        const tbody = document.getElementById('kehadiranTbody');
        const cardList = document.getElementById('kehadiranCardList');
        
        // Remove empty state if present
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        const emptyCard = cardList ? cardList.querySelector('.py-5') : null;
        if (emptyCard) {
            const emptyCardContainer = emptyCard.closest('.card');
            if (emptyCardContainer) emptyCardContainer.remove();
        }

        // Check if row already exists (same santri + tanggal + sholat)
        const existingRow = tbody ? tbody.querySelector(
            `tr[data-santri-id="${scan.santri_id}"][data-tanggal="${scan.tanggal}"][data-sholat="${scan.waktu_sholat}"]`
        ) : null;
        if (existingRow) {
            existingRow.classList.add('row-new-entry');
            setTimeout(() => existingRow.classList.remove('row-new-entry'), 2000);
        }

        const sholatIcon = ['Dzuhur', 'Ashar'].includes(scan.waktu_sholat)
            ? '<i class="bi bi-sun-fill me-1 small"></i>'
            : '<i class="bi bi-moon-stars-fill me-1 small"></i>';

        const waktu = scan.waktu_hadir ? scan.waktu_hadir.substring(0, 5) : '-';
        const waktuClass = scan.waktu_hadir ? 'fw-bold text-dark me-2' : 'fw-bold text-danger me-2';
        const waktuCardClass = scan.waktu_hadir ? 'waktu-val' : 'text-danger waktu-val';

        const fotoCell = scan.photo_url
            ? `<a href="${scan.photo_url}" target="_blank" title="Buka foto asli">
                 <img src="${scan.photo_url}" alt="Scan" class="rounded border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
               </a>`
            : '<span class="text-muted small">-</span>';

        const fotoHtmlCard = scan.photo_url
            ? `<a href="${scan.photo_url}" target="_blank" title="Buka foto asli">
                 <img src="${scan.photo_url}" alt="Scan" class="rounded border shadow-sm object-fit-cover" style="width: 50px; height: 50px;">
               </a>`
            : `<div class="avatar bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                 <i class="bi bi-person-fill"></i>
               </div>`;

        // Format tanggal
        const d = new Date(scan.tanggal + 'T00:00:00');
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const formattedDate = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        const formattedDateShort = `${d.getDate()} ${months[d.getMonth()]}`;

        let statusBadge = '';
        if (scan.status === 'Alfa') {
            statusBadge = '<span class="badge badge-soft badge-soft-danger px-4">Alpha</span>';
        } else if (scan.status === 'Izin') {
            statusBadge = '<span class="badge badge-soft badge-soft-info px-4">Izin</span>';
        } else {
            statusBadge = '<span class="badge badge-soft badge-soft-success px-4">Hadir</span>';
        }

        let statusBadgeCard = '';
        if (scan.status === 'Alfa') {
            statusBadgeCard = '<span class="badge badge-soft status-badge badge-soft-danger px-3.5 py-1.5 rounded-pill fw-bold" style="font-size: 0.75rem;">Alpha</span>';
        } else if (scan.status === 'Izin') {
            statusBadgeCard = '<span class="badge badge-soft status-badge badge-soft-info px-3.5 py-1.5 rounded-pill fw-bold" style="font-size: 0.75rem;">Izin</span>';
        } else {
            statusBadgeCard = '<span class="badge badge-soft status-badge badge-soft-success px-3.5 py-1.5 rounded-pill fw-bold" style="font-size: 0.75rem;">Hadir</span>';
        }

        // 1. Insert into Table (Desktop)
        if (tbody && !existingRow) {
            const tr = document.createElement('tr');
            tr.setAttribute('data-presensi-id', scan.id);
            tr.setAttribute('data-santri-id', scan.santri_id);
            tr.setAttribute('data-tanggal', scan.tanggal);
            tr.setAttribute('data-sholat', scan.waktu_sholat);
            tr.className = 'row-new-entry';

            tr.innerHTML = `
                <td class="text-center">
                    <div class="form-check m-0 d-inline-block">
                        <input class="form-check-input row-checkbox" type="checkbox" value="${scan.id}">
                    </div>
                </td>
                <td><div class="fw-bold text-dark">${scan.nama}</div></td>
                <td class="text-center">${fotoCell}</td>
                <td>${scan.kelas}</td>
                <td><span class="badge badge-soft badge-soft-info">${sholatIcon} ${scan.waktu_sholat}</span></td>
                <td class="waktu-hadir-cell">
                    <div class="d-flex align-items-center">
                        <div class="${waktuClass}">${waktu}</div>
                        <div class="small text-muted border-start ps-2">${formattedDate}</div>
                    </div>
                </td>
                <td class="text-center status-cell">${statusBadge}</td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Edit Status" onclick="editStatus('${scan.santri_id}', '${scan.tanggal}', '${scan.waktu_sholat}', '${scan.status}')">
                            <i class="bi bi-pencil-square text-primary"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Hapus" onclick="deletePresensi('${scan.santri_id}', '${scan.tanggal}', '${scan.waktu_sholat}')">
                            <i class="bi bi-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            `;

            tbody.insertBefore(tr, tbody.firstChild);
        }

        // 2. Insert into Card List (Mobile)
        if (cardList) {
            const existingCard = cardList.querySelector(
                `.card[data-santri-id="${scan.santri_id}"][data-tanggal="${scan.tanggal}"][data-sholat="${scan.waktu_sholat}"]`
            );
            if (existingCard) {
                existingCard.classList.add('row-new-entry');
                setTimeout(() => existingCard.classList.remove('row-new-entry'), 2000);
            } else {
                const card = document.createElement('div');
                card.className = 'card border-0 shadow-sm rounded-4 mb-3 p-3 bg-white row-new-entry';
                card.setAttribute('data-presensi-id', scan.id);
                card.setAttribute('data-santri-id', scan.santri_id);
                card.setAttribute('data-tanggal', scan.tanggal);
                card.setAttribute('data-sholat', scan.waktu_sholat);

                card.innerHTML = `
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0">${fotoHtmlCard}</div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <div>
                                    <span class="fw-bold text-dark d-block text-truncate" style="font-size: 0.95rem;">${scan.nama}</span>
                                    <span class="text-muted small">Kelas: ${scan.kelas}</span>
                                </div>
                                ${statusBadgeCard}
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge badge-soft badge-soft-info py-0.5 px-2" style="font-size: 0.7rem;">
                                        ${sholatIcon} ${scan.waktu_sholat}
                                    </span>
                                    <span class="text-muted small waktu-text" style="font-size: 0.75rem;">
                                        <strong class="${waktuCardClass}">${waktu}</strong>
                                        <span class="tanggal-val">• ${formattedDateShort}</span>
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Edit Status" onclick="editStatus('${scan.santri_id}', '${scan.tanggal}', '${scan.waktu_sholat}', '${scan.status}')">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Hapus" onclick="deletePresensi('${scan.santri_id}', '${scan.tanggal}', '${scan.waktu_sholat}')">
                                        <i class="bi bi-trash text-danger"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                cardList.insertBefore(card, cardList.firstChild);
            }
        }

        // Update count
        const countEl = document.getElementById('recordCount');
        if (countEl && tbody) {
            const totalRows = tbody.querySelectorAll('tr[data-presensi-id]').length;
            countEl.textContent = `Menampilkan ${totalRows} data rekaman kehadiran terbaru.`;
        }
        
        // Update selection state/listeners if needed
        updateBulkDeleteButtonState();
    }

    // ─── Polling Function ──────────────────────────────────────
    function pollLatestScans() {
        fetch(`/presensi/latest-scans?since=${encodeURIComponent(lastPollTime)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.data && data.data.length > 0) {
                // Process newest first (data is already desc by updated_at)
                const newScans = data.data.filter(scan => !knownIds.has(scan.id));

                // Insert row for each new scan
                newScans.reverse().forEach(scan => {
                    knownIds.add(scan.id);
                    insertNewRow(scan);
                });

                // Also handle updates for existing IDs
                data.data.filter(scan => !newScans.includes(scan)).forEach(scan => {
                    const existingRow = document.querySelector(
                        `tr[data-santri-id="${scan.santri_id}"][data-tanggal="${scan.tanggal}"][data-sholat="${scan.waktu_sholat}"]`
                    );
                    if (existingRow) {
                        existingRow.classList.add('row-new-entry');
                        setTimeout(() => existingRow.classList.remove('row-new-entry'), 2000);
                    }
                });
            }
            
            lastPollTime = data.server_time || new Date().toISOString();
        })
        .catch(err => {
            console.warn('Polling error:', err);
        });
    }

    // Start polling
    setInterval(pollLatestScans, POLL_INTERVAL);

    // Also poll once shortly after page load
    setTimeout(pollLatestScans, 2000);

    // ─── Bulk Delete Action / Logic ─────────────────────────────
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const tbody = document.getElementById('kehadiranTbody');

    function updateBulkDeleteButtonState() {
        const checkedBoxes = tbody.querySelectorAll('.row-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            selectedCountSpan.textContent = count;
            bulkDeleteBtn.classList.remove('d-none');
        } else {
            bulkDeleteBtn.classList.add('d-none');
        }

        // Sync selectAllCheckbox
        const totalCheckboxes = tbody.querySelectorAll('.row-checkbox').length;
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = totalCheckboxes > 0 && count === totalCheckboxes;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            tbody.querySelectorAll('.row-checkbox').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateBulkDeleteButtonState();
        });
    }

    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            updateBulkDeleteButtonState();
        }
    });

    window.bulkDeletePresensi = function() {
        const checkedBoxes = tbody.querySelectorAll('.row-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => cb.value).filter(val => val !== '');

        if (ids.length === 0) {
            return;
        }

        if (!confirm(`Apakah Anda yakin ingin menghapus ${ids.length} data presensi yang terpilih?`)) {
            return;
        }

        bulkDeleteBtn.disabled = true;
        bulkDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menghapus...';

        fetch('/api_presensi.php?action=bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash-fill me-1"></i> Hapus Terpilih (<span id="selectedCount">0</span>)`;
            
            if (data.success) {
                checkedBoxes.forEach(cb => {
                    const row = cb.closest('tr');
                    if (row) {
                        row.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(30px)';
                        setTimeout(() => {
                            row.remove();
                            const totalRows = tbody.querySelectorAll('tr[data-santri-id]').length;
                            const countEl = document.getElementById('recordCount');
                            if (totalRows === 0) {
                                tbody.innerHTML = `
                                    <tr id="emptyRow">
                                        <td colspan="8" class="text-center py-5">
                                            <div class="py-4 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                                <h6 class="fw-bold">Belum Ada Data Presensi</h6>
                                                <p class="small mb-0">Data kehadiran akan muncul di sini setelah santri melakukan scan.</p>
                                            </div>
                                        </td>
                                    </tr>`;
                                if (countEl) countEl.textContent = '';
                            } else {
                                if (countEl) countEl.textContent = `Menampilkan ${totalRows} data rekaman kehadiran terbaru.`;
                            }
                            updateBulkDeleteButtonState();
                        }, 400);
                    }
                });

                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                showFlashMessage('success', data.message);
            } else {
                showFlashMessage('danger', data.message || 'Gagal menghapus data.');
            }
        })
        .catch(err => {
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = `<i class="bi bi-trash-fill me-1"></i> Hapus Terpilih (<span id="selectedCount">0</span>)`;
            console.error('Bulk delete error:', err);
            showFlashMessage('danger', 'Terjadi kesalahan saat menghapus data.');
        });
    };
});
</script>
@endpush
