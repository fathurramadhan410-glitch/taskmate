@extends('layouts.app')

@section('content')
<div class="admin-page">
    <div class="admin-head">
        <div>
            <h1 class="admin-title">Tambah Akun</h1>
            <p class="admin-sub">Buat akun baru untuk mahasiswa atau admin lain.</p>
        </div>
        <a href="{{ route('admin.akun') }}" class="admin-btn admin-btn-ghost">&larr; Daftar Akun</a>
    </div>

    <div class="admin-card admin-card-form">
        <form method="POST" action="{{ route('admin.akun.store') }}">
            @csrf
            <div class="admin-form-grid">
                <div class="admin-form-row admin-form-row-full">
                    <label class="admin-label">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="admin-input" required>
                </div>
                <div class="admin-form-row admin-form-row-full">
                    <label class="admin-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="admin-input" required>
                </div>
                <div class="admin-form-row admin-form-row-full">
                    <label class="admin-label">Sandi (min. 6 karakter)</label>
                    <input type="text" name="password" class="admin-input" required minlength="6">
                </div>
                <div class="admin-form-row admin-form-row-full">
                    <label class="admin-label">Role</label>
                    <select name="role" class="admin-input">
                        <option value="mahasiswa" {{ old('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>
@endsection