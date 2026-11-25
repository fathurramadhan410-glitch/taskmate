@extends('layouts.app')

@section('title', 'Manajemen Akun')
@section('subtitle', 'Kelola akun mahasiswa & administrator TaskMate')

@section('content')

<div class="page-toolbar">
    <span class="toolbar-count">
        @if(request('q'))
            {{ $akuns->count() }} dari {{ $totalSemua }} akun cocok dengan pencarian
        @else
            {{ $totalSemua }} akun terdaftar
        @endif
    </span>
    <div class="toolbar-right">
        <form class="toolbar-search" method="GET" action="{{ route('admin.akun') }}">
            <div class="input-icon">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..." aria-label="Cari akun">
            </div>
        </form>
        <button type="button" class="btn btn-primary" id="btnTambahAkun">Tambah Akun</button>
    </div>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Pengguna</th>
            <th>Gmail</th>
            <th>Peran Sistem</th>
            <th>Workspace yang Diikuti</th>
            <th>Status Akun</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($akuns as $u)
            <tr>
                <td>
                    <div class="user-cell">
                        <x-avatar :user="$u" size="sm"/>
                        <strong>{{ $u->nama }}</strong>
                    </div>
                </td>
                <td><span class="cell-mail">{{ $u->email }}</span></td>
                <td>
                    @if(($u->role ?? 'mahasiswa') === 'admin')
                        <span class="badge badge-version">Administrator</span>
                    @else
                        <span class="badge badge-member">Mahasiswa</span>
                    @endif
                </td>
                <td>
                    <div class="wp-list">
                        @forelse (($wpMap->get($u->id_user) ?? collect()) as $w)
                            <div class="wp-item">
                                <span class="wp-nama">{{ $w->nama }}</span>
                                @if(($w->peran ?? 'member') === 'lead')
                                    <span class="wp-role role-lead">Project Manager</span>
                                @else
                                    <span class="wp-role role-member">Anggota</span>
                                @endif
                            </div>
                        @empty
                            <span class="wp-empty">Belum mengikuti Workspace</span>
                        @endforelse
                    </div>
                </td>
                <td>
                    @if(($u->status_akun ?? 'aktif') === 'aktif')
                        <span class="badge" style="background:#E7F6EE;border:1px solid #BCE5CB;color:#1B7A3D;">Aktif</span>
                    @elseif($u->status_akun === 'diblokir')
                        <span class="badge" style="background:#FDECEC;border:1px solid #F2C4C4;color:#991B1B;">Diblokir</span>
                        @if(!empty($u->alasan_blokir))
                            <small style="display:block;margin-top:4px;font-size:11.5px;color:#A34A4A;max-width:180px;">{{ $u->alasan_blokir }}</small>
                        @endif
                    @elseif($u->status_akun === 'terkunci')
                        <span class="badge" style="background:#FDF6E3;border:1px solid #EFDCA8;color:#92400E;">Terkunci</span>
                    @else
                        <span class="badge">{{ $u->status_akun }}</span>
                    @endif
                </td>
                <td>
                    @if(($u->status_akun ?? 'aktif') === 'diblokir')
                        <form method="POST" action="{{ route('admin.akun.buka', $u->id_user) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">Buka Blokir</button>
                        </form>
                    @else
                        <button type="button" class="btn btn-outline btn-sm btn-blokir"
                                data-url="{{ route('admin.akun.blokir', $u->id_user) }}"
                                data-email="{{ $u->email }}">Blokir</button>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#6B7280;padding:32px;">
                    @if(request('q'))
                        Tidak ada akun yang cocok dengan "{{ request('q') }}".
                    @else
                        Belum ada akun terdaftar.
                    @endif
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- ================= MODAL TAMBAH AKUN ================= --}}
<div class="modal-overlay" id="modalTambahAkun" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulTambahAkun">
        <div class="modal-head">
            <h3 id="judulTambahAkun">Tambah Akun</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.akun.store') }}">
            @csrf
            <div class="form-group">
                <label for="fNama">Nama Lengkap</label>
                <input id="fNama" type="text" name="nama" value="{{ old('nama') }}" required maxlength="100">
            </div>
            <div class="form-group">
                <label for="fEmail">Email Gmail</label>
                <input id="fEmail" type="email" name="email" value="{{ old('email') }}" required placeholder="nama@gmail.com">
            </div>
            <div class="form-group">
                <label for="fRole">Peran Sistem</label>
                <select id="fRole" name="role" required>
                    <option value="mahasiswa" {{ old('role') === 'mahasiswa' ? 'selected' : '' }}>Mahasiswa</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                </select>
            </div>
            <div class="form-group">
                <label for="fSandi">Sandi Awal</label>
                <input id="fSandi" type="password" name="sandi" required minlength="6" placeholder="Minimal 6 karakter">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;">
                <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

{{-- ================= MODAL BLOKIR (dengan alasan) ================= --}}
<div class="modal-overlay" id="modalBlokir" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulBlokir">
        <div class="modal-head">
            <h3 id="judulBlokir">Blokir Akun</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form method="POST" id="formBlokir" action="">
            @csrf
            <p style="font-size:13px;color:#6B7280;margin:0 0 12px;">
                Akun <strong id="blokirEmail"></strong> tidak akan bisa login, dan alasan ini
                akan tampil pada panel merah saat dia mencoba masuk.
            </p>
            <div class="form-group">
                <label for="fAlasan">Alasan Blokir</label>
                <textarea id="fAlasan" name="alasan" rows="3" required
                          placeholder="Contoh: Melanggar ketentuan penggunaan TaskMate"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px;">
                <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Blokir Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Buka/tutup overlay modal (klik backdrop, tombol close, atau tombol Esc)
    function siapkanOverlay(id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.hidden = true;
        });
        overlay.querySelectorAll('[data-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () { overlay.hidden = true; });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') overlay.hidden = true;
        });
    }

    var btnTambah = document.getElementById('btnTambahAkun');
    var modalTambah = document.getElementById('modalTambahAkun');
    if (btnTambah && modalTambah) {
        btnTambah.addEventListener('click', function () { modalTambah.hidden = false; });
    }
    siapkanOverlay('modalTambahAkun');

    // Tombol Blokir per baris -> isi alamat form & email, lalu buka modal
    var modalBlokir = document.getElementById('modalBlokir');
    var formBlokir  = document.getElementById('formBlokir');
    var blokirEmail = document.getElementById('blokirEmail');
    document.querySelectorAll('.btn-blokir').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!modalBlokir || !formBlokir) return;
            formBlokir.action = btn.getAttribute('data-url');
            blokirEmail.textContent = btn.getAttribute('data-email');
            modalBlokir.hidden = false;
        });
    });
    siapkanOverlay('modalBlokir');
});
</script>

@endsection