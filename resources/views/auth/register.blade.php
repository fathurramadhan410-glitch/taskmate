@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
<div class="auth-split">
    <div class="auth-side">
        <div class="auth-side-inner">
            <a href="{{ route('landing') }}" class="auth-side-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate">
            </a>
            <h2>Satu Akun, Semua Kelompok</h2>
            <p class="auth-side-sub">
                Buat akun TaskMate dan kelola seluruh pekerjaan kelompok Anda: dari workspace,
                pembagian jobdesk, Kanban, repository draf, sampai pengingat deadline otomatis.
            </p>
            <ul class="auth-side-points">
                <li><x-icon name="check-circle"/> Gratis untuk seluruh mahasiswa</li>
                <li><x-icon name="check-circle"/> Bisa jadi Leader di satu workspace, Member di lainnya</li>
                <li><x-icon name="check-circle"/> Notifikasi lonceng + email pengingat</li>
                <li><x-icon name="check-circle"/> Data berkas hanya bisa diakses anggota workspace</li>
            </ul>
            <p class="auth-side-tag"><x-icon name="clock"/> Cepat · Transparan · Terpantau</p>
        </div>
        <div class="auth-side-deco" aria-hidden="true"></div>
    </div>

    <div class="auth-panel">
        <div class="auth-card">
            <div class="auth-head">
                <h1>Buat Akun Baru</h1>
                <p>Gratis dan hanya butuh satu menit.</p>
            </div>

            <a href="{{ route('google.redirect') }}" class="btn btn-google btn-block" title="Daftar menggunakan akun Google">
                <svg class="icon" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                Daftar dengan Google
            </a>

            <div class="auth-divider"><span>atau daftar dengan email</span></div>

            <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                @csrf
                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <div class="input-icon">
                        <x-icon name="user"/>
                        <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required autofocus placeholder="cth: Fathur Ramadhan">
                    </div>
                    @error('nama')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-icon">
                        <x-icon name="mail"/>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="nama@student.poliban.ac.id">
                    </div>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <div class="input-icon">
                        <x-icon name="lock"/>
                        <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter" data-password-toggle>
                        <button type="button" class="input-eye" aria-label="Tampilkan kata sandi"><x-icon name="eye"/></button>
                    </div>
                    @error('password')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Ulangi Kata Sandi</label>
                    <div class="input-icon">
                        <x-icon name="lock"/>
                        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi kata sandi">
                    </div>
                    @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block">Daftar Sekarang</button>
            </form>

            <p class="auth-alt">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
        </div>

        <a href="{{ route('landing') }}" class="auth-back"><x-icon name="chevron-left"/> Kembali ke halaman utama</a>
    </div>
</div>
@endsection
