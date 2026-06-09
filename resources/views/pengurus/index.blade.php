@extends('layouts.app')

@section('title', 'Kelola Asatidz')

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
        box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease;
    }
    .card-stats:hover {
        transform: translateY(-2px);
    }
    .table th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: #67748e;
        padding: 1rem;
        border-bottom: 2px solid #edf2f9;
    }
    .table td {
        padding: 1rem;
        color: #495057;
        font-size: 0.875rem;
        border-bottom: 1px solid #edf2f9;
    }
    .avatar-sm {
        width: 42px;
        height: 42px;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .badge-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
        border: 1px solid rgba(25, 135, 84, 0.15);
    }
    .badge-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, 0.15);
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
        color: #0dcaf0;
        border: 1px solid rgba(13, 202, 240, 0.15);
    }
    .action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.6rem;
        transition: all 0.2s ease-in-out;
    }
    .action-btn:hover {
        transform: scale(1.1);
    }
    
    body.dark-mode .table td {
        border-bottom-color: #333;
        color: #c1c9d2;
    }
    body.dark-mode .table th {
        border-bottom-color: #444;
        color: #adb5bd;
    }
    body.dark-mode .table-light {
        background-color: #2c2c2c !important;
    }
    body.dark-mode .avatar-sm {
        border-color: #444;
    }
</style>
@endpush

@section('content')
@php
    $totalPengurus = $pengurus->count();
    $totalAdmin = $pengurus->filter(fn($u) => in_array(strtolower(trim($u->role)), ['admin', 'super_admin']))->count();
    $totalAsatidz = $pengurus->filter(fn($u) => in_array(strtolower(trim($u->role)), ['asatidz', 'ustadz', 'guru']))->count();
@endphp

