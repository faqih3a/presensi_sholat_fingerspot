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
    
    /* Form & Preview Styles */
    .form-control, .form-select {
        border: 1px solid #edf2f9;
        border-radius: 0.75rem;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.1);
    }
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
        border: 2px dashed #dee2e6;
    }
    #image-preview {
        width: 100%;
        display: none;
        border-radius: 0.75rem;
    }
    body.dark-mode .preview-container {
        background: #2c2c2c;
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                <div class="text-center w-100">
                    <h4 class="fw-bold text-dark mb-1" id="tambahAsatidzModalLabel">
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Tambah Asatidz Masjid Baru
                    </h4>
                    <p class="text-muted small">Buat akun untuk asatidz baru (Admin atau Asatidz) di sistem.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="{{ route('pengurus.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold small text-muted text-uppercase">Nama Lengkap</label>
                                <input type="text" name="name" id="name" class="form-control py-2 @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Contoh: Ahmad Subagja">
                                @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold small text-muted text-uppercase">Alamat Email</label>
                                <input type="email" name="email" id="email" class="form-control py-2 @error('email') is-invalid @enderror" value="{{ old('email') }}" required placeholder="email@example.com">
                                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Role / Peran</label>
                                <div class="premium-select-wrapper">
                                    <button class="premium-select-btn dropdown-toggle py-2" type="button" id="roleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span id="selected-role-text">
                                            @if(old('role') === 'admin')
                                                Admin (Pengelola Sistem)
                                            @elseif(old('role') === 'asatidz')
                                                Asatidz (Pengajar/Staff)
                                            @else
                                                -- Pilih Role --
                                            @endif
                                        </span>
                                        <i class="bi bi-chevron-down small text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow border-0" aria-labelledby="roleDropdown">
                                        <li><a class="dropdown-item py-2 {{ old('role') === 'asatidz' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectRole('asatidz', 'Asatidz (Pengajar/Staff)')">Asatidz (Pengajar/Staff)</a></li>
                                        <li><a class="dropdown-item py-2 {{ old('role') === 'admin' ? 'active' : '' }}" href="javascript:void(0)" onclick="selectRole('admin', 'Admin (Pengelola Sistem)')">Admin (Pengelola Sistem)</a></li>
                                    </ul>
                                    <input type="hidden" name="role" id="role_input" value="{{ old('role') }}" required>
                                </div>
                                @error('role') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="wa_number" class="form-label fw-bold small text-muted text-uppercase">Nomor WhatsApp</label>
                                <input type="text" name="wa_number" id="wa_number" class="form-control py-2 @error('wa_number') is-invalid @enderror" value="{{ old('wa_number') }}" placeholder="Contoh: 628123456789">
                                <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">Gunakan format internasional tanpa tanda + (Contoh: 628123456789)</small>
                                @error('wa_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold small text-muted text-uppercase">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control py-2 @error('password') is-invalid @enderror" required placeholder="Minimal 5 karakter">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label fw-bold small text-muted text-uppercase">Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control py-2" required placeholder="Ulangi password">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="avatar" class="form-label fw-bold small text-muted text-uppercase">Foto Profil</label>
                                <input type="file" class="form-control py-2" id="avatar" name="avatar" accept="image/jpeg, image/png, image/jpg">
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
                        <button type="submit" id="submit-btn" class="btn btn-gradient-success flex-grow-1 py-2 fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i>Buat Akun Asatidz
                        </button>
                        <button type="button" class="btn btn-light px-4 py-2 fw-bold text-muted" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function selectRole(val, text) {
        document.getElementById('role_input').value = val;
        document.getElementById('selected-role-text').innerText = text;
        
        // Update active state
        const items = document.querySelectorAll('#roleDropdown + .dropdown-menu .dropdown-item');
        items.forEach(item => {
            if (item.innerText === text) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if ($errors->any())
            const myModal = new bootstrap.Modal(document.getElementById('tambahAsatidzModal'));
            myModal.show();
        @endif

        const fotoInput = document.getElementById('avatar');
        const imagePreview = document.getElementById('image-preview');
        const previewPlaceholder = document.getElementById('preview-placeholder');
        const extractionStatus = document.getElementById('extraction-status');
        const modalForm = document.getElementById('tambahAsatidzModal').querySelector('form');
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const tambahAsatidzModal = document.getElementById('tambahAsatidzModal');

        // Reset Modal on Close
        tambahAsatidzModal.addEventListener('hidden.bs.modal', function () {
            modalForm.reset();
            imagePreview.style.display = 'none';
            imagePreview.src = '#';
            previewPlaceholder.classList.remove('d-none');
            extractionStatus.classList.add('d-none');
            extractionStatus.innerHTML = '';
            
            // Reset role dropdown select text
            document.getElementById('selected-role-text').innerText = '-- Pilih Role --';
            const items = document.querySelectorAll('#roleDropdown + .dropdown-menu .dropdown-item');
            items.forEach(item => item.classList.remove('active'));
            
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
            if (!file) return;

            // Show image preview
            const imgUrl = URL.createObjectURL(file);
            imagePreview.src = imgUrl;
            imagePreview.style.display = 'block';
            previewPlaceholder.classList.add('d-none');
            
            extractionStatus.classList.remove('d-none');
            extractionStatus.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Foto profil terpilih!';
            extractionStatus.className = 'text-success mt-2 small fw-bold';
        });
    });
</script>
@endpush
@endsection
