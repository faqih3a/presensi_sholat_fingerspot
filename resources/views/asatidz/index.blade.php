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
    .form-control {
        border: 1px solid #edf2f9;
        border-radius: 0.75rem;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    .form-control:focus {
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

    /* Edit modal styles */
    .edit-current-avatar {
        width: 80px;
        height: 80px;
        border-radius: 0.75rem;
        object-fit: cover;
        border: 2px solid #edf2f9;
    }
    .edit-preview-box img {
        width: 80px;
        height: 80px;
        border-radius: 0.75rem;
        object-fit: cover;
        border: 2px solid #198754;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1 text-dark fw-bold">Kelola Asatidz</h1>
            <p class="text-muted small mb-0">Daftar semua akun asatidz masjid yang terdaftar di sistem.</p>
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
        <div class="col-12">
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
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-person-badge-fill text-success me-2"></i>Daftar Asatidz</h6>
            <div class="small text-muted">{{ $totalAsatidz }} Terdaftar</div>
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
                            <th>Tanggal Daftar</th>
                            <th class="text-center" width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($asatidz as $index => $u)
                        <tr>
                            <td class="text-center">
                                <span class="text-muted fw-bold small">{{ $index + 1 }}</span>
                            </td>
                            <td>
                                @if($u->avatar)
                                    <img src="{{ asset('storage/avatars/' . $u->avatar) }}" alt="{{ $u->name }}" class="avatar-sm rounded-circle object-fit-cover">
                                @else
                                    @php
                                        // Create name initials
                                        $words = explode(' ', trim($u->name));
                                        $initials = '';
                                        if (count($words) >= 2) {
                                            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                        } else {
                                            $initials = strtoupper(substr($u->name, 0, 2));
                                        }
                                    @endphp
                                    <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="background: linear-gradient(310deg, #198754 0%, #2dc57b 100%); font-size: 0.85rem;">
                                        {{ $initials ?: 'A' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $u->name }}</div>
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
                                <div class="text-dark small">{{ $u->created_at->format('d M Y') }}</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ $u->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="action-btn bg-info bg-opacity-10 text-info border-0 btn-edit-asatidz" title="Edit"
                                        data-id="{{ $u->id }}"
                                        data-name="{{ $u->name }}"
                                        data-email="{{ $u->email }}"
                                        data-wa="{{ $u->wa_number }}"
                                        data-avatar="{{ $u->avatar ? asset('storage/avatars/' . $u->avatar) : '' }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('asatidz.destroy', $u->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus asatidz ini?')">
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
                            <td colspan="7" class="text-center py-5">
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
        @if($totalAsatidz > 0)
        <div class="card-footer bg-white border-top py-3">
            <div class="small text-muted">
                Menampilkan 1 sampai {{ $totalAsatidz }} dari {{ $totalAsatidz }} data Asatidz.
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
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Tambah Asatidz Baru
                    </h4>
                    <p class="text-muted small">Buat akun untuk asatidz baru di sistem.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="{{ route('asatidz.store') }}" method="POST" enctype="multipart/form-data">
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
                                <label for="wa_number" class="form-label fw-bold small text-muted text-uppercase">Nomor WhatsApp</label>
                                <input type="text" name="wa_number" id="wa_number" class="form-control py-2 @error('wa_number') is-invalid @enderror" value="{{ old('wa_number') }}" placeholder="Contoh: 628123456789">
                                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Gunakan format kode negara (628...)</small>
                                @error('wa_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-bold small text-muted text-uppercase">Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control py-2 @error('password') is-invalid @enderror" required minlength="5">
                                        <button class="btn btn-outline-secondary toggle-password-btn" type="button">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label fw-bold small text-muted text-uppercase">Ulangi Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control py-2" required minlength="5">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="avatar" class="form-label fw-bold small text-muted text-uppercase">Foto Profil</label>
                                <input type="file" name="avatar" id="avatar" class="form-control py-2 @error('avatar') is-invalid @enderror" accept="image/*">
                                @error('avatar') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="mb-3">
                                <div class="preview-container shadow-sm">
                                    <img id="image-preview" src="#" alt="Preview Foto" />
                                    <div id="preview-placeholder" class="text-center text-muted">
                                        <i class="bi bi-camera fs-1 d-block mb-2"></i>
                                        <span class="small">Belum ada foto terpilih</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-gradient-success flex-grow-1 py-2 fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i>Simpan Data Asatidz
                        </button>
                        <button type="button" class="btn btn-light px-4 py-2 fw-bold text-muted" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Asatidz (Single Reusable) -->
<div class="modal fade" id="editAsatidzModal" tabindex="-1" aria-labelledby="editAsatidzModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <div class="modal-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                <div class="text-center w-100">
                    <h4 class="fw-bold text-dark mb-1" id="editAsatidzModalLabel">
                        <i class="bi bi-pencil-square text-info me-2"></i>Edit Data Asatidz
                    </h4>
                    <p class="text-muted small">Perbarui informasi akun Asatidz.</p>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editAsatidzForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nama Lengkap</label>
                                <input type="text" name="name" id="edit_name" class="form-control py-2" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Alamat Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control py-2" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Nomor WhatsApp</label>
                                <input type="text" name="wa_number" id="edit_wa_number" class="form-control py-2" placeholder="Contoh: 628123456789">
                                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Gunakan format kode negara (628...)</small>
                            </div>

                            <div class="alert alert-info border-0 rounded-3 small mb-3 py-2">
                                <i class="bi bi-info-circle me-1"></i> Kosongkan password jika tidak ingin diubah.
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Password Baru</label>
                                    <input type="password" name="password" id="edit_password" class="form-control py-2" placeholder="Kosongkan jika tetap">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Konfirmasi</label>
                                    <input type="password" name="password_confirmation" id="edit_password_confirmation" class="form-control py-2" placeholder="Ulangi password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Foto Profil (Opsional)</label>
                                <input type="file" name="avatar" id="edit_avatar" class="form-control py-2" accept="image/*">
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div id="edit-current-avatar-box">
                                        <label class="d-block small text-muted mb-1">Foto Saat Ini</label>
                                        <img id="edit-current-avatar" src="#" alt="Avatar" class="edit-current-avatar">
                                    </div>
                                    <div class="edit-preview-box" id="edit-preview-box" style="display: none;">
                                        <label class="d-block small text-muted mb-1">Foto Baru</label>
                                        <img id="edit-image-preview" src="#" alt="Preview">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-gradient-success flex-grow-1 py-2 fw-bold">
                            <i class="bi bi-check-circle-fill me-2"></i>Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-light px-4 py-2 fw-bold text-muted" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ─── Modal Tambah: Toggle Password ─────────────────────────
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const toggleBtn = document.querySelector('.toggle-password-btn');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                confirmInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });
        }

        // ─── Modal Tambah: Image Preview ───────────────────────────
        const fotoInput = document.getElementById('avatar');
        const imagePreview = document.getElementById('image-preview');
        const previewPlaceholder = document.getElementById('preview-placeholder');

        if (fotoInput) {
            fotoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const imgUrl = URL.createObjectURL(file);
                imagePreview.src = imgUrl;
                imagePreview.style.display = 'block';
                previewPlaceholder.classList.add('d-none');
            });
        }

        // ─── Modal Edit: Populate & Open ───────────────────────────
        const editForm = document.getElementById('editAsatidzForm');
        const editModal = document.getElementById('editAsatidzModal');
        const editCurrentAvatarBox = document.getElementById('edit-current-avatar-box');
        const editCurrentAvatar = document.getElementById('edit-current-avatar');
        const editPreviewBox = document.getElementById('edit-preview-box');
        const editImagePreview = document.getElementById('edit-image-preview');
        const editAvatarInput = document.getElementById('edit_avatar');

        document.querySelectorAll('.btn-edit-asatidz').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const email = this.dataset.email;
                const wa = this.dataset.wa;
                const avatar = this.dataset.avatar;

                // Set form action dynamically
                editForm.action = `/asatidz/${id}`;

                // Populate fields
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_wa_number').value = wa || '';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_password_confirmation').value = '';

                // Reset file input & preview
                editAvatarInput.value = '';
                editPreviewBox.style.display = 'none';

                // Show/hide current avatar
                if (avatar) {
                    editCurrentAvatar.src = avatar;
                    editCurrentAvatarBox.style.display = 'block';
                } else {
                    editCurrentAvatarBox.style.display = 'none';
                }

                // Open the modal
                const bsModal = new bootstrap.Modal(editModal);
                bsModal.show();
            });
        });

        // ─── Modal Edit: Image Preview ─────────────────────────────
        if (editAvatarInput) {
            editAvatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) {
                    editPreviewBox.style.display = 'none';
                    return;
                }
                const imgUrl = URL.createObjectURL(file);
                editImagePreview.src = imgUrl;
                editPreviewBox.style.display = 'block';
            });
        }

        // ─── Modal Edit: Reset on close ────────────────────────────
        if (editModal) {
            editModal.addEventListener('hidden.bs.modal', function() {
                editAvatarInput.value = '';
                editPreviewBox.style.display = 'none';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_password_confirmation').value = '';
            });
        }
    });
</script>
@endpush
