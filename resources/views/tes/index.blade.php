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
@keyframes toastSlideIn {
    from { opacity: 0; transform: translateX(60px) scale(0.95); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}
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
            <!-- Bulk Delete Button (Hidden by default) -->
            <button type="button" id="bulkDeleteBtn" class="btn btn-danger btn-sm rounded-3 px-3 shadow-sm d-none" onclick="bulkDeletePresensi()">
                <i class="bi bi-trash-fill me-1"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
            </button>
        </div>
        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
            <form id="filterForm" action="{{ route('tes.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-3 m-0 no-loader">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="ref_date" value="{{ $ref_date }}">

                {{-- Search input --}}
                <div class="input-group" style="max-width: 220px;">
                    <span class="input-group-text border-end-0" style="background: #fff; border-radius: 0.75rem 0 0 0.75rem; border-color: #edf2f9;"><i class="bi bi-search text-muted" style="font-size: 0.8rem;"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama..." value="{{ request('search') }}" style="border-radius: 0 0.75rem 0.75rem 0; border-color: #edf2f9; font-size: 0.85rem;">
                </div>

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

                @if(request('search') || request('status'))
                    <a href="{{ route('tes.index', ['mode' => $mode, 'ref_date' => $ref_date]) }}"
                       class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 d-flex align-items-center gap-1"
                       title="Reset semua filter">
                        <i class="bi bi-x-lg" style="font-size: 0.7rem;"></i>
                        <span style="font-size: 0.75rem; font-weight: 600;">Reset</span>
                    </a>
                @endif
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
                        <th class="text-center" width="40">
                            <div class="form-check m-0 d-inline-block">
                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                            </div>
                        </th>
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
                    <tr data-presensi-id="{{ $presensi->id ?? '' }}" data-santri-id="{{ $presensi->santri_id }}" data-tanggal="{{ $presensi->tanggal }}" data-sholat="{{ $presensi->waktu_sholat }}">
                        <td class="text-center">
                            <div class="form-check m-0 d-inline-block">
                                <input class="form-check-input row-checkbox" type="checkbox" value="{{ $presensi->id ?? '' }}">
                            </div>
                        </td>
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
                            <button type="button" class="btn btn-sm btn-white border px-2 py-1 rounded-2 shadow-sm" title="Hapus" onclick="deletePresensi('{{ $presensi->santri_id }}', '{{ $presensi->tanggal }}', '{{ $presensi->waktu_sholat }}')">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="8" class="text-center py-5">
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
    <div class="card-footer bg-white border-top py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="small text-muted">
            @if(method_exists($presensis, 'firstItem'))
                Menampilkan <strong>{{ $presensis->firstItem() }}</strong>
                &ndash;
                <strong>{{ $presensis->lastItem() }}</strong>
                dari <strong>{{ $presensis->total() }}</strong> data
                @if(request('search') || request('status'))
                    <span class="fw-semibold">(terfilter)</span>
                @endif
            @else
                Menampilkan <strong>{{ count($presensis) }}</strong> data presensi tes.
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
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const tbody = document.querySelector('table tbody');

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

    if (tbody) {
        tbody.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-checkbox')) {
                updateBulkDeleteButtonState();
            }
        });
    }

    window.deletePresensi = function(santriId, tanggal, waktuSholat) {
        if (!confirm('Apakah Anda yakin ingin menghapus data presensi ini?')) {
            return;
        }

        const items = document.querySelectorAll(
            `[data-santri-id="${santriId}"][data-tanggal="${tanggal}"][data-sholat="${waktuSholat}"]`
        );
        
        items.forEach(item => {
            const deleteBtn = item.querySelector('button[title="Hapus"]');
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm text-danger"></span>';
            }
        });

        fetch('/api_presensi.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                santri_id: santriId,
                tanggal: tanggal,
                waktu_sholat: waktuSholat,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                items.forEach(item => {
                    item.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(30px)';
                    setTimeout(() => {
                        item.remove();
                        checkEmptyState();
                    }, 400);
                });
                showFlashMessage('success', data.message);
            } else {
                items.forEach(item => {
                    const deleteBtn = item.querySelector('button[title="Hapus"]');
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="bi bi-trash text-danger"></i>';
                    }
                });
                showFlashMessage('danger', data.message || 'Gagal menghapus data.');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            items.forEach(item => {
                const deleteBtn = item.querySelector('button[title="Hapus"]');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="bi bi-trash text-danger"></i>';
                }
            });
            showFlashMessage('danger', 'Terjadi kesalahan saat menghapus data.');
        });
    }

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
                            checkEmptyState();
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

    function checkEmptyState() {
        const totalRows = tbody.querySelectorAll('tr[data-presensi-id]').length;
        const countEl = document.querySelector('.card-footer .text-muted');
        const footer = document.querySelector('.card-footer');
        
        if (totalRows === 0) {
            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="8" class="text-center py-5">
                        <div class="py-4 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                            <h6 class="fw-bold">Belum Ada Data Presensi Tes</h6>
                            <p class="small mb-0">Data presensi diluar waktu sholat akan muncul di sini setelah santri melakukan scan.</p>
                        </div>
                    </td>
                </tr>`;
            if (footer) footer.remove();
        } else {
            if (countEl) countEl.textContent = `Menampilkan ${totalRows} data presensi tes.`;
        }
    }

    function showFlashMessage(type, message) {
        const existing = document.getElementById('ajaxFlashMessage');
        if (existing) existing.remove();

        const alertDiv = document.createElement('div');
        alertDiv.id = 'ajaxFlashMessage';
        alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
        alertDiv.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px; max-width: 450px; animation: toastSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);';
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger'} fs-5"></i>
                <span class="fw-semibold">${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.style.transition = 'opacity 0.3s ease-out';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 4000);
    }
});
</script>
@endpush
