@extends('layouts.app')

@section('title', 'Edit Admin')

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <a href="{{ route('admin-manage.index') }}" class="text-decoration-none text-muted mb-2 d-inline-block">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
        <h4 class="fw-800 text-dark mb-1">Edit Data Admin</h4>
        <p class="text-muted small">Perbarui informasi akun Admin.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <form action="{{ route('admin-manage.update', $admin->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">NAMA LENGKAP</label>
                            <input type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" value="{{ old('name', $admin->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">ALAMAT EMAIL</label>
                            <input type="email" name="email" class="form-control rounded-3 @error('email') is-invalid @enderror" value="{{ old('email', $admin->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">NOMOR WHATSAPP</label>
                            <input type="text" name="wa_number" class="form-control rounded-3 @error('wa_number') is-invalid @enderror" value="{{ old('wa_number', $admin->wa_number) }}" placeholder="Contoh: 628123456789">
                            <small class="text-muted mt-1 d-block">Gunakan format internasional tanpa tanda + (Contoh: 628123456789)</small>
                            @error('wa_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">FOTO PROFIL (OPSIONAL)</label>
                            <input type="file" name="avatar" class="form-control rounded-3 @error('avatar') is-invalid @enderror" accept="image/*">
                            @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($admin->avatar)
                                    <div>
                                        <label class="d-block small text-muted mb-1">Foto Saat Ini:</label>
                                        <img src="{{ asset('storage/avatars/' . $admin->avatar) }}" alt="Avatar" class="rounded-3 border object-fit-cover" style="width: 100px; height: 100px;">
                                    </div>
                                @endif
                                <div id="preview-box" style="display: none;">
                                    <label class="d-block small text-muted mb-1">Foto Baru:</label>
                                    <img id="image-preview" src="#" alt="Preview" class="rounded-3 border object-fit-cover" style="width: 100px; height: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4 text-muted opacity-25">
                
                <div class="alert alert-info border-0 rounded-3 small mb-4">
                    <i class="bi bi-info-circle me-2"></i> Kosongkan password jika tidak ingin diubah.
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">PASSWORD BARU</label>
                        <input type="password" name="password" id="password" class="form-control rounded-3 @error('password') is-invalid @enderror" placeholder="Kosongkan jika tetap">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">KONFIRMASI PASSWORD</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control rounded-3" placeholder="Ulangi password">
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success rounded-3 px-5 py-2 w-100 fw-bold">
                        SIMPAN PERUBAHAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fotoInput = document.querySelector('input[name="avatar"]');
        const imagePreview = document.getElementById('image-preview');
        const previewBox = document.getElementById('preview-box');

        fotoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) {
                previewBox.style.display = 'none';
                return;
            }

            const imgUrl = URL.createObjectURL(file);
            imagePreview.src = imgUrl;
            previewBox.style.display = 'block';
        });
    });
</script>
@endpush