<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold">Kelola Asatidz Masjid</h1>
            <p class="text-muted small mb-0">Daftar semua akun asatidz masjid (Admin dan Asatidz) yang terdaftar di sistem.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-gradient-success px-4 py-2 fw-bold d-flex align-items-center gap-2" style="border-radius: 0.75rem;" data-bs-toggle="modal" data-bs-target="#tambahAsatidzModal">
                <i class="bi bi-person-plus-fill"></i> Tambah Asatidz
            </button>
        </div>
    </div>

    <!-- Alerts -->
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
        <div class="col-12 col-sm-4">
            <div class="card card-stats p-3 bg-white border-0 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">Total Asatidz & Admin</span>
                    <span class="h3 fw-bold text-dark mb-0">{{ $totalPengurus }}</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-success" style="width: 48px; height: 48px; background-color: rgba(25, 135, 84, 0.1);">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card card-stats p-3 bg-white border-0 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">Total Admin</span>
                    <span class="h3 fw-bold text-dark mb-0">{{ $totalAdmin }}</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px; background-color: rgba(13, 110, 253, 0.1);">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-4">
            <div class="card card-stats p-3 bg-white border-0 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">Total Asatidz</span>
                    <span class="h3 fw-bold text-dark mb-0">{{ $totalAsatidz }}</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-success" style="width: 48px; height: 48px; background-color: rgba(25, 135, 84, 0.1);">
                    <i class="bi bi-person-workspace fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card card-stats overflow-hidden border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-person-badge-fill text-success me-2"></i>Daftar Asatidz & Admin</h6>
            <div class="small text-muted">{{ $totalPengurus }} Terdaftar</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" width="80">No</th>
                            <th width="80">Foto</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. WhatsApp</th>
                            <th>Role / Peran</th>
                            <th>Tanggal Daftar</th>
                            <th class="text-center" width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pengurus as $index => $u)
                        <tr>
                            <td class="text-center">
                                <span class="text-muted fw-bold small">{{ $index + 1 }}</span>
                            </td>
                            <td>
                                @if($u->avatar)
                                    <img src="{{ asset('storage/avatars/' . $u->avatar) }}" alt="{{ $u->name }}" class="avatar-sm rounded-circle object-fit-cover">
                                @else
                                    @php
                                        $roleVal = strtolower(trim($u->role));
                                        $isAsatidz = in_array($roleVal, ['asatidz', 'ustadz', 'guru']);
                                        $gradient = $isAsatidz 
                                            ? 'background: linear-gradient(310deg, #198754 0%, #2dc57b 100%)' 
                                            : 'background: linear-gradient(310deg, #0d6efd 0%, #0dcaf0 100%)';
                                        
                                        // Create name initials
                                        $words = explode(' ', trim($u->name));
                                        $initials = '';
                                        if (count($words) >= 2) {
                                            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                        } else {
                                            $initials = strtoupper(substr($u->name, 0, 2));
                                        }
                                    @endphp
                                    <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="{{ $gradient }}; font-size: 0.85rem;">
                                        {{ $initials ?: 'A' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $u->name }}</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">PIN: {{ $u->fingerspot_pin ?? '-' }}</div>
                            </td>
                            <td>{{ $u->email }}</td>
                            <td>
                                @if($u->wa_number)
                                    <a href="https://wa.me/{{ $u->wa_number }}" target="_blank" class="text-decoration-none text-success fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-whatsapp"></i> {{ $u->wa_number }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $roleVal = strtolower(trim($u->role));
                                @endphp
                                @if($roleVal === 'admin' || $roleVal === 'super_admin')
                                    <span class="badge badge-soft-primary px-3 py-2 fw-semibold" style="font-size: 0.7rem; border-radius: 0.5rem;">Admin</span>
                                @elseif($roleVal === 'asatidz' || $roleVal === 'ustadz' || $roleVal === 'guru')
                                    <span class="badge badge-soft-success px-3 py-2 fw-semibold" style="font-size: 0.7rem; border-radius: 0.5rem;">Asatidz</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2 fw-semibold text-white" style="font-size: 0.7rem; border-radius: 0.5rem;">{{ ucfirst($u->role) ?: 'Asatidz' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-dark small">{{ $u->created_at->format('d M Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $u->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('pengurus.edit', $u->id) }}" class="action-btn bg-info bg-opacity-10 text-info" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('pengurus.destroy', $u->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus asatidz ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn bg-danger bg-opacity-10 text-danger border-0" title="Hapus">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="py-4">
                                    <i class="bi bi-person-exclamation text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="mt-3 fw-bold text-dark">Belum Ada Data</h5>
                                    <p class="text-muted mb-4">Silakan daftarkan asatidz baru untuk mulai mengelola.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($totalPengurus > 0)
        <div class="card-footer bg-white border-top py-3">
            <div class="small text-muted">
                Menampilkan 1 sampai {{ $totalPengurus }} dari {{ $totalPengurus }} data Asatidz & Admin.
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Asatidz -->
<div class="modal fade" id="tambahAsatidzModal" tabindex="-1" aria-labelledby="tambahAsatidzModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="tambahAsatidzModalLabel">Tambah Asatidz Masjid Baru</h5>
                <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-4">Buat akun untuk asatidz baru (Admin atau Asatidz) di sistem.</p>
                
                <form action="{{ route('pengurus.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                        <input type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Contoh: Ahmad Subagja">
                        @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">ALAMAT EMAIL</label>
                        <input type="email" name="email" class="form-control rounded-3 @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="email@example.com">
                        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">ROLE / PERAN</label>
                        <select name="role" class="form-select rounded-3 @error('role') is-invalid @enderror" required>
                            <option value="asatidz" {{ old('role') === 'asatidz' ? 'selected' : '' }}>Asatidz (Pengajar/Staff)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (Pengelola Sistem)</option>
                        </select>
                        @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOMOR WHATSAPP</label>
                        <input type="text" name="wa_number" class="form-control rounded-3 @error('wa_number') is-invalid @enderror" value="{{ old('wa_number') }}" placeholder="Contoh: 628123456789">
                        <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">Gunakan format internasional tanpa tanda + (Contoh: 628123456789)</small>
                        @error('wa_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label small fw-bold text-muted">PASSWORD</label>
                            <input type="password" name="password" class="form-control rounded-3 @error('password') is-invalid @enderror" required placeholder="Minimal 5 karakter">
                            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">KONFIRMASI PASSWORD</label>
                            <input type="password" name="password_confirmation" class="form-control rounded-3" required placeholder="Ulangi password">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success rounded-3 w-100 fw-bold py-2">
                            BUAT AKUN ASATIDZ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if ($errors->any())
            const myModal = new bootstrap.Modal(document.getElementById('tambahAsatidzModal'));
            myModal.show();
        @endif
    });
</script>
@endpush
@endsection
