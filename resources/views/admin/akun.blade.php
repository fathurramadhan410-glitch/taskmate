@extends('layouts.app')

@section('title', 'Manajemen Akun')
@section('subtitle', 'Kelola akun mahasiswa & administrator TaskMate')

@section('content')
@php
    $akuns = $akuns ?? collect();
    $wpMap = $wpMap ?? collect();
    $q = $q ?? request('q');
    $totalSemua = (int) ($totalSemua ?? $akuns->count());
    $totalAdmin = (int) ($totalAdmin ?? 0);
    $totalMahasiswa = (int) ($totalMahasiswa ?? 0);
    $totalAktif = (int) ($totalAktif ?? 0);
    $totalBlokir = (int) ($totalBlokir ?? 0);
@endphp

<style>
    /* ====== Halaman Manajemen Akun — semua kelas baru berawalan akunadm- ====== */

    /* ---- Kartu statistik ---- */
    .akunadm-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px;}
    .akunadm-stat{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;}
    .akunadm-stat-top{display:flex;align-items:center;gap:8px;margin-bottom:8px;}
    .akunadm-dot{width:8px;height:8px;border-radius:999px;flex-shrink:0;}
    .akunadm-dot-total{background:#111827;}
    .akunadm-dot-admin{background:#b45309;}
    .akunadm-dot-anggota{background:#0f766e;}
    .akunadm-dot-aktif{background:#059669;}
    .akunadm-dot-blokir{background:#dc2626;}
    .akunadm-stat-label{font-size:11.5px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#6b7280;}
    .akunadm-stat-value{font-size:26px;font-weight:700;color:#111827;line-height:1.1;}

    /* ---- Tabel ---- */
    .akunadm-tablewrap{overflow-x:auto;margin-top:4px;}
    .akunadm-role{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;line-height:1.4;}
    .akunadm-role-admin{background:#111827;color:#fff;}
    .akunadm-role-anggota{background:#f3f4f6;color:#374151;}
    .akunadm-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;line-height:1.4;}
    .akunadm-pill-aktif{background:#ecfdf5;color:#047857;}
    .akunadm-pill-blokir{background:#fef2f2;color:#b91c1c;}
    .akunadm-alasan{font-size:12px;color:#6b7280;margin-top:4px;max-width:280px;line-height:1.45;}
    .akunadm-th-aksi,.akunadm-td-aksi{text-align:right;white-space:nowrap;}
    .akunadm-kosong{text-align:center;color:#6b7280;padding:28px 12px;}

    /* ---- Modal (anti-overflow, gaya polos) ---- */
    .akunadm-overlay{position:fixed;inset:0;z-index:1000;background:rgba(17,24,39,.55);display:none;}
    .akunadm-overlay.is-aktif{display:flex;align-items:flex-end;justify-content:center;}
    .akunadm-modal{background:#fff;width:100%;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;border-radius:16px 16px 0 0;box-shadow:0 24px 60px rgba(17,24,39,.28);}
    .akunadm-modal-sm{max-width:440px;}
    .akunadm-modal-lg{max-width:560px;}
    @media (min-width:640px){
        .akunadm-overlay.is-aktif{align-items:center;padding:16px;}
        .akunadm-modal{max-height:calc(100vh - 32px);border-radius:14px;}
    }
    .akunadm-formwrap{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;}
    .akunadm-modal-head{flex-shrink:0;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid #f3f4f6;}
    .akunadm-modal-head h3{margin:0;font-size:15.5px;font-weight:700;color:#111827;}
    .akunadm-modal-sub{margin:2px 0 0;font-size:12.5px;color:#6b7280;}
    .akunadm-x{flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:0;background:transparent;color:#6b7280;cursor:pointer;transition:background-color .15s ease,color .15s ease;}
    .akunadm-x:hover{background:#f3f4f6;color:#111827;}
    .akunadm-modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:18px 20px;-webkit-overflow-scrolling:touch;}
    .akunadm-modal-foot{flex-shrink:0;display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid #f3f4f6;background:#fff;}

    /* ---- Isi form & modal ---- */
    .akunadm-field{margin-bottom:14px;}
    .akunadm-field:last-child{margin-bottom:0;}
    .akunadm-field label{display:block;font-size:13px;font-weight:600;color:#111827;margin-bottom:6px;}
    .akunadm-field label b{color:#dc2626;font-weight:700;}
    .akunadm-field input,.akunadm-field select,.akunadm-field textarea{display:block;width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;color:#111827;background:#fff;font-family:inherit;transition:border-color .15s ease,box-shadow .15s ease;}
    .akunadm-field textarea{resize:vertical;min-height:110px;line-height:1.5;}
    .akunadm-field input:focus,.akunadm-field select:focus,.akunadm-field textarea:focus{outline:none;border-color:#111827;box-shadow:0 0 0 3px rgba(17,24,39,.08);}
    .akunadm-field input::placeholder,.akunadm-field textarea::placeholder{color:#9ca3af;}
    .akunadm-hint{display:block;font-size:12px;color:#6b7280;margin-top:6px;}
    .akunadm-errors{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;margin-bottom:16px;}
    .akunadm-errors strong{display:block;font-size:13px;color:#b91c1c;margin-bottom:4px;}
    .akunadm-errors ul{margin:0;padding-left:18px;}
    .akunadm-errors li{font-size:13px;color:#b91c1c;}

    /* ---- Baris akun pada modal blokir ---- */
    .akunadm-akunrow{display:flex;align-items:center;gap:12px;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:16px;}
    .akunadm-akunawal{flex-shrink:0;width:40px;height:40px;border-radius:999px;background:#f3f4f6;color:#111827;font-weight:700;font-size:15px;display:inline-flex;align-items:center;justify-content:center;}
    .akunadm-akuninfo{flex:1 1 auto;min-width:0;}
    .akunadm-akuninfo strong{display:block;font-size:14px;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .akunadm-akuninfo span{display:block;font-size:12.5px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px;}
    .akunadm-status-aktif{flex-shrink:0;font-size:12px;font-weight:700;color:#059669;}

    /* ---- Tombol modal ---- */
    .akunadm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;border:1px solid transparent;font-size:14px;font-weight:600;line-height:1.2;font-family:inherit;cursor:pointer;text-decoration:none;transition:background-color .15s ease,border-color .15s ease,color .15s ease;}
    .akunadm-btn-dark{background:#111827;color:#fff;}
    .akunadm-btn-dark:hover{background:#1f2937;}
    .akunadm-btn-outline{background:#fff;color:#111827;border-color:#d1d5db;}
    .akunadm-btn-outline:hover{background:#f9fafb;}
    .akunadm-btn-red{background:#dc2626;color:#fff;}
    .akunadm-btn-red:hover{background:#b91c1c;}
</style>

{{-- Kartu statistik akun --}}
<div class="akunadm-stats">
    <div class="akunadm-stat">
        <div class="akunadm-stat-top"><span class="akunadm-dot akunadm-dot-total"></span><span class="akunadm-stat-label">Total Akun</span></div>
        <div class="akunadm-stat-value">{{ $totalSemua }}</div>
    </div>
    <div class="akunadm-stat">
        <div class="akunadm-stat-top"><span class="akunadm-dot akunadm-dot-admin"></span><span class="akunadm-stat-label">Total Admin</span></div>
        <div class="akunadm-stat-value">{{ $totalAdmin }}</div>
    </div>
    <div class="akunadm-stat">
        <div class="akunadm-stat-top"><span class="akunadm-dot akunadm-dot-anggota"></span><span class="akunadm-stat-label">Total Anggota</span></div>
        <div class="akunadm-stat-value">{{ $totalMahasiswa }}</div>
    </div>
    <div class="akunadm-stat">
        <div class="akunadm-stat-top"><span class="akunadm-dot akunadm-dot-aktif"></span><span class="akunadm-stat-label">Akun Aktif</span></div>
        <div class="akunadm-stat-value">{{ $totalAktif }}</div>
    </div>
    <div class="akunadm-stat">
        <div class="akunadm-stat-top"><span class="akunadm-dot akunadm-dot-blokir"></span><span class="akunadm-stat-label">Akun Terblokir</span></div>
        <div class="akunadm-stat-value">{{ $totalBlokir }}</div>
    </div>
</div>

{{-- Toolbar pencarian + tombol tambah --}}
<div class="page-toolbar">
    <div class="toolbar-count">
        @if(trim((string)$q) !== '')
            {{ $akuns->count() }} dari {{ $totalSemua }} akun cocok dengan pencarian “{{ $q }}”
        @else
            {{ $totalSemua }} akun terdaftar
        @endif
    </div>
    <form class="toolbar-search" method="GET" action="{{ route('admin.akun') }}">
        <div class="input-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau Gmail…" aria-label="Cari akun">
        </div>
        <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        @if(trim((string)$q) !== '')
            <a href="{{ route('admin.akun') }}" class="btn btn-outline btn-sm">Reset</a>
        @endif
        <button type="button" id="btnTambahAkun" class="btn btn-primary">+ Tambah Akun</button>
    </form>
</div>

{{-- Tabel akun --}}
<div class="akunadm-tablewrap">
    <table class="table">
        <thead>
            <tr>
                <th>Pengguna</th>
                <th>Gmail</th>
                <th>Peran Sistem</th>
                <th>Workspace</th>
                <th>Status</th>
                <th class="akunadm-th-aksi">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($akuns as $u)
                @php
                    $statusRaw = strtolower(trim((string)($u->status ?? 'aktif')));
                    $terblokir = $statusRaw !== '' && !in_array($statusRaw, ['aktif', 'active'], true);
                    $isAdmin = strtolower(trim((string)($u->role ?? 'mahasiswa'))) === 'admin';
                    $alasanBlokir = $u->alasan_blokir ?? $u->blokir_alasan ?? null;
                @endphp
                <tr>
                    <td>
                        <div class="user-cell">
                            <x-avatar :user="$u" size="sm"/>
                            <span>{{ $u->nama }}</span>
                        </div>
                    </td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @if($isAdmin)
                            <span class="akunadm-role akunadm-role-admin">Administrator</span>
                        @else
                            <span class="akunadm-role akunadm-role-anggota">Mahasiswa</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $wpsRaw = $wpMap->get($u->id_user);
                            $wps = collect(is_iterable($wpsRaw) ? $wpsRaw : []);
                        @endphp
                        @if($wps->isNotEmpty())
                            <div class="wp-list">
                                @foreach($wps as $wp)
                                    @php
                                        $peranWp = strtolower(trim((string)($wp->peran ?? $wp->role ?? '')));
                                    @endphp
                                    <div class="wp-item">
                                        <span class="wp-nama">{{ $wp->nama ?? $wp->nama_workspace ?? 'Workspace' }}</span>
                                        <span class="wp-role">{{ $peranWp === 'lead' ? 'Project Manager' : ($peranWp === '' ? 'Anggota' : ucfirst($peranWp)) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="wp-empty">Belum ikut workspace</span>
                        @endif
                    </td>
                    <td>
                        @if($terblokir)
                            <span class="akunadm-pill akunadm-pill-blokir">Terblokir</span>
                            @if(trim((string)($alasanBlokir ?? '')) !== '')
                                <div class="akunadm-alasan">“{{ $alasanBlokir }}”</div>
                            @else
                                <div class="akunadm-alasan">Terkunci tanpa alasan tercatat.</div>
                            @endif
                        @else
                            <span class="akunadm-pill akunadm-pill-aktif">Aktif</span>
                        @endif
                    </td>
                    <td class="akunadm-td-aksi">
                        @if($terblokir)
                            <form action="{{ route('admin.akun.buka', $u->id_user) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">Buka Blokir</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-outline btn-sm btn-blokir"
                                    data-url="{{ route('admin.akun.blokir', $u->id_user) }}"
                                    data-nama="{{ $u->nama }}"
                                    data-email="{{ $u->email }}">Blokir</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="akunadm-kosong">
                        @if(trim((string)$q) !== '')
                            Tidak ada akun yang cocok dengan pencarian “{{ $q }}”.
                        @else
                            Belum ada akun terdaftar.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Tambah Akun (anti-overflow) --}}
<div class="akunadm-overlay" id="modalTambahAkun" role="dialog" aria-modal="true" aria-labelledby="akunadmTambahTitle">
    <div class="akunadm-modal akunadm-modal-lg">
        <div class="akunadm-modal-head">
            <div>
                <h3 id="akunadmTambahTitle">Tambah Akun</h3>
                <p class="akunadm-modal-sub">Buat akun baru untuk mahasiswa atau administrator.</p>
            </div>
            <button type="button" class="akunadm-x" data-modal-close aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form class="akunadm-formwrap" action="{{ route('admin.akun.store') }}" method="POST">
            @csrf
            <div class="akunadm-modal-body">
                @if($errors->any() && old('nama') !== null)
                    <div class="akunadm-errors" role="alert">
                        <strong>Periksa kembali isian berikut:</strong>
                        <ul>
                            @foreach($errors->all() as $pesanError)
                                <li>{{ $pesanError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="akunadm-field">
                    <label for="fNama">Nama Lengkap <b>*</b></label>
                    <input type="text" id="fNama" name="nama" value="{{ old('nama') }}" maxlength="100" required placeholder="cth. Rina Kartika">
                </div>

                <div class="akunadm-field">
                    <label for="fEmail">Email Gmail <b>*</b></label>
                    <input type="email" id="fEmail" name="email" value="{{ old('email') }}" required placeholder="cth. rina@gmail.com">
                    <small class="akunadm-hint">Alamat ini dipakai untuk login TaskMate.</small>
                </div>

                <div class="akunadm-field">
                    <label for="fRole">Peran Sistem <b>*</b></label>
                    <select id="fRole" name="role" required>
                        <option value="mahasiswa" @selected(old('role', 'mahasiswa') === 'mahasiswa')>Anggota — Mahasiswa</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                    </select>
                </div>

                <div class="akunadm-field">
                    <label for="fSandi">Kata Sandi <b>*</b></label>
                    <input type="password" id="fSandi" name="sandi" minlength="6" required placeholder="Minimal 6 karakter" autocomplete="new-password">
                    <small class="akunadm-hint">Minimal 6 karakter — sampaikan kata sandi ini kepada pemilik akun.</small>
                </div>
            </div>
            <div class="akunadm-modal-foot">
                <button type="button" class="akunadm-btn akunadm-btn-outline" data-modal-close>Batal</button>
                <button type="submit" class="akunadm-btn akunadm-btn-dark">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Blokir Akun (gaya polos) --}}
<div class="akunadm-overlay" id="modalBlokir" role="dialog" aria-modal="true" aria-labelledby="akunadmBlokirTitle">
    <div class="akunadm-modal akunadm-modal-sm">
        <div class="akunadm-modal-head">
            <h3 id="akunadmBlokirTitle">Blokir Akun</h3>
            <button type="button" class="akunadm-x" data-modal-close aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form id="formBlokir" class="akunadm-formwrap" action="" method="POST">
            @csrf
            <div class="akunadm-modal-body">
                <div class="akunadm-akunrow">
                    <span class="akunadm-akunawal" id="blokirAwal">A</span>
                    <div class="akunadm-akuninfo">
                        <strong id="blokirNama">Akun</strong>
                        <span id="blokirEmail">—</span>
                    </div>
                    <span class="akunadm-status-aktif">Aktif</span>
                </div>

                <div class="akunadm-field">
                    <label for="fAlasan">Alasan Blokir <b>*</b></label>
                    <textarea id="fAlasan" name="alasan" rows="4" required placeholder="cth. Melanggar ketentuan penggunaan TaskMate…"></textarea>
                    <small class="akunadm-hint">Alasan ini tampil pada kolom Status di daftar akun.</small>
                </div>
            </div>
            <div class="akunadm-modal-foot">
                <button type="button" class="akunadm-btn akunadm-btn-outline" data-modal-close>Batal</button>
                <button type="submit" class="akunadm-btn akunadm-btn-red">Ya, Blokir Akun</button>
            </div>
        </form>
    </div>
</div>

<script>
    function siapkanOverlay(id) {
        var overlay = document.getElementById(id);
        if (!overlay) {
            return { buka: function () {}, tutup: function () {} };
        }
        function tutup() {
            overlay.classList.remove('is-aktif');
        }
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) tutup();
        });
        Array.prototype.forEach.call(overlay.querySelectorAll('[data-modal-close]'), function (el) {
            el.addEventListener('click', tutup);
        });
        return {
            buka: function () { overlay.classList.add('is-aktif'); },
            tutup: tutup
        };
    }

    (function () {
        var modalTambah = siapkanOverlay('modalTambahAkun');
        var modalBlokir = siapkanOverlay('modalBlokir');
        var formBlokir = document.getElementById('formBlokir');
        var elNama = document.getElementById('blokirNama');
        var elEmail = document.getElementById('blokirEmail');
        var elAwal = document.getElementById('blokirAwal');
        var elAlasan = document.getElementById('fAlasan');

        var btnTambah = document.getElementById('btnTambahAkun');
        if (btnTambah) {
            btnTambah.addEventListener('click', function () {
                modalTambah.buka();
                var fokus = document.getElementById('fNama');
                if (fokus) setTimeout(function () { fokus.focus(); }, 60);
            });
        }

        Array.prototype.forEach.call(document.querySelectorAll('.btn-blokir'), function (btn) {
            btn.addEventListener('click', function () {
                var nama = (btn.getAttribute('data-nama') || 'Akun ini').trim();
                if (formBlokir) formBlokir.action = btn.getAttribute('data-url') || '';
                if (elNama) elNama.textContent = nama;
                if (elEmail) elEmail.textContent = btn.getAttribute('data-email') || '—';
                if (elAwal) elAwal.textContent = (nama.charAt(0) || 'A').toUpperCase();
                if (elAlasan) elAlasan.value = '';
                modalBlokir.buka();
                if (elAlasan) setTimeout(function () { elAlasan.focus(); }, 60);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                modalTambah.tutup();
                modalBlokir.tutup();
            }
        });

        @if($errors->any() && old('nama') !== null)
            modalTambah.buka();
        @endif
    })();
</script>
@endsection