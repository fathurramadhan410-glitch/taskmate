@extends('layouts.app')

@section('content')
<div class="admin-page">
    <div class="admin-head">
        <div>
            <h1 class="admin-title">Aplikasi SIAKAD &amp; E-Learning</h1>
            <p class="admin-sub">Kelola daftar aplikasi yang tampil di menu SIAKAD &amp; E-Learning.</p>
        </div>
        <a href="{{ route('siakad') }}" class="admin-btn admin-btn-ghost" target="_blank">Lihat Halaman SIAKAD</a>
    </div>

    <div class="admin-card admin-card-form">
        <h2 class="admin-card-title">{{ $diedit ? 'Edit Aplikasi: ' . $diedit->nama : 'Tambah Aplikasi Baru' }}</h2>
        <form method="POST" action="{{ $diedit ? route('admin.siakad.update', $diedit) : route('admin.siakad.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="admin-form-grid">
                <div class="admin-form-row">
                    <label class="admin-label">Nama Aplikasi</label>
                    <input type="text" name="nama" value="{{ old('nama', $diedit->nama ?? '') }}" class="admin-input" required>
                </div>
                <div class="admin-form-row">
                    <label class="admin-label">Tag / Label Kecil</label>
                    <input type="text" name="tag" value="{{ old('tag', $diedit->tag ?? '') }}" class="admin-input" placeholder="Contoh: E-Learning" required>
                </div>
                <div class="admin-form-row">
                    <label class="admin-label">Warna Tag</label>
                    <select name="warna" class="admin-input">
                        <option value="cyan" {{ old('warna', $diedit->warna ?? 'cyan') === 'cyan' ? 'selected' : '' }}>Cyan</option>
                        <option value="navy" {{ old('warna', $diedit->warna ?? 'cyan') === 'navy' ? 'selected' : '' }}>Navy</option>
                    </select>
                </div>
                <div class="admin-form-row">
                    <label class="admin-label">Urutan Tampil</label>
                    <input type="number" name="urutan" value="{{ old('urutan', $diedit->urutan ?? 0) }}" class="admin-input" min="0">
                </div>
                <div class="admin-form-row admin-form-row-full">
                    <label class="admin-label">URL Aplikasi</label>
                    <input type="url" name="url" value="{{ old('url', $diedit->url ?? '') }}" class="admin-input" placeholder="https://..." required>
                </div>
                <div class="admin-form-row admin-form-row-full">
                    <label class="admin-label">Keterangan Singkat</label>
                    <textarea name="caption" class="admin-input" rows="3" required>{{ old('caption', $diedit->caption ?? '') }}</textarea>
                </div>
                <div class="admin-form-row">
                    <label class="admin-label">Logo (opsional)</label>
                    <input type="file" name="logo" accept="image/*" class="admin-input">
                </div>
                <div class="admin-form-row">
                    <label class="admin-check">
                        <input type="checkbox" name="aktif" value="1" {{ old('aktif', $diedit ? $diedit->aktif : true) ? 'checked' : '' }}>
                        Tampilkan di halaman SIAKAD
                    </label>
                </div>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">{{ $diedit ? 'Simpan Perubahan' : 'Tambah Aplikasi' }}</button>
                @if($diedit)
                    <a href="{{ route('admin.siakad') }}" class="admin-btn admin-btn-ghost">Batal</a>
                @endif
            </div>
        </form>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Aplikasi</th>
                    <th>Tag</th>
                    <th>URL</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aplikasi as $app)
                    <tr>
                        <td>
                            <div class="admin-app">
                                @if($app->logo)
                                    <img src="{{ asset($app->logo) }}" alt="Logo {{ $app->nama }}" class="admin-app-logo">
                                @else
                                    <span class="admin-app-logo admin-app-logo-kosong">{{ strtoupper(mb_substr($app->nama, 0, 1)) }}</span>
                                @endif
                                <div class="admin-akun-nama">{{ $app->nama }}</div>
                            </div>
                        </td>
                        <td><span class="admin-badge {{ $app->warna === 'navy' ? 'admin-badge-navy' : 'admin-badge-cyan' }}">{{ $app->tag }}</span></td>
                        <td class="admin-url">{{ $app->url }}</td>
                        <td>{{ $app->urutan }}</td>
                        <td>
                            <span class="admin-badge {{ $app->aktif ? 'admin-badge-aktif' : 'admin-badge-diblokir' }}">{{ $app->aktif ? 'Tampil' : 'Disembunyikan' }}</span>
                        </td>
                        <td>
                            <div class="admin-aksi">
                                <a href="{{ route('admin.siakad', ['edit' => $app->id]) }}" class="admin-btn admin-btn-ghost">Edit</a>
                                <form method="POST" action="{{ route('admin.siakad.hapus', $app) }}" onsubmit="return confirm('Hapus aplikasi {{ $app->nama }}?')">
                                    @csrf
                                    <button type="submit" class="admin-btn admin-btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-kosong">Belum ada aplikasi. Tambahkan lewat form di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection