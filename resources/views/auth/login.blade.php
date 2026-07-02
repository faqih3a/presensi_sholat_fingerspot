@extends('layouts.guest')

@section('title', 'Login')

@push('styles')
<style>
    /* ─── Design Tokens (override/extend guest layout) ─── */
    .login-wrap {
        min-height: 100dvh;
        display: flex;
    }

    /* ─── Left Panel ─── */
    .login-left {
        flex: 0 0 100%;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 3rem 1.5rem;
        background-color: var(--color-bg);
    }
    @media (min-width: 992px) {
        .login-left { flex: 0 0 50%; max-width: 50%; }
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        background-color: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 2.25rem 2rem;
    }

    /* Brand Icon */
    .brand-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background-color: rgba(42, 107, 79, 0.08);
        color: #2A6B4F;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        margin-bottom: 1rem;
    }
    .login-title {
        font-family: var(--font-display);
        font-size: 1.4rem;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: var(--color-text);
        margin: 0 0 .2rem;
    }
    .login-subtitle {
        font-size: .83rem;
        color: var(--color-muted);
        margin: 0 0 1.75rem;
    }

    /* Form Labels */
    .field-label {
        display: block;
        font-size: .7rem;
        font-weight: 600;
        color: var(--color-muted);
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-bottom: .35rem;
    }
    .field-row {
        margin-bottom: 1.1rem;
    }

    /* Inputs */
    .field-input {
        width: 100%;
        border: 1px solid var(--color-border);
        border-radius: 7px;
        padding: .62rem .85rem;
        font-size: .88rem;
        font-family: var(--font-body);
        color: var(--color-text);
        background-color: var(--color-surface);
        transition: border-color .15s ease, box-shadow .15s ease;
        outline: none;
        box-sizing: border-box;
    }
    .field-input:focus {
        border-color: #2A6B4F;
        box-shadow: 0 0 0 3px rgba(42, 107, 79, .1);
    }
    .field-input::placeholder { color: var(--color-muted); opacity: .65; }

    /* Password wrapper */
    .pass-wrap { position: relative; }
    .pass-wrap .field-input { padding-right: 2.5rem; }
    .pass-toggle {
        position: absolute;
        right: .8rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        padding: 0;
        color: var(--color-muted);
        cursor: pointer;
        font-size: .9rem;
        line-height: 1;
        transition: color .15s;
    }
    .pass-toggle:hover { color: var(--color-text); }

    /* Remember / forgot row */
    .meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    .meta-row label {
        font-size: .8rem;
        color: var(--color-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .meta-row a {
        font-size: .78rem;
        color: #2A6B4F;
        text-decoration: none;
        font-weight: 500;
    }
    .meta-row a:hover { text-decoration: underline; }

    /* Buttons */
    .btn-primary-dark {
        display: block;
        width: 100%;
        background-color: var(--color-text);
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: .7rem;
        font-size: .88rem;
        font-weight: 600;
        font-family: var(--font-body);
        cursor: pointer;
        transition: background-color .15s ease;
        text-align: center;
    }
    .btn-primary-dark:hover { background-color: #333; }
    .btn-primary-dark:active { transform: scale(0.985); }

    .btn-secondary-outline {
        display: block;
        width: 100%;
        background-color: transparent;
        color: var(--color-text);
        border: 1px solid var(--color-border);
        border-radius: 7px;
        padding: .65rem;
        font-size: .86rem;
        font-weight: 500;
        font-family: var(--font-body);
        cursor: pointer;
        transition: border-color .15s, background-color .15s;
        text-decoration: none;
        text-align: center;
    }
    .btn-secondary-outline:hover {
        border-color: #2A6B4F;
        color: #2A6B4F;
        background-color: rgba(42,107,79,.04);
    }

    /* Divider */
    .divider-or {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin: .9rem 0;
    }
    .divider-or::before, .divider-or::after {
        content: '';
        flex: 1;
        height: 1px;
        background-color: var(--color-border);
    }
    .divider-or span {
        font-size: .7rem;
        color: var(--color-muted);
        font-weight: 500;
        letter-spacing: .06em;
    }

    /* Alerts */
    .flash-alert {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        border-radius: 7px;
        border: 1px solid;
        padding: .6rem .85rem;
        font-size: .82rem;
        margin-bottom: 1.1rem;
        line-height: 1.45;
    }
    .flash-alert i { flex-shrink: 0; margin-top: 1px; }
    .flash-error  { background: #FDEBEC; border-color: #F5C6CB; color: #9F2F2D; }
    .flash-success { background: #EDF3EC; border-color: #B7D9BA; color: #346538; }

    /* ─── Right Panel ─── */
    .login-right {
        display: none;
        flex: 0 0 50%;
        max-width: 50%;
        position: relative;
        background-image: url('{{ asset('images/bg-thursina.png') }}');
        background-size: cover;
        background-position: center;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 3rem;
    }
    @media (min-width: 992px) { .login-right { display: flex; } }
    .login-right::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(16, 32, 22, 0.74);
    }
    .right-content {
        position: relative;
        z-index: 1;
        max-width: 380px;
        color: #fff;
    }
    .live-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.16);
        border-radius: 5px;
        padding: .28rem .65rem;
        font-size: .73rem;
        font-weight: 500;
        color: rgba(255,255,255,.88);
        margin-bottom: 1.25rem;
    }
    .live-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #4DA37A;
        animation: pulse-live 2s infinite;
    }
    @keyframes pulse-live {
        0%,100% { box-shadow: 0 0 0 0 rgba(77,163,122,.4); }
        50% { box-shadow: 0 0 0 5px rgba(77,163,122,0); }
    }
    .right-content h2 {
        font-family: var(--font-display);
        font-size: 1.85rem;
        font-weight: 700;
        letter-spacing: -0.04em;
        line-height: 1.15;
        margin-bottom: .75rem;
    }
    .right-content p {
        font-size: .88rem;
        opacity: .68;
        line-height: 1.65;
    }

    /* Footer */
    .login-footer {
        margin-top: 1.25rem;
        text-align: center;
        font-size: .74rem;
        color: var(--color-muted);
    }
</style>
@endpush

@section('content')
<div class="login-wrap">
    {{-- Left: Form --}}
    <div class="login-left">
        <div style="width:100%;max-width:400px">
            <div class="login-card">
                {{-- Brand --}}
                <div class="brand-icon">
                    <i class="bi bi-fingerprint"></i>
                </div>
                <h2 class="login-title">Presensi Thursina</h2>
                <p class="login-subtitle">Masuk untuk memantau kehadiran sholat santri.</p>

                {{-- Flash Messages --}}
                @if (session('error'))
                    <div class="flash-alert flash-error">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if (session('success'))
                    <div class="flash-alert flash-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="flash-alert flash-error">
                        <i class="bi bi-x-circle-fill"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="field-row">
                        <label class="field-label" for="email">Email</label>
                        <input type="email" class="field-input" id="email" name="email"
                            placeholder="nama@thursina.sch.id"
                            value="{{ old('email') }}" required autofocus>
                    </div>

                    <div class="field-row">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
                            <label class="field-label mb-0" for="password">Password</label>
                            <a href="#" style="font-size:.76rem;color:#2A6B4F;text-decoration:none;font-weight:500">Lupa password?</a>
                        </div>
                        <div class="pass-wrap">
                            <input type="password" class="field-input" id="password" name="password"
                                placeholder="••••••••" required>
                            <button type="button" class="pass-toggle" id="togglePassword" aria-label="Tampilkan password">
                                <i class="bi bi-eye-slash" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="meta-row">
                        <label for="rememberMe">
                            <input type="checkbox" id="rememberMe" name="remember"
                                style="cursor:pointer;accent-color:#2A6B4F;border-radius:3px">
                            Ingat saya
                        </label>
                    </div>

                    <button type="submit" class="btn-primary-dark mb-2">Masuk</button>

                    <div class="divider-or"><span>atau</span></div>

                    <a href="{{ route('santri.create') }}" class="btn-secondary-outline">Daftar akun santri baru</a>
                </form>
            </div>

            <p class="login-footer">© 2026 Thursina IIBS. All rights reserved.</p>
        </div>
    </div>

    {{-- Right: Visual --}}
    <div class="login-right">
        <div class="right-content">
            <div class="live-badge">
                <span class="live-dot"></span>
                Sistem aktif
            </div>
            <h2>Pantau ibadah santri secara real-time</h2>
            <p>Presensi sholat berbasis fingerspot terintegrasi. Data hadir, izin, dan alfa tercatat otomatis untuk setiap waktu sholat.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById('togglePassword');
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');

        if (btn && input && icon) {
            btn.addEventListener('click', function () {
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                icon.className = isPass ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }
    });
</script>
@endpush
@endsection
