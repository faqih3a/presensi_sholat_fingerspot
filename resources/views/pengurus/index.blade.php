@extends('layouts.app')

@section('title', 'Kelola Pengurus')

@section('push_styles')
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
    .badge-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, 0.2);
    }
    .badge-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
        color: #0dcaf0;
        border: 1px solid rgba(13, 202, 240, 0.2);
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
    
    body.dark-mode .table td, body.dark-mode .table th {
        border-bottom-color: #333;
    }
    body.dark-mode .table-light {
        background-color: #2c2c2c !important;
    }
    body.dark-mode .avatar-sm {
        border-color: #444;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold">Kelola Pengurus Masjid</h1>
            <p class="text-muted small mb-0">Daftar semua akun pengurus masjid (Admin dan Asatidz) yang terdaftar di sistem.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pengurus.create') }}" class="btn btn-gradient-success px-4 py-2 fw-bold" style="border-radius: 0.5rem;">
                <i class="bi bi-person-plus-fill me-2"></i> Tambah Pengurus
            </a>
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

    <div class="card card-stats overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-person-badge-fill text-success me-2"></i>Daftar Pengurus</h6>
            <div class="small text-muted">{{ count($pengurus) }} Pengurus Terdaftar</div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" width="80">No</th>
                            <th>Foto</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. WhatsApp</th>
                            <th>Role / Peran</th>
                            <th>Tanggal Daftar</th>
                            <th class="text-center">Aksi</th>
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
                                    <div class="avatar-sm bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center border">
                                        <i class="bi bi-person fs-5"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $u->name }}</div>
                                <div class="small text-muted">PIN: {{ $u->fingerspot_pin ?? '-' }}</div>
                            </td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->wa_number ?? '-' }}</td>
                            <td>
                                @if($u->role === 'admin')
                                    <span class="badge badge-soft-primary px-3 py-2 fw-semibold">Admin</span>
                                @elseif($u->role === 'asatidz')
                                    <span class="badge badge-soft-success px-3 py-2 fw-semibold">Asatidz</span>
                                @else
                                    <span class="badge bg-secondary px-3 py-2 fw-semibold">{{ ucfirst($u->role) }}</span>
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
                                        <form action="{{ route('pengurus.destroy', $u->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengurus ini?')">
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
                                    <p class="text-muted mb-4">Silakan daftarkan pengurus baru untuk mulai mengelola.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(count($pengurus) > 0)
        <div class="card-footer bg-white border-top py-3">
            <div class="small text-muted">
                Menampilkan 1 sampai {{ count($pengurus) }} dari {{ count($pengurus) }} data pengurus.
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
