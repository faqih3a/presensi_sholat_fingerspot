<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" type="image/png" href="{{ asset('images/logo-thursina.png') }}" />
    <title>Presensi Thursina | @yield('title')</title>
    <!-- Google Fonts: Outfit + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --font-body: 'DM Sans', 'Helvetica Neue', Arial, sans-serif;
            --font-display: 'Outfit', 'Helvetica Neue', Arial, sans-serif;
            --color-bg: #F7F6F3;
            --color-surface: #FFFFFF;
            --color-border: #E8E6E0;
            --color-text: #1E1D1B;
            --color-muted: #7B7468;
            --color-accent: #2A6B4F;
            --color-accent-light: rgba(42, 107, 79, 0.08);
        }
        html { scroll-behavior: smooth; }
        body { 
            font-family: var(--font-body);
            background-color: var(--color-bg);
            color: var(--color-text);
        }
        #wrapper { overflow-x: hidden; }
        #sidebar-wrapper {
            min-height: 100vh;
            width: 280px;
            margin-left: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #ffffff;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1050;
            border-right: 1px solid #edf2f9;
            display: flex;
            flex-direction: column;
        }
        #sidebar-wrapper .sidebar-heading {
            padding: 1.5rem 1.25rem;
            font-size: 1rem;
            color: var(--color-text);
            background-color: var(--color-surface);
            font-weight: 700;
            font-family: var(--font-display);
            letter-spacing: -0.01em;
            white-space: nowrap;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        #sidebar-wrapper .list-group { width: 100%; padding: 0.75rem; }
        #sidebar-wrapper .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            background-color: transparent;
            color: var(--color-muted);
            display: flex;
            align-items: center;
            gap: 0.85rem;
            transition: background-color 0.18s ease, color 0.18s ease, padding 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 6px;
            margin-bottom: 2px;
            font-weight: 500;
            font-size: 0.88rem;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }
        #sidebar-wrapper .sidebar-text {
            transition: opacity 0.2s ease-in-out;
            opacity: 1;
            display: inline-block;
        }
        #sidebar-wrapper .list-group-item i {
            font-size: 1rem;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }
        #sidebar-wrapper .list-group-item:hover {
            color: var(--color-text);
            background-color: var(--color-bg);
        }
        #sidebar-wrapper .list-group-item.active {
            color: var(--color-accent);
            background-color: var(--color-accent-light);
            font-weight: 600;
        }
        #page-content-wrapper { 
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            padding-left: 0;
        }
        body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -280px; }
        
        /* Sidebar Responsive Logic */
        @media (min-width: 768px) {
            #page-content-wrapper { padding-left: 280px; }
            body.sb-sidenav-toggled #page-content-wrapper { padding-left: 0; }
            
            /* Sidebar Mini (Desktop) */
            body.sb-mini #sidebar-wrapper { width: 85px; }
            body.sb-mini #page-content-wrapper { padding-left: 85px; }
            
            body.sb-mini #sidebar-wrapper .sidebar-text { 
                opacity: 0;
                width: 0;
                display: none;
            }
            body.sb-mini #sidebar-wrapper .sidebar-heading div { 
                display: none !important; 
            }
            body.sb-mini #sidebar-wrapper .sidebar-heading { 
                padding: 1rem 0 !important; 
                min-height: 60px;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            body.sb-mini #sidebar-wrapper .sidebar-heading .btn-link {
                position: static;
                margin: 0 auto !important;
                padding: 0 !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                width: 40px !important;
                height: 40px !important;
            }
            body.sb-mini #sidebar-wrapper .list-group-item { 
                width: 50px !important;
                height: 50px !important;
                margin: 0.4rem auto !important;
                padding: 0 !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 0 !important;
            }
            body.sb-mini #sidebar-wrapper .list-group-item i { 
                margin: 0 !important; 
                font-size: 1.3rem;
                width: auto !important;
            }
            body.sb-mini #sidebar-wrapper .list-group-item.active {
                background-color: var(--color-accent-light);
                color: var(--color-accent);
            }
            body.sb-mini #sidebar-wrapper .mt-auto {
                padding: 1rem 0 !important;
                display: flex !important;
                justify-content: center !important;
            }
            body.sb-mini #sidebar-wrapper #darkModeBtn {
                margin: 0 auto !important;
                padding: 0 !important;
                width: 40px !important;
                height: 40px !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }
        }
        @media (max-width: 767.98px) {
            #sidebar-wrapper { margin-left: -280px; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
            body.sb-sidenav-toggled #sidebar-overlay { display: block; }
        }
        
        #sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
            z-index: 1040;
            backdrop-filter: blur(2px);
        }

        .navbar {
            padding: 0.85rem 1.75rem !important;
            min-height: 70px;
        }
        .navbar-light .navbar-nav .nav-link { color: #67748e; font-weight: 500; }
        .navbar-light .navbar-nav .nav-link:hover { color: #333; }
        
        /* Remove Bootstrap Caret */
        .dropdown-toggle::after {
            display: none !important;
        }
        
        /* Page Transitions */
        /* Content Fade-in */
        #page-content-wrapper {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Dark Mode Styles */
        body.dark-mode {
            --color-bg: #141412;
            --color-surface: #1C1B18;
            --color-border: #2E2C27;
            --color-text: #EAE8E0;
            --color-muted: #8C8880;
            --color-accent: #4DA37A;
            --color-accent-light: rgba(77, 163, 122, 0.1);
            background-color: var(--color-bg);
            color: var(--color-text);
        }
        body.dark-mode #sidebar-wrapper { background-color: var(--color-surface); border-right-color: var(--color-border); }
        body.dark-mode #sidebar-wrapper .sidebar-heading { background-color: var(--color-surface); color: var(--color-text); border-bottom-color: var(--color-border) !important; }
        body.dark-mode #sidebar-wrapper .list-group-item { color: var(--color-muted); }
        body.dark-mode #sidebar-wrapper .list-group-item:hover { color: var(--color-text); background-color: rgba(255,255,255,0.04); }
        body.dark-mode .navbar { background-color: var(--color-surface) !important; border-bottom-color: var(--color-border) !important; }
        body.dark-mode .navbar-light .navbar-nav .nav-link { color: var(--color-muted); }
        body.dark-mode .navbar-light .navbar-nav .nav-link:hover { color: var(--color-text); }
        body.dark-mode .card { background-color: var(--color-surface); border-color: var(--color-border); }
        body.dark-mode .card-header { background-color: var(--color-surface); border-bottom-color: var(--color-border); }
        body.dark-mode .text-gray-800, body.dark-mode .text-dark, body.dark-mode .text-primary { color: var(--color-text) !important; }
        
        /* Comprehensive Table Dark Mode */
        body.dark-mode .table { color: var(--color-text) !important; border-color: var(--color-border) !important; }
        body.dark-mode .table :not(caption) > * > * { background-color: transparent !important; color: inherit !important; border-color: var(--color-border) !important; }
        body.dark-mode .table thead th, body.dark-mode thead.bg-light, body.dark-mode .bg-light { background-color: rgba(255,255,255,0.04) !important; color: var(--color-text) !important; border-color: var(--color-border) !important; }
        body.dark-mode .table-striped tbody tr:nth-of-type(odd) { --bs-table-accent-bg: rgba(255, 255, 255, 0.025) !important; color: var(--color-text) !important; }
        body.dark-mode .table-hover tbody tr:hover { --bs-table-accent-bg: rgba(255, 255, 255, 0.05) !important; color: var(--color-text) !important; }
        body.dark-mode .table-bordered, body.dark-mode .table-bordered td, body.dark-mode .table-bordered th { border-color: var(--color-border) !important; }
        body.dark-mode .card-footer { background-color: var(--color-surface) !important; border-top-color: var(--color-border) !important; color: var(--color-muted) !important; }
        
        /* Buttons & Utilities in Dark Mode */
        body.dark-mode .btn-light, body.dark-mode .btn-white { background-color: rgba(255,255,255,0.06) !important; border-color: var(--color-border) !important; color: var(--color-text) !important; }
        body.dark-mode .btn-light:hover, body.dark-mode .btn-white:hover { background-color: rgba(255,255,255,0.1) !important; color: var(--color-text) !important; }
        body.dark-mode .bg-light { background-color: rgba(255,255,255,0.04) !important; }
        body.dark-mode .bg-white { background-color: var(--color-surface) !important; }
        
        body.dark-mode .dropdown-menu { background-color: var(--color-surface); border-color: var(--color-border); }
        body.dark-mode .dropdown-item { color: var(--color-text); }
        body.dark-mode .dropdown-item:hover { background-color: rgba(255,255,255,0.05); color: var(--color-text); }
        body.dark-mode .border-bottom { border-bottom-color: var(--color-border) !important; }
        body.dark-mode .border-top { border-top-color: var(--color-border) !important; }
        body.dark-mode .text-muted { color: var(--color-muted) !important; }
        body.dark-mode .bg-white { background-color: var(--color-surface) !important; }
        body.dark-mode .page-loader { background-color: #121212; }
        
        /* Form Elements for Dark Mode */
        body.dark-mode .form-control, body.dark-mode .form-select {
            background-color: #2c2c2c;
            color: #f8f9fa;
            border-color: #444;
        }
        body.dark-mode .form-control:focus, body.dark-mode .form-select:focus {
            background-color: #2c2c2c;
            color: #fff;
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        body.dark-mode .form-control::placeholder { color: #6c757d; }
        body.dark-mode .input-group-text, body.dark-mode .btn-outline-secondary {
            background-color: #1e1e1e;
            color: #adb5bd;
            border-color: #444;
        }
        
        /* Premium Select Styles */
        .premium-select-wrapper {
            position: relative;
        }
        .premium-select-btn {
            background-color: #fff;
            border: 1px solid #edf2f9;
            border-radius: 0.8rem;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: #4d5157;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
            cursor: pointer;
            width: 100%;
        }
        .premium-select-btn:hover {
            border-color: #198754;
            background-color: #f8f9fa;
        }
        .premium-select-btn:focus, .premium-select-btn.show {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
            outline: 0;
        }
        .premium-select-btn::after {
            display: none !important;
        }
        .premium-select-wrapper .dropdown-menu {
            width: 100%;
            margin-top: 5px !important;
        }
        .premium-select-wrapper .dropdown-item.active, 
        .premium-select-wrapper .dropdown-item:active {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        body.dark-mode .premium-select-btn {
            background-color: #2c2c2c;
            border-color: #444;
            color: #adb5bd;
        }
        body.dark-mode .premium-select-btn:hover {
            background-color: #333;
        }
        body.dark-mode .premium-select-wrapper .dropdown-menu {
            background-color: #2c2c2c;
        }

        /* Modern Select Styles */
        .form-select {
            border: 1px solid #edf2f9;
            border-radius: 0.6rem;
            padding: 0.5rem 2.25rem 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: #4d5157;
            transition: all 0.2s;
            cursor: pointer;
            background-color: #fff;
        }
        .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
            outline: 0;
        }
        
        /* Modern Dropdown Styles */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;
            border-radius: 1rem !important;
            padding: 0.5rem;
            margin-top: 10px !important;
            animation: dropdownFade 0.2s ease-out;
        }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-item {
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #67748e;
            transition: all 0.2s;
        }
        .dropdown-item:hover {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            transform: translateX(5px);
        }
        .dropdown-item i {
            font-size: 1rem;
            margin-right: 0.75rem;
        }
        
        /* Badge Soft Styles */
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
        
        body.dark-mode .form-select {
            background-color: rgba(255,255,255,0.05);
            border-color: var(--color-border);
            color: var(--color-text);
        }
        body.dark-mode .dropdown-menu {
            background-color: var(--color-surface);
            box-shadow: 0 8px 32px rgba(0,0,0,0.35) !important;
        }
        body.dark-mode .dropdown-item {
            color: var(--color-muted);
        }
        body.dark-mode .dropdown-item:hover {
            background-color: var(--color-accent-light);
            color: var(--color-accent);
        }

        body.dark-mode .btn-outline-secondary:hover { background-color: #333; color: #fff; }
        body.dark-mode .modal-content { background-color: #1e1e1e; border-color: #333; }
        body.dark-mode .modal-header, body.dark-mode .modal-footer { border-color: #333; }
        body.dark-mode .close, body.dark-mode .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        /* Mobile Bottom Navigation Bar Styles */
        /* Premium Utility Classes */
        .font-display { font-family: var(--font-display); }
        h1, h2, h3, h4, h5 { font-family: var(--font-display); letter-spacing: -0.02em; }
        .text-accent { color: var(--color-accent) !important; }
        .bg-surface { background-color: var(--color-surface) !important; }
        .border-theme { border-color: var(--color-border) !important; }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 65px;
            background-color: #ffffff;
            border-top: 1px solid #edf2f9;
            box-shadow: 0 -3px 15px rgba(0, 0, 0, 0.05);
            z-index: 1040;
            padding-bottom: env(safe-area-inset-bottom);
        }
        .bottom-nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-around;
            height: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #67748e;
            font-size: 0.65rem;
            font-weight: 700;
            width: 20%;
            height: 100%;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .bottom-nav-item i {
            font-size: 1.2rem;
            margin-bottom: 2px;
            padding: 4px 16px;
            border-radius: 20px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bottom-nav-item:hover {
            color: #198754;
        }
        .bottom-nav-item.active {
            color: #198754;
        }
        .bottom-nav-item.active i {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            transform: scale(1.05);
        }
        body.dark-mode .bottom-nav {
            background-color: #1e1e1e;
            border-top-color: #333;
            box-shadow: 0 -3px 15px rgba(0, 0, 0, 0.3);
        }
        body.dark-mode .bottom-nav-item {
            color: #adb5bd;
        }
        body.dark-mode .bottom-nav-item:hover, body.dark-mode .bottom-nav-item.active {
            color: #2dc57b;
        }
        body.dark-mode .bottom-nav-item.active i {
            background-color: rgba(45, 197, 123, 0.15);
            color: #2dc57b;
        }

        /* Responsiveness & Safe Area */
        @media (max-width: 767.98px) {
            body {
                padding-bottom: 75px !important; /* Space for bottom nav */
            }
            #sidebarToggle {
                display: none !important;
            }
            .container-fluid {
                padding: 1rem !important;
            }
            .chart-card {
                padding: 1.15rem !important;
            }
            .table:not(.no-responsive) {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Mobile Dropdown Fix to prevent pushing page content down */
        @media (max-width: 991.98px) {
            .navbar-nav .nav-item.dropdown {
                position: relative !important;
            }
            .navbar-nav .dropdown-menu {
                position: absolute !important;
                float: none;
                right: 0 !important;
                left: auto !important;
                top: 100% !important;
                z-index: 1060 !important;
            }
        }

        /* Page Loader */
        #page-loader {
            position: fixed;
            inset: 0;
            background-color: var(--color-bg, #F7F6F3);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.25s ease-out, visibility 0.25s ease-out;
        }
        #page-loader.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .page-loader-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--color-accent, #2A6B4F);
            animation: loader-bounce 1.2s ease-in-out infinite both;
        }
        .page-loader-dot:nth-child(1) { animation-delay: -0.32s; }
        .page-loader-dot:nth-child(2) { animation-delay: -0.16s; margin: 0 8px; }
        @keyframes loader-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }
        body.dark-mode #page-loader { background-color: var(--color-bg, #141412); }
    </style>
    @stack('styles')
</head>
<body>
    <script>
        // Apply dark mode immediately to avoid flash
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
    <!-- Page Loader -->
    <div id="page-loader">
        <div style="display:flex;align-items:center">
            <div class="page-loader-dot"></div>
            <div class="page-loader-dot"></div>
            <div class="page-loader-dot"></div>
        </div>
    </div>
    <!-- Global Page Content -->
    <div class="d-flex" id="wrapper">
        <!-- Overlay -->
        <div id="sidebar-overlay"></div>

        <!-- Sidebar-->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ asset('images/logo-thursina.png') }}" alt="Logo"
                        style="width:32px;height:32px;border-radius:7px;object-fit:contain;background:rgba(42,107,79,.07);padding:3px;flex-shrink:0">
                    <span class="sidebar-text" style="font-family:var(--font-display);font-weight:700;letter-spacing:-0.02em;color:var(--color-text)">Presensi</span>
                </div>
                <button class="btn btn-link text-muted p-0 d-none d-md-block" id="miniSidebarToggle">
                    <i class="bi bi-chevron-left" id="miniSidebarIcon"></i>
                </button>
            </div>
            <div class="list-group list-group-flush flex-grow-1">
                @auth
                    @if(auth()->user()->role === 'santri')
                        <a class="list-group-item list-group-item-action {{ request()->is('santri/dashboard') ? 'active' : '' }}" href="/santri/dashboard">
                            <i class="bi bi-grid-fill"></i>
                            <span class="sidebar-text">Dashboard Santri</span>
                        </a>
                        <a class="list-group-item list-group-item-action {{ request()->is('izin') || request()->is('izin/create') ? 'active' : '' }}" href="{{ route('izin.index') }}">
                            <i class="bi bi-envelope-open-fill"></i>
                            <span class="sidebar-text">Permohonan Izin</span>
                        </a>
                    @endif

                    @if(in_array(auth()->user()->role, ['admin', 'asatidz']))
                        <a class="list-group-item list-group-item-action {{ request()->is('dashboard') ? 'active' : '' }}" href="/dashboard">
                            <i class="bi bi-grid-fill"></i>
                            <span class="sidebar-text">Dashboard Admin</span>
                        </a>
                        
                        @if(auth()->user()->role === 'admin')
                            <a class="list-group-item list-group-item-action {{ request()->is('admin-manage*') ? 'active' : '' }}" href="{{ route('admin-manage.index') }}">
                                <i class="bi bi-shield-lock-fill"></i>
                                <span class="sidebar-text">Kelola Admin</span>
                            </a>
                            <a class="list-group-item list-group-item-action {{ request()->is('asatidz*') ? 'active' : '' }}" href="{{ route('asatidz.index') }}">
                                <i class="bi bi-person-workspace"></i>
                                <span class="sidebar-text">Kelola Asatidz</span>
                            </a>
                        @endif

                        <a class="list-group-item list-group-item-action {{ request()->is('santri*') ? 'active' : '' }}" href="{{ route('santri.index') }}">
                            <i class="bi bi-people-fill"></i>
                            <span class="sidebar-text">Kelola Santri</span>
                        </a>
                        
                        <a class="list-group-item list-group-item-action {{ request()->is('kehadiran-sholat') ? 'active' : '' }}" href="/kehadiran-sholat">
                            <i class="bi bi-clipboard2-check-fill"></i>
                            <span class="sidebar-text">Kehadiran Sholat</span>
                        </a>
                        
                        <a class="list-group-item list-group-item-action {{ request()->is('izin/manage') ? 'active' : '' }}" href="{{ route('izin.manage') }}">
                            <i class="bi bi-envelope-open-fill"></i>
                            <span class="sidebar-text">Kelola Izin</span>
                        </a>
                        
                        <a class="list-group-item list-group-item-action {{ request()->is('tes') ? 'active' : '' }}" href="{{ route('tes.index') }}">
                            <i class="bi bi-clipboard-plus-fill"></i>
                            <span class="sidebar-text">Tes Presensi</span>
                        </a>
                    @endif
                @endauth
            </div>
            
            <!-- Dark Mode Toggle -->
            <div class="mt-auto p-3 border-top text-center">
                <button class="btn btn-link text-secondary p-2 rounded-circle hover-bg-light" id="darkModeBtn" title="Toggle Dark Mode">
                    <i class="bi bi-moon-stars fs-5" id="darkModeIcon"></i>
                </button>
            </div>
        </div>

        
        <!-- Page content wrapper-->
        <div id="page-content-wrapper">
            <!-- Top navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-link text-dark p-0 border-0 me-3 d-md-none" id="sidebarToggle" type="button">
                            <i class="bi bi-list fs-3"></i>
                        </button>
                        
                        <!-- Search Bar -->
                        <form action="{{ route('dashboard.kehadiran') }}" method="GET" class="ms-md-3 me-auto d-none d-md-block no-loader" style="width: 320px;">
                            <div class="input-group bg-light rounded-3 border-0" style="height: 40px;">
                                <span class="input-group-text bg-transparent border-0 text-muted ps-3">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control bg-transparent border-0 ps-1" placeholder="Cari santri..." aria-label="Search" value="{{ request('search') }}" style="font-size: 0.9rem;">
                            </div>
                        </form>
                    </div>

                    <div class="d-flex align-items-center">
                        <ul class="navbar-nav ms-auto align-items-center flex-row gap-2">
                            @auth
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-0" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        @php
                                            $navAvatarUrl = null;
                                            if(auth()->user()->role === 'santri' && auth()->user()->santri && auth()->user()->santri->display_photo) {
                                                $navAvatarUrl = auth()->user()->santri->display_photo;
                                            } elseif (auth()->user()->avatar) {
                                                $navAvatarUrl = asset('storage/avatars/' . auth()->user()->avatar);
                                            }
                                        @endphp
                                        
                                        @if($navAvatarUrl)
                                            <img src="{{ $navAvatarUrl }}" alt="Profile" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #198754;">
                                        @else
                                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person-fill" style="font-size: 1.2rem;"></i>
                                            </div>
                                        @endif
                                        <span class="fw-semibold d-none d-sm-inline text-dark ms-1" style="font-size: 0.95rem;">{{ auth()->user()->name }}</span>
                                        <i class="bi bi-chevron-down small text-muted d-none d-sm-inline ms-1"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <div class="px-3 py-2">
                                            <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                            <div class="small text-muted">{{ auth()->user()->email }}</div>
                                        </div>
                                        <a class="dropdown-item {{ request()->is('profile') && !request()->has('tab') ? 'active' : '' }}" href="{{ route('profile.index') }}">
                                            <i class="bi bi-person-circle text-success"></i> Profil Saya
                                        </a>
                                        <a class="dropdown-item {{ request('tab') === 'security' ? 'active' : '' }}" href="{{ route('profile.index', ['tab' => 'security']) }}">
                                            <i class="bi bi-shield-lock text-info"></i> Keamanan Akun
                                        </a>
                                        <div class="dropdown-divider mx-2"></div>
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right"></i> Keluar
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @else
                                <li class="nav-item"><a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a></li>
                            @endauth
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Page content-->
            <div class="container-fluid p-4 d-flex flex-column" style="min-height: calc(100vh - 56px);">
                <div class="flex-grow-1">
                    @yield('content')
                </div>
                
            </div>
        </div>
    </div>
    @auth
    <!-- Bottom Navigation Bar for Mobile -->
    <div class="bottom-nav d-md-none">
        <div class="bottom-nav-inner">
            @if(auth()->user()->role === 'santri')
                <a href="/santri/dashboard" class="bottom-nav-item {{ request()->is('santri/dashboard*') ? 'active' : '' }}">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('izin.index') }}" class="bottom-nav-item {{ request()->is('izin*') ? 'active' : '' }}">
                    <i class="bi bi-envelope-open-fill"></i>
                    <span>Izin</span>
                </a>
                <a href="{{ route('profile.index') }}" class="bottom-nav-item {{ request()->is('profile*') ? 'active' : '' }}">
                    <i class="bi bi-person-fill"></i>
                    <span>Profil</span>
                </a>
            @else
                <a href="/dashboard" class="bottom-nav-item {{ request()->is('dashboard*') ? 'active' : '' }}">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('santri.index') }}" class="bottom-nav-item {{ request()->is('santri*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Santri</span>
                </a>
                <a href="/kehadiran-sholat" class="bottom-nav-item {{ request()->is('kehadiran-sholat*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard2-check-fill"></i>
                    <span>Riwayat</span>
                </a>
                <a href="{{ route('izin.manage') }}" class="bottom-nav-item {{ request()->is('izin/manage*') ? 'active' : '' }}">
                    <i class="bi bi-envelope-open-fill"></i>
                    <span>Izin</span>
                </a>
                <a href="{{ route('profile.index') }}" class="bottom-nav-item {{ request()->is('profile*') ? 'active' : '' }}">
                    <i class="bi bi-person-fill"></i>
                    <span>Profil</span>
                </a>
            @endif
        </div>
    </div>
    @endauth

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark Mode Logic
        const darkModeBtn = document.getElementById('darkModeBtn');
        const darkModeIcon = document.getElementById('darkModeIcon');
        
        const updateDarkModeUI = (isDark) => {
            if (isDark) {
                document.body.classList.add('dark-mode');
                if (darkModeIcon) darkModeIcon.className = 'bi bi-sun fs-5 text-warning';
            } else {
                document.body.classList.remove('dark-mode');
                if (darkModeIcon) darkModeIcon.className = 'bi bi-moon-stars fs-5';
            }
        };

        // Initialize state based on the preload script
        updateDarkModeUI(document.body.classList.contains('dark-mode'));

        if (darkModeBtn) {
            darkModeBtn.addEventListener('click', () => {
                const isDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                updateDarkModeUI(isDark);
            });
        }

        // Toggle the side navigation
        window.addEventListener('DOMContentLoaded', event => {
            const sidebarToggle = document.body.querySelector('#sidebarToggle');
            const miniSidebarToggle = document.getElementById('miniSidebarToggle');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const miniSidebarIcon = document.getElementById('miniSidebarIcon');

            // Apply mini state from localStorage
            if (localStorage.getItem('sidebar-mini') === 'true') {
                document.body.classList.add('sb-mini');
                if (miniSidebarIcon) miniSidebarIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
            }

            const toggleSidebar = (e) => {
                if (e) e.preventDefault();
                document.body.classList.toggle('sb-sidenav-toggled');
            };

            const toggleMiniSidebar = (e) => {
                if (e) e.preventDefault();
                document.body.classList.toggle('sb-mini');
                const isMini = document.body.classList.contains('sb-mini');
                localStorage.setItem('sidebar-mini', isMini);
                
                if (miniSidebarIcon) {
                    if (isMini) {
                        miniSidebarIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
                    } else {
                        miniSidebarIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
                    }
                }
            };

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', (e) => {
                    if (window.innerWidth >= 768) {
                        toggleMiniSidebar(e);
                    } else {
                        toggleSidebar(e);
                    }
                });
            }
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }
            if (miniSidebarToggle) {
                miniSidebarToggle.addEventListener('click', toggleMiniSidebar);
            }
        });

        // Handle Content Fade-in
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
            // Hide page loader
            const loader = document.getElementById('page-loader');
            if (loader) loader.classList.add('hidden');
        });

        // Show page loader on navigation
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a');
            if (!target || !target.href) return;
            const href = target.getAttribute('href');
            const hasToggle = target.hasAttribute('data-bs-toggle');
            const hasNoLoader = target.closest('.no-loader') || target.classList.contains('no-loader');
            if (href && !href.startsWith('#') && !href.startsWith('javascript') &&
                target.target !== '_blank' && !hasToggle && !hasNoLoader &&
                target.hostname === window.location.hostname) {
                const loader = document.getElementById('page-loader');
                if (loader) loader.classList.remove('hidden');
            }
        });

        // Show loader on form submit (kecuali no-loader)
        document.addEventListener('submit', function(e) {
            if (e.target.classList.contains('no-loader')) return;
            const loader = document.getElementById('page-loader');
            if (loader) loader.classList.remove('hidden');
        });

        // Handle browser back button (bfcache)
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                document.body.classList.add('loaded');
                const loader = document.getElementById('page-loader');
                if (loader) loader.classList.add('hidden');
            }
        });
    </script>

    @auth
    <!-- Auto-Logout due to Inactivity -->
    <div class="modal fade" id="idleTimeoutModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="idleTimeoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title fw-bold" id="idleTimeoutModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan Sesi Berakhir
                    </h5>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-3">Anda tidak melakukan aktivitas selama beberapa waktu. Demi keamanan, Anda akan dikeluarkan secara otomatis dalam:</p>
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="bg-light border rounded px-4 py-3 text-center" style="min-width: 120px;">
                            <span id="idleTimerCountdown" class="fs-2 fw-bold text-danger">60</span>
                            <div class="small text-muted">detik</div>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Gerakkan mouse, ketik, atau klik tombol di bawah untuk melanjutkan sesi Anda.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light border px-4 me-2" id="idleLogoutBtn">Keluar Sekarang</button>
                    <button type="button" class="btn btn-success px-4" id="idleKeepAliveBtn">Tetap Masuk</button>
                </div>
            </div>
        </div>
    </div>

    <form id="auto-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="auto_logout" value="1">
    </form>

    <script>
        (function() {
            const IDLE_LIMIT = 10 * 60 * 1000; // 10 menit
            const WARNING_LIMIT = 9 * 60 * 1000; // 9 menit (peringatan muncul)
            const STORAGE_KEY = 'last_activity_timestamp';
            const LOGOUT_FORM_ID = 'auto-logout-form';
            const MODAL_ID = 'idleTimeoutModal';
            const COUNTDOWN_ID = 'idleTimerCountdown';
            const KEEP_ALIVE_BTN_ID = 'idleKeepAliveBtn';
            const LOGOUT_BTN_ID = 'idleLogoutBtn';

            let warningModal = null;
            let isModalShown = false;
            let checkInterval = null;

            function updateActivity() {
                localStorage.setItem(STORAGE_KEY, Date.now().toString());
                if (isModalShown) {
                    hideWarningModal();
                }
            }

            function hideWarningModal() {
                if (warningModal) {
                    warningModal.hide();
                }
                isModalShown = false;
            }

            function showWarningModal() {
                if (!warningModal) {
                    const modalEl = document.getElementById(MODAL_ID);
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        warningModal = new bootstrap.Modal(modalEl);
                    }
                }
                if (warningModal && !isModalShown) {
                    warningModal.show();
                    isModalShown = true;
                }
            }

            function triggerLogout() {
                clearInterval(checkInterval);
                const form = document.getElementById(LOGOUT_FORM_ID);
                if (form) {
                    form.submit();
                } else {
                    window.location.href = '/logout';
                }
            }

            function checkIdleTime() {
                const lastActivity = parseInt(localStorage.getItem(STORAGE_KEY) || Date.now().toString(), 10);
                const elapsed = Date.now() - lastActivity;

                if (elapsed >= IDLE_LIMIT) {
                    triggerLogout();
                    return;
                }

                if (elapsed >= WARNING_LIMIT) {
                    showWarningModal();
                    const remainingSeconds = Math.max(0, Math.ceil((IDLE_LIMIT - elapsed) / 1000));
                    const countdownEl = document.getElementById(COUNTDOWN_ID);
                    if (countdownEl) {
                        countdownEl.textContent = remainingSeconds;
                    }
                } else {
                    if (isModalShown) {
                        hideWarningModal();
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                const modalEl = document.getElementById(MODAL_ID);
                if (!modalEl) return;

                const currentStored = localStorage.getItem(STORAGE_KEY);
                if (!currentStored || Date.now() - parseInt(currentStored, 10) > IDLE_LIMIT) {
                    updateActivity();
                }

                const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
                events.forEach(event => {
                    document.addEventListener(event, updateActivity, { passive: true });
                });

                const keepAliveBtn = document.getElementById(KEEP_ALIVE_BTN_ID);
                if (keepAliveBtn) {
                    keepAliveBtn.addEventListener('click', updateActivity);
                }

                const logoutBtn = document.getElementById(LOGOUT_BTN_ID);
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', triggerLogout);
                }

                checkInterval = setInterval(checkIdleTime, 1000);
            });
        })();
    </script>
    @endauth

    @include('partials.presensi-actions-modal')
    @stack('scripts')
</body>
</html>
