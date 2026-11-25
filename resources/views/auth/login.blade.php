@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<style>
    /* Pesan status login INLINE di dalam form (bukan pop-up).
       Warna ditulis langsung agar tidak bergantung variabel CSS. */
    .auth-alert {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 14px 16px; border-radius: 14px; margin-bottom: 18px;
        line-height: 1.6; animation: authAlertIn .3s ease;
    }
    .auth-alert .aa-ico {
        flex: none; width: 34px; height: 34px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .auth-alert .aa-ico .icon { width: 16px; height: 16px; }
    .auth-alert b { display: block; font-size: 13.5px; margin-bottom: 2px; }
    .auth-alert p { font-size: 12.5px; margin: 0; }
    .auth-alert.aa-merah { background: #FEF2F2; border: 1px solid #F2C4C4; }
    .auth-alert.aa-merah .aa-ico { background: #F8D7D7; color: #B91C1C; }
    .auth-alert.aa-merah b { color: #991B1B; }
    .auth-alert.aa-merah p { color: #A34A4A; }
    @keyframes authAlertIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
</style>

<div class="auth-split">

    {{-- ================= PANEL VISUAL (KIRI) ================= --}}
    <aside class="auth-visual">
        <div class="auth-visual-inner">
            <a href="{{ route('landing') }}" class="av-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate">
                <span>TaskMate</span>
            </a>

            <h2 class="av-title">
                Belajar, ngerjain tugas &amp; berkolaborasi
                dalam <em>Satu Platform</em>
            </h2>
            <p class="av-sub">
                Dari tugas harian sampai proyek akhir — bagi jobdesk, pantau progres di Kanban,
                dan kumpulkan draf final bersama seluruh anggota kelompokmu.
            </p>

            <div class="av-photo-wrap">
                <img src="{{ asset('images/auth-hero.png') }}"
                     alt="Siswa-siswi Indonesia dari berbagai jenjang berkolaborasi mengerjakan tugas bersama"
                     class="av-photo">

                {{-- Chip: aktivitas kanban (kecil, bagian atas) --}}
                <div class="av-float av-chip av-chip-kanban" aria-hidden="true">
                    <span class="av-chip-ico ico-green"><x-icon name="check-circle"/></span>
                    <div>
                        <b>Kanban diperbarui</b>
                        <span>Desain mockup pindah ke Review</span>
                    </div>
                </div>

                {{-- Chip: pengingat deadline --}}
                <div class="av-float av-chip av-chip-pengingat" aria-hidden="true">
                    <span class="av-chip-ico ico-amber"><x-icon name="bell"/></span>
                    <div>
                        <b>Pengingat H-1</b>
                        <span>Bab 2 dikumpulkan besok!</span>
                    </div>
                </div>
            </div>

            <div class="av-footer">
                <div class="av-ava-stack" aria-hidden="true">
                    <span class="avs-1">D</span><span class="avs-2">A</span><span class="avs-3">B</span><span class="avs-4">+</span>
                </div>
                <p>
                    Dipakai kelompok belajar dari <b>SMP, SMA/SMK</b>, sampai <b>perguruan tinggi</b> —
                    satu platform interaktif untuk semua jenjang pelajar.
                </p>
            </div>
        </div>
        <div class="av-deco av-deco-1" aria-hidden="true"></div>
        <div class="av-deco av-deco-2" aria-hidden="true"></div>
    </aside>

    {{-- ================= PANEL FORM (KANAN) ================= --}}
    <main class="auth-panel">
        <div class="auth-card">
            <a href="{{ route('landing') }}" class="auth-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate">
            </a>

            <div class="auth-head">
                <h1>Masuk ke TaskMate</h1>
                <p>Satu akun untuk semua tugas &amp; proyek kelompokmu.</p>
            </div>

            <a href="{{ route('google.redirect') }}" class="btn btn-google btn-block" title="Masuk menggunakan akun Google">
                <svg class="icon" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                Masuk dengan Google
            </a>

            <div class="auth-divider"><span>atau masuk dengan Email</span></div>

            <form method="POST" action="{{ route('login.attempt') }}" class="auth-form">
                @csrf

                {{-- ===== Pesan kesalahan login: INLINE di dalam form ===== --}}
                @if (session('gagal'))
                    <div class="auth-alert aa-merah" role="alert">
                        <span class="aa-ico"><x-icon name="alert"/></span>
                        <div>
                            <b>Login tidak berhasil</b>
                            <p>{{ session('gagal') }}</p>
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-icon">
                        <x-icon name="mail"/>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@student.poliban.ac.id">
                    </div>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <div class="input-icon">
                        <x-icon name="lock"/>
                        <input type="password" id="password" name="password" required placeholder="Masukkan kata sandimu" data-password-toggle>
                        <button type="button" class="input-eye" aria-label="Tampilkan kata sandi"><x-icon name="eye"/></button>
                    </div>
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <label class="checkbox auth-remember">
                    <input type="checkbox" name="ingat" value="1">
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <button type="submit" class="btn btn-primary btn-lg btn-block auth-submit">Masuk ke TaskMate</button>
            </form>

            <p class="auth-alt">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>

            <p class="auth-legal">
                Dengan masuk, kamu menyetujui Ketentuan Layanan &amp; Kebijakan Privasi TaskMate.
            </p>
        </div>

        <a href="{{ route('landing') }}" class="auth-back"><x-icon name="chevron-left"/> Kembali ke halaman utama</a>

        <p class="auth-dev">Dikembangkan oleh Kelompok 5 · Sistem Informasi Kota Cerdas — Politeknik Negeri Banjarmasin</p>
    </main>

</div>
@endsection