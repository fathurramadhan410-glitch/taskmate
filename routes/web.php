<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Web TaskMate
|--------------------------------------------------------------------------
*/

/* ============================== PUBLIK ============================== */

Route::get('/', [DashboardController::class, 'landing'])->name('landing');

// Halaman undangan via link bertanda tangan (bisa diakses sebelum login,
// akan diarahkan ke login terlebih dahulu lalu kembali otomatis)
Route::get('/gabung/{kode}', [JoinController::class, 'show'])
    ->name('gabung.kode')->middleware(['signed', 'throttle:12,1']);
Route::post('/gabung/{kode}', [JoinController::class, 'confirm'])
    ->name('gabung.konfirmasi')->middleware(['signed', 'throttle:12,1']);

/* ============================ AUTENTIKASI ============================ */

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/masuk', [LoginController::class, 'login'])
        ->middleware('throttle:8,1')->name('login.attempt');

    Route::get('/daftar', [RegisterController::class, 'show'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store'])
        ->middleware('throttle:8,1')->name('register.store');
});

// Login Google (Socialite)
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

/* =============================== PENGGUNA =============================== */

Route::middleware('auth')->group(function () {

    Route::post('/keluar', [LoginController::class, 'logout'])->name('logout');

    // Dashboard & workspace
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/gabung-kode', [DashboardController::class, 'joinByCode'])
        ->middleware('throttle:6,1')->name('dashboard.gabung');
    Route::post('/workspace', [WorkspaceController::class, 'store'])->name('workspace.store');

    Route::middleware('workspace.member')->prefix('/workspace/{workspace}')->group(function () {
        Route::get('/', [WorkspaceController::class, 'show'])->name('workspace.show');
        Route::patch('/ubah', [WorkspaceController::class, 'update'])->name('workspace.update');
        Route::delete('/hapus', [WorkspaceController::class, 'destroy'])->name('workspace.destroy');
        Route::post('/undangan/atur', [WorkspaceController::class, 'aturUndangan'])->name('workspace.undangan');
        Route::post('/anggota/{member}/keluarkan', [WorkspaceController::class, 'keluarkanAnggota'])->name('workspace.keluarkan');

        // Kanban & tugas
        Route::get('/kanban', [KanbanController::class, 'show'])->name('kanban.show');
        Route::post('/tugas', [TaskController::class, 'store'])->name('tugas.store');
        Route::get('/tugas/{task}/detail', [TaskController::class, 'detail'])->name('tugas.detail');
        Route::get('/tugas/{task}/edit', [TaskController::class, 'editData'])->name('tugas.edit');
        Route::patch('/tugas/{task}', [TaskController::class, 'update'])->name('tugas.update');
        Route::delete('/tugas/{task}', [TaskController::class, 'destroy'])->name('tugas.destroy');
        Route::patch('/tugas/{task}/status', [KanbanController::class, 'move'])->name('kanban.move');

        // Repository & berkas
        Route::get('/repository', [RepositoryController::class, 'index'])->name('repository.index');
        Route::post('/repository', [RepositoryController::class, 'upload'])->name('repository.upload');
        Route::post('/repository/materi', [RepositoryController::class, 'uploadMateri'])->name('repository.materi');
        Route::delete('/berkas/{file}', [RepositoryController::class, 'destroy'])->name('berkas.hapus');

        // Chat realtime
        Route::get('/chat', [ChatController::class, 'show'])->name('chat.show');
        Route::get('/chat/pesan', [ChatController::class, 'fetch'])->name('chat.pesan');
        Route::post('/chat', [ChatController::class, 'send'])->name('chat.kirim');
    });

    // Unduh & pratinjau berkas (cek keanggotaan di controller)
    Route::get('/berkas/{version}/unduh', [RepositoryController::class, 'download'])->name('berkas.unduh');
    Route::get('/berkas/{version}/pratinjau', [RepositoryController::class, 'preview'])->name('berkas.pratinjau');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/badge', [NotificationController::class, 'badge'])->name('notifikasi.badge');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'bacaSemua'])->name('notifikasi.bacaSemua');
    Route::post('/notifikasi/{notifikasi}/baca', [NotificationController::class, 'baca'])->name('notifikasi.baca');

    // Profil akun
    Route::get('/profil', [ProfileController::class, 'show'])->name('profil.show');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::patch('/profil/kata-sandi', [ProfileController::class, 'updatePassword'])->name('profil.password');
    Route::post('/profil/foto', [ProfileController::class, 'updateAvatar'])->name('profil.avatar');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profil.destroy');
});
