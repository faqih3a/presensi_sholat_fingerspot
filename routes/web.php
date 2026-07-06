<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\AsatidzController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SantriDashboardController;
use App\Http\Controllers\TesController;

// Auth routes (Login is now the root page)
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/', [AuthController::class, 'login'])->middleware('guest');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');


// Protected routes
Route::middleware(['auth'])->group(function () {
    // Shared routes (Available to all logged-in users)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Santri routes
    Route::middleware(['role:santri'])->group(function () {
        Route::get('/santri/dashboard', [SantriDashboardController::class, 'index'])->name('santri.dashboard');
        Route::get('/santri/dashboard/export', [SantriDashboardController::class, 'export'])->name('santri.dashboard.export');
        Route::get('/izin', [IzinController::class, 'index'])->name('izin.index');
        Route::get('/izin/create', [IzinController::class, 'create'])->name('izin.create');
        Route::post('/izin', [IzinController::class, 'store'])->name('izin.store');
    });

    // Admin & Asatidz Routes (Mosque Staff)
    Route::middleware(['role:admin,asatidz'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/kehadiran-sholat', [DashboardController::class, 'kehadiran'])->name('dashboard.kehadiran');
        Route::get('/kehadiran-sholat/export', [DashboardController::class, 'exportKehadiran'])->name('dashboard.kehadiran.export');
        
        Route::get('/santri', [SantriController::class, 'adminList'])->name('santri.index');
        Route::get('/santri/api/list', [SantriController::class, 'apiList'])->name('santri.api.list');
        Route::get('/santri/register', [SantriController::class, 'create'])->name('santri.create');
        Route::post('/santri/register', [SantriController::class, 'store'])->name('santri.store');
        Route::put('/santri/{santri}', [SantriController::class, 'update'])->name('santri.update');
        Route::delete('/santri/{santri}', [SantriController::class, 'destroy'])->name('santri.destroy');
        Route::post('/santri/sync-mesin', [SantriController::class, 'syncMesin'])->name('santri.sync-mesin');
        
        Route::get('/izin/manage', [IzinController::class, 'manage'])->name('izin.manage');
        Route::post('/izin/{izin}/status', [IzinController::class, 'updateStatus'])->name('izin.update-status');
        
        Route::post('/presensi/update-status', [PresensiController::class, 'updateStatus'])->name('presensi.update-status');
        Route::get('/presensi/latest-scans', [PresensiController::class, 'latestScans'])->name('presensi.latest-scans');
        Route::post('/presensi/delete', [PresensiController::class, 'deleteByParams'])->name('presensi.delete-by-params');
        Route::post('/presensi/bulk-delete', [PresensiController::class, 'bulkDelete'])->name('presensi.bulk-delete');
        Route::delete('/presensi/{presensi}', [PresensiController::class, 'destroy'])->name('presensi.destroy');


        // Tes Presensi (diluar waktu sholat)
        Route::get('/tes', [TesController::class, 'index'])->name('tes.index');
        Route::get('/tes/export', [TesController::class, 'exportTes'])->name('tes.export');
        Route::delete('/tes/{presensi}', [TesController::class, 'destroy'])->name('tes.destroy');
        Route::post('/tes/toggle', [TesController::class, 'toggle'])->name('tes.toggle');
    });

    // Admin-only routes (Mosque Staff Management)
    Route::middleware(['role:admin'])->group(function () {
        // Fallback redirect for old URL
        Route::redirect('/pengurus', '/asatidz');

        // Kelola Asatidz
        Route::get('/asatidz', [AsatidzController::class, 'index'])->name('asatidz.index');
        Route::get('/asatidz/create', [AsatidzController::class, 'create'])->name('asatidz.create');
        Route::post('/asatidz', [AsatidzController::class, 'store'])->name('asatidz.store');
        Route::put('/asatidz/{asatidz}', [AsatidzController::class, 'update'])->name('asatidz.update');
        Route::delete('/asatidz/{asatidz}', [AsatidzController::class, 'destroy'])->name('asatidz.destroy');

        // Kelola Admin
        Route::get('/admin-manage', [AdminController::class, 'index'])->name('admin-manage.index');
        Route::get('/admin-manage/create', [AdminController::class, 'create'])->name('admin-manage.create');
        Route::post('/admin-manage', [AdminController::class, 'store'])->name('admin-manage.store');
        Route::put('/admin-manage/{admin}', [AdminController::class, 'update'])->name('admin-manage.update');
        Route::delete('/admin-manage/{admin}', [AdminController::class, 'destroy'])->name('admin-manage.destroy');
    });
});

Route::get('/buat-storage-link', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return 'Storage link successfully created!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

