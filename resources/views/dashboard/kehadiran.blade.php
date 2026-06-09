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

    /* ─── Toast Notification ──────────────────────────────── */
    .scan-toast-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        pointer-events: none;
    }
    .scan-toast {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.25rem;
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(25,135,84,0.15);
        animation: toastSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: auto;
        max-width: 380px;
        border-left: 4px solid #198754;
    }
    .scan-toast.toast-exit {
        animation: toastSlideOut 0.3s ease-in forwards;
    }
    @keyframes toastSlideIn {
        from { opacity: 0; transform: translateX(60px) scale(0.95); }
        to { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastSlideOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(60px); }
    }
    .scan-toast-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #198754;
        flex-shrink: 0;
    }
    .scan-toast-avatar-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(310deg, #198754 0%, #2dc57b 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .scan-toast-body {
        flex: 1;
        min-width: 0;
    }
    .scan-toast-name {
        font-weight: 700;
        font-size: 0.85rem;
        color: #333;
        line-height: 1.2;
    }
    .scan-toast-detail {
        font-size: 0.75rem;
        color: #67748e;
        line-height: 1.3;
    }
    .scan-toast-close {
        background: none;
        border: none;
        color: #adb5bd;
        font-size: 1rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        flex-shrink: 0;
    }
    .scan-toast-close:hover { color: #333; }

    body.dark-mode .scan-toast {
        background: #2c2c2c;
        box-shadow: 0 10px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(25,135,84,0.25);
    }
    body.dark-mode .scan-toast-name { color: #f8f9fa; }
    body.dark-mode .scan-toast-detail { color: #adb5bd; }

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
        </div>
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
            <form id="filterForm" action="{{ route('dashboard.kehadiran') }}" method="GET" class="d-flex flex-wrap align-items-center gap-3 m-0 no-loader">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="ref_date" value="{{ $ref_date }}">
                <input type="hidden" name="tanggal_mulai" value="{{ $tanggal_mulai }}">
                <input type="hidden" name="tanggal_akhir" value="{{ $tanggal_akhir }}">
                
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
            </form>
            <a href="{{ route('dashboard.kehadiran.export', request()->query()) }}" class="btn btn-gradient-success btn-sm px-3 fw-bold" data-no-loader="true">
                <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap" id="kehadiranTable">
                <thead class="bg-light">
                    <tr>
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
                        <td colspan="7" class="text-center py-5">
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
    </div>
    @if(count($presensis) > 0)
    <div class="card-footer bg-white border-top py-3 text-center text-md-start">
        <div class="small text-muted" id="recordCount">
            Menampilkan {{ count($presensis) }} data rekaman kehadiran terbaru.
        </div>
    </div>
    @endif
</div>

<!-- Toast Container -->
<div class="scan-toast-container" id="scanToastContainer"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Realtime Polling Configuration ────────────────────────
    const POLL_INTERVAL = 10000; // 10 detik
    const TOAST_DURATION = 5000; // 5 detik
    let lastPollTime = new Date().toISOString();
    let knownIds = new Set();

    // Collect existing presensi IDs from the table
    document.querySelectorAll('#kehadiranTbody tr[data-presensi-id]').forEach(row => {
        const id = row.getAttribute('data-presensi-id');
        if (id) knownIds.add(parseInt(id));
    });

    // ─── Toast Functions ───────────────────────────────────────
    function showScanToast(scan) {
        const container = document.getElementById('scanToastContainer');
        
        const toast = document.createElement('div');
        toast.className = 'scan-toast';
        
        const avatarHtml = scan.foto
            ? `<img src="${scan.foto}" class="scan-toast-avatar" alt="${scan.nama}">`
            : `<div class="scan-toast-avatar-placeholder"><i class="bi bi-person-fill"></i></div>`;
        
        const sholatIcon = ['Dzuhur', 'Ashar'].includes(scan.waktu_sholat)
            ? '<i class="bi bi-sun-fill text-warning"></i>'
            : '<i class="bi bi-moon-stars-fill text-info"></i>';
        
        const waktu = scan.waktu_hadir ? scan.waktu_hadir.substring(0, 5) : '-';
        
        toast.innerHTML = `
            ${avatarHtml}
            <div class="scan-toast-body">
                <div class="scan-toast-name">${scan.nama}</div>
                <div class="scan-toast-detail">
                    ${sholatIcon} ${scan.waktu_sholat} • ${waktu} WIB • Kelas ${scan.kelas}
                </div>
            </div>
            <button class="scan-toast-close" onclick="this.parentElement.classList.add('toast-exit'); setTimeout(() => this.parentElement.remove(), 300);">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        
        container.appendChild(toast);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 300);
            }
        }, TOAST_DURATION);
    }

    // ─── Insert New Row into Table ─────────────────────────────
    function insertNewRow(scan) {
        const tbody = document.getElementById('kehadiranTbody');
        
        // Remove empty state row if present
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        // Check if row already exists (same santri + tanggal + sholat)
        const existingRow = tbody.querySelector(
            `tr[data-santri-id="${scan.santri_id}"][data-tanggal="${scan.tanggal}"][data-sholat="${scan.waktu_sholat}"]`
        );
        if (existingRow) {
            // Update existing row with highlight
            existingRow.classList.add('row-new-entry');
            setTimeout(() => existingRow.classList.remove('row-new-entry'), 2000);
            return;
        }

        const sholatIcon = ['Dzuhur', 'Ashar'].includes(scan.waktu_sholat)
            ? '<i class="bi bi-sun-fill me-1 small"></i>'
            : '<i class="bi bi-moon-stars-fill me-1 small"></i>';

        const waktu = scan.waktu_hadir ? scan.waktu_hadir.substring(0, 5) : '-';
        const waktuClass = scan.waktu_hadir ? 'fw-bold text-dark me-2' : 'fw-bold text-danger me-2';

        const fotoCell = scan.photo_url
            ? `<a href="${scan.photo_url}" target="_blank" title="Buka foto asli">
                 <img src="${scan.photo_url}" alt="Scan" class="rounded border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
               </a>`
            : '<span class="text-muted small">-</span>';

        // Format tanggal
        const d = new Date(scan.tanggal + 'T00:00:00');
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const formattedDate = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;

        let statusBadge = '';
        if (scan.status === 'Alfa') {
            statusBadge = '<span class="badge badge-soft badge-soft-danger px-4">Alpha</span>';
        } else if (scan.status === 'Izin') {
            statusBadge = '<span class="badge badge-soft badge-soft-info px-4">Izin</span>';
        } else {
            statusBadge = '<span class="badge badge-soft badge-soft-success px-4">Hadir</span>';
        }

        const tr = document.createElement('tr');
        tr.setAttribute('data-presensi-id', scan.id);
        tr.setAttribute('data-santri-id', scan.santri_id);
        tr.setAttribute('data-tanggal', scan.tanggal);
        tr.setAttribute('data-sholat', scan.waktu_sholat);
        tr.className = 'row-new-entry';

        tr.innerHTML = `
            <td><div class="fw-bold text-dark">${scan.nama}</div></td>
            <td class="text-center">${fotoCell}</td>
            <td>${scan.kelas}</td>
            <td><span class="badge badge-soft badge-soft-info">${sholatIcon} ${scan.waktu_sholat}</span></td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="${waktuClass}">${waktu}</div>
                    <div class="small text-muted border-start ps-2">${formattedDate}</div>
                </div>
            </td>
            <td class="text-center">${statusBadge}</td>
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

        // Insert at top of tbody
        tbody.insertBefore(tr, tbody.firstChild);

        // Update count
        const countEl = document.getElementById('recordCount');
        if (countEl) {
            const totalRows = tbody.querySelectorAll('tr[data-presensi-id]').length;
            countEl.textContent = `Menampilkan ${totalRows} data rekaman kehadiran terbaru.`;
        }
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
                
                // Show toast + insert row for each new scan
                newScans.reverse().forEach(scan => {
                    knownIds.add(scan.id);
                    showScanToast(scan);
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
});
</script>
@endpush
