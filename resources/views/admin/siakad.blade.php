@extends('layouts.app')

@section('title', 'Aplikasi SIAKAD')
@section('subtitle', 'Kelola aplikasi SIAKAD yang ditampilkan kepada pengguna')

@section('content')
@php
    $apps = $apps ?? collect();
    $editApp = $editApp ?? null;
    $tagPresets = ['E-Learning', 'Sistem Akademik', 'Umum', 'Keuangan', 'Perpustakaan', 'Kemahasiswaan'];
@endphp

<style>
    /* ====== Halaman Aplikasi SIAKAD — semua kelas berawalan siakadadm- ====== */

    .siakadadm-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px;}
    @media (min-width:768px){.siakadadm-card{padding:24px;}}

    /* ---- Form ---- */
    .siakadadm-form-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px;}
    .siakadadm-form-title{margin:0;font-size:17px;font-weight:700;color:#111827;}
    .siakadadm-form-sub{margin:4px 0 0;font-size:13px;color:#6b7280;}
    .siakadadm-editbadge{flex-shrink:0;display:inline-flex;align-items:center;max-width:240px;padding:5px 12px;border-radius:999px;background:#fffbeb;border:1px solid #fde68a;color:#b45309;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

    .siakadadm-errors{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;margin-bottom:16px;}
    .siakadadm-errors strong{display:block;font-size:13px;color:#b91c1c;margin-bottom:4px;}
    .siakadadm-errors ul{margin:0;padding-left:18px;}
    .siakadadm-errors li{font-size:13px;color:#b91c1c;}

    .siakadadm-grid{display:grid;grid-template-columns:1fr;gap:14px;}
    @media (min-width:640px){.siakadadm-grid{grid-template-columns:1fr 1fr;}}
    .siakadadm-span2{grid-column:1 / -1;}

    .siakadadm-field label{display:block;font-size:13px;font-weight:600;color:#111827;margin-bottom:6px;}
    .siakadadm-field label b{color:#dc2626;font-weight:700;}
    .siakadadm-field input[type="text"],
    .siakadadm-field input[type="number"],
    .siakadadm-field textarea{display:block;width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;color:#111827;background:#fff;font-family:inherit;transition:border-color .15s ease,box-shadow .15s ease;}
    .siakadadm-field textarea{resize:vertical;min-height:84px;line-height:1.5;}
    .siakadadm-field input:focus,.siakadadm-field textarea:focus{outline:none;border-color:#111827;box-shadow:0 0 0 3px rgba(17,24,39,.08);}
    .siakadadm-field input::placeholder,.siakadadm-field textarea::placeholder{color:#9ca3af;}
    .siakadadm-hint{display:block;font-size:12px;color:#6b7280;margin-top:6px;}

    .siakadadm-file{display:block;position:relative;cursor:pointer;}
    .siakadadm-file input[type="file"]{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}
    .siakadadm-filebox{display:flex;align-items:center;gap:12px;border:1.5px dashed #d1d5db;border-radius:10px;padding:12px 14px;background:#fafafa;transition:border-color .15s ease,background-color .15s ease;}
    .siakadadm-file:hover .siakadadm-filebox,.siakadadm-file:focus-within .siakadadm-filebox{border-color:#111827;background:#f5f5f4;}
    .siakadadm-filebox svg{flex-shrink:0;color:#6b7280;}
    .siakadadm-filebox strong{display:block;font-size:13.5px;font-weight:600;color:#111827;word-break:break-all;}
    .siakadadm-filebox small{display:block;font-size:12px;color:#6b7280;margin-top:2px;}
    .siakadadm-logonow{display:flex;align-items:center;gap:10px;margin-top:10px;}
    .siakadadm-logonow img{width:40px;height:40px;object-fit:contain;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:4px;}
    .siakadadm-logonow span{font-size:12px;color:#6b7280;}

    .siakadadm-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;}
    .siakadadm-chip{padding:6px 13px;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:background-color .15s ease,border-color .15s ease,color .15s ease;}
    .siakadadm-chip:hover{border-color:#111827;color:#111827;}
    .siakadadm-chip.is-pilih{background:#111827;border-color:#111827;color:#fff;}

    .siakadadm-form-actions{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-top:20px;}

    /* ---- Daftar aplikasi ---- */
    .siakadadm-listhead{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin:26px 0 14px;}
    .siakadadm-listhead h2{margin:0;font-size:16px;font-weight:700;color:#111827;}
    .siakadadm-listcount{font-size:13px;color:#6b7280;}

    .siakadadm-gridcards{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;}
    .siakadadm-item{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;display:flex;flex-direction:column;transition:border-color .15s ease,box-shadow .15s ease;}
    .siakadadm-item:hover{border-color:#d1d5db;box-shadow:0 6px 18px rgba(17,24,39,.06);}
    .siakadadm-item-top{display:flex;align-items:flex-start;gap:12px;}
    .siakadadm-logo{flex-shrink:0;width:44px;height:44px;border-radius:10px;object-fit:contain;background:#f9fafb;border:1px solid #e5e7eb;padding:4px;}
    .siakadadm-logo-initial{display:inline-flex;align-items:center;justify-content:center;border:none;background:#f3f4f6;padding:0;font-size:16px;font-weight:700;color:#111827;}
    .siakadadm-item-titlewrap{flex:1 1 auto;min-width:0;}
    .siakadadm-item-nama{margin:0;font-size:14.5px;font-weight:700;color:#111827;line-height:1.35;word-break:break-word;}
    .siakadadm-item-tag{flex-shrink:0;margin-left:auto;display:inline-flex;padding:3px 10px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:11.5px;font-weight:600;}
    .siakadadm-item-desc{margin:10px 0 0;font-size:13px;color:#6b7280;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .siakadadm-item-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:auto;padding-top:12px;}
    .siakadadm-item-link{font-size:12.5px;font-weight:600;color:#0f766e;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%;}
    .siakadadm-item-link:hover{text-decoration:underline;}
    .siakadadm-item-nolink{font-size:12.5px;color:#9ca3af;}
    .siakadadm-item-urut{flex-shrink:0;padding:3px 10px;border-radius:999px;background:#f9fafb;border:1px solid #e5e7eb;font-size:11.5px;font-weight:600;color:#374151;}
    .siakadadm-item-foot{display:flex;justify-content:flex-end;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #f3f4f6;}

    .siakadadm-empty{border:1.5px dashed #d1d5db;border-radius:14px;padding:38px 20px;text-align:center;background:#fff;}
    .siakadadm-empty strong{display:block;font-size:14.5px;color:#111827;margin-bottom:4px;}
    .siakadadm-empty p{margin:0;font-size:13px;color:#6b7280;}

    /* ---- Tombol ---- */
    .siakadadm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border-radius:10px;border:1px solid transparent;font-size:14px;font-weight:600;line-height:1.2;font-family:inherit;cursor:pointer;text-decoration:none;transition:background-color .15s ease,border-color .15s ease,color .15s ease;}
    .siakadadm-btn-primary{background:#111827;color:#fff;}
    .siakadadm-btn-primary:hover{background:#1f2937;}
    .siakadadm-btn-outline{background:#fff;color:#111827;border-color:#d1d5db;}
    .siakadadm-btn-outline:hover{background:#f9fafb;}
    .siakadadm-btn-ghostdanger{background:#fff;color:#b91c1c;border-color:#fecaca;}
    .siakadadm-btn-ghostdanger:hover{background:#fef2f2;}
    .siakadadm-btn-danger{background:#dc2626;color:#fff;}
    .siakadadm-btn-danger:hover{background:#b91c1c;}
    .siakadadm-btn-sm{padding:7px 12px;font-size:13px;border-radius:8px;}

    /* ---- Modal Hapus (anti-overflow) ---- */
    .siakadadm-overlay{position:fixed;inset:0;z-index:1000;background:rgba(17,24,39,.55);display:none;}
    .siakadadm-overlay.is-aktif{display:flex;align-items:flex-end;justify-content:center;}
    .siakadadm-modal{background:#fff;width:100%;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;border-radius:16px 16px 0 0;box-shadow:0 24px 60px rgba(17,24,39,.28);}
    @media (min-width:640px){
        .siakadadm-overlay.is-aktif{align-items:center;padding:16px;}
        .siakadadm-modal{max-height:calc(100vh - 32px);max-width:430px;border-radius:14px;}
    }
    .siakadadm-formwrap{display:flex;flex-direction:column;flex:1 1 auto;min-height:0;}
    .siakadadm-modal-head{flex-shrink:0;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid #f3f4f6;}
    .siakadadm-modal-head h3{margin:0;font-size:15.5px;font-weight:700;color:#111827;}
    .siakadadm-x{flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:0;background:transparent;color:#6b7280;cursor:pointer;transition:background-color .15s ease,color .15s ease;}
    .siakadadm-x:hover{background:#f3f4f6;color:#111827;}
    .siakadadm-modal-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:18px 20px;-webkit-overflow-scrolling:touch;}
    .siakadadm-modal-foot{flex-shrink:0;display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid #f3f4f6;background:#fff;}
    .siakadadm-hapusrow{display:flex;align-items:center;gap:12px;border:1px solid #e5e7eb;border-radius:12px;padding:12px;}
    .siakadadm-hapusawal{flex-shrink:0;width:40px;height:40px;border-radius:999px;background:#f3f4f6;color:#111827;font-weight:700;font-size:15px;display:inline-flex;align-items:center;justify-content:center;}
    .siakadadm-hapusinfo{flex:1 1 auto;min-width:0;}
    .siakadadm-hapusinfo strong{display:block;font-size:14px;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .siakadadm-hapusinfo small{display:block;font-size:12px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px;}
    .siakadadm-hapusnote{margin:12px 0 0;font-size:13px;color:#6b7280;line-height:1.55;}
</style>

{{-- Form Tambah / Edit Aplikasi — tampil inline di atas halaman --}}
<form class="siakadadm-card" action="{{ $editApp ? route('admin.siakad.update', $editApp->id) : route('admin.siakad.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="siakadadm-form-head">
        <div>
            <h2 class="siakadadm-form-title">{{ $editApp ? 'Edit Aplikasi' : 'Tambah Aplikasi Baru' }}</h2>
            <p class="siakadadm-form-sub">{{ $editApp ? 'Perbarui informasi aplikasi, lalu simpan perubahan.' : 'Lengkapi informasi aplikasi — aplikasi langsung tampil pada daftar di bawah.' }}</p>
        </div>
        @if($editApp)
            <span class="siakadadm-editbadge">Mode Edit — {{ $editApp->nama }}</span>
        @endif
    </div>

    @if($errors->any())
        <div class="siakadadm-errors" role="alert">
            <strong>Periksa kembali isian berikut:</strong>
            <ul>
                @foreach($errors->all() as $pesanError)
                    <li>{{ $pesanError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="siakadadm-grid">
        <div class="siakadadm-field">
            <label for="siakadadmNama">Nama Aplikasi <b>*</b></label>
            <input type="text" id="siakadadmNama" name="nama" value="{{ old('nama', $editApp->nama ?? '') }}" required maxlength="100" placeholder="cth. SIAKAD Kampus">
        </div>

        <div class="siakadadm-field">
            <label for="siakadadmUrl">URL Aplikasi</label>
            <input type="text" id="siakadadmUrl" name="url" value="{{ old('url', $editApp->url ?? '') }}" maxlength="255" placeholder="https://siakad.contoh.ac.id" inputmode="url">
            <small class="siakadadm-hint">Kosongkan bila aplikasi tidak memiliki tautan.</small>
        </div>

        <div class="siakadadm-field siakadadm-span2">
            <label for="siakadadmDeskripsi">Deskripsi</label>
            <textarea id="siakadadmDeskripsi" name="deskripsi" rows="3" maxlength="1000" placeholder="Jelaskan singkat fungsi aplikasi…">{{ old('deskripsi', $editApp->deskripsi ?? '') }}</textarea>
        </div>

        <div class="siakadadm-field">
            <label>Logo Aplikasi</label>
            <label class="siakadadm-file">
                <input type="file" id="siakadadmLogo" name="logo" accept="image/*">
                <span class="siakadadm-filebox">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.2-4.2a1.5 1.5 0 0 0-2.1 0L7 18.5"/></svg>
                    <span>
                        <strong id="siakadadmLogoNama">{{ ($editApp && !empty($editApp->logo)) ? 'Ganti berkas logo (opsional)' : 'Pilih berkas logo' }}</strong>
                        <small>PNG, JPG, atau SVG</small>
                    </span>
                </span>
            </label>
            @if($editApp && !empty($editApp->logo))
                <div class="siakadadm-logonow">
                    <img src="{{ asset($editApp->logo) }}" alt="Logo saat ini">
                    <span>Logo saat ini — biarkan kosong bila tidak diganti.</span>
                </div>
            @endif
        </div>

        <div class="siakadadm-field">
            <label for="siakadadmUrutan">Urutan Tampil</label>
            <input type="number" id="siakadadmUrutan" name="urutan" value="{{ old('urutan', $editApp->urutan ?? ($apps->count() + 1)) }}" min="0" step="1">
            <small class="siakadadm-hint">Angka kecil tampil lebih dulu pada daftar.</small>
        </div>

        <div class="siakadadm-field siakadadm-span2">
            <label for="siakadadmTag">Kategori Aplikasi</label>
            <div class="siakadadm-chips">
                @foreach($tagPresets as $tagPreset)
                    <button type="button" class="siakadadm-chip" data-tag="{{ $tagPreset }}">{{ $tagPreset }}</button>
                @endforeach
            </div>
            <input type="text" id="siakadadmTag" name="tag" value="{{ old('tag', $editApp->tag ?? '') }}" list="siakadadmTagList" maxlength="50" placeholder="Atau ketik kategori lain di sini…" autocomplete="off">
            <datalist id="siakadadmTagList">
                @foreach($tagPresets as $tagPreset)
                    <option value="{{ $tagPreset }}"></option>
                @endforeach
            </datalist>
            <small class="siakadadm-hint">Pilih salah satu kategori di atas, atau ketik kategori sendiri. Bila dikosongkan, dipakai kategori “Umum”.</small>
        </div>
    </div>

    <div class="siakadadm-form-actions">
        @if($editApp)
            <a href="{{ route('admin.siakad') }}" class="siakadadm-btn siakadadm-btn-outline">Batal</a>
        @endif
        <button type="submit" class="siakadadm-btn siakadadm-btn-primary">{{ $editApp ? 'Simpan Perubahan' : 'Tambah Aplikasi' }}</button>
    </div>
</form>

{{-- Daftar Aplikasi --}}
<div class="siakadadm-listhead">
    <h2>Daftar Aplikasi</h2>
    <span class="siakadadm-listcount">{{ $apps->count() }} aplikasi terdaftar</span>
</div>

@if($apps->isEmpty())
    <div class="siakadadm-empty">
        <strong>Belum ada aplikasi terdaftar</strong>
        <p>Tambahkan aplikasi pertama melalui form di atas.</p>
    </div>
@else
    <div class="siakadadm-gridcards">
        @foreach($apps as $app)
            @php
                $tagTampil = trim((string)($app->tag ?? '')) !== '' ? $app->tag : 'Umum';
                $hostTampil = !empty($app->url) ? (parse_url($app->url, PHP_URL_HOST) ?: $app->url) : null;
            @endphp
            <article class="siakadadm-item">
                <div class="siakadadm-item-top">
                    @if(!empty($app->logo))
                        <img class="siakadadm-logo" src="{{ asset($app->logo) }}" alt="Logo {{ $app->nama }}">
                    @else
                        <span class="siakadadm-logo siakadadm-logo-initial">{{ strtoupper(\Illuminate\Support\Str::substr(trim((string)($app->nama ?? '')) ?: 'A', 0, 1)) }}</span>
                    @endif
                    <div class="siakadadm-item-titlewrap">
                        <h3 class="siakadadm-item-nama">{{ $app->nama }}</h3>
                    </div>
                    <span class="siakadadm-item-tag">{{ $tagTampil }}</span>
                </div>

                <p class="siakadadm-item-desc">{{ trim((string)($app->deskripsi ?? '')) !== '' ? $app->deskripsi : 'Tanpa deskripsi.' }}</p>

                <div class="siakadadm-item-meta">
                    @if($hostTampil)
                        <a class="siakadadm-item-link" href="{{ $app->url }}" target="_blank" rel="noopener">{{ $hostTampil }}</a>
                    @else
                        <span class="siakadadm-item-nolink">Tanpa tautan</span>
                    @endif
                    <span class="siakadadm-item-urut">Urutan {{ $app->urutan ?? 0 }}</span>
                </div>

                <div class="siakadadm-item-foot">
                    <a href="{{ route('admin.siakad', ['edit' => $app->id]) }}" class="siakadadm-btn siakadadm-btn-sm siakadadm-btn-outline">Edit</a>
                    <button type="button" class="siakadadm-btn siakadadm-btn-sm siakadadm-btn-ghostdanger siakadadm-btn-hapus"
                            data-url="{{ route('admin.siakad.hapus', $app->id) }}"
                            data-nama="{{ $app->nama }}"
                            data-meta="{{ $tagTampil }}{{ $hostTampil ? ' · ' . $hostTampil : '' }}">Hapus</button>
                </div>
            </article>
        @endforeach
    </div>
@endif

{{-- Modal Hapus Aplikasi --}}
<div class="siakadadm-overlay" id="siakadadmHapusOverlay" role="dialog" aria-modal="true" aria-labelledby="siakadadmHapusTitle">
    <div class="siakadadm-modal">
        <div class="siakadadm-modal-head">
            <h3 id="siakadadmHapusTitle">Hapus Aplikasi?</h3>
            <button type="button" class="siakadadm-x" data-modal-close aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form id="siakadadmFormHapus" action="" method="POST" class="siakadadm-formwrap">
            @csrf
            <div class="siakadadm-modal-body">
                <div class="siakadadm-hapusrow">
                    <span class="siakadadm-hapusawal" id="siakadadmHapusAwal">A</span>
                    <div class="siakadadm-hapusinfo">
                        <strong id="siakadadmHapusNama">Aplikasi</strong>
                        <small id="siakadadmHapusMeta">—</small>
                    </div>
                </div>
                <p class="siakadadm-hapusnote">Aplikasi akan dihapus permanen dari daftar dan tidak lagi tampil di halaman pengguna. Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <div class="siakadadm-modal-foot">
                <button type="button" class="siakadadm-btn siakadadm-btn-outline" data-modal-close>Batal</button>
                <button type="submit" class="siakadadm-btn siakadadm-btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var overlay = document.getElementById('siakadadmHapusOverlay');
        var formHapus = document.getElementById('siakadadmFormHapus');

        function tutupHapus() {
            if (!overlay) return;
            overlay.classList.remove('is-aktif');
            if (formHapus) formHapus.action = '';
        }

        function bukaHapus(btn) {
            if (!overlay) return;
            var nama = (btn.getAttribute('data-nama') || 'Aplikasi').trim();
            if (formHapus) formHapus.action = btn.getAttribute('data-url') || '';
            var elNama = document.getElementById('siakadadmHapusNama');
            var elMeta = document.getElementById('siakadadmHapusMeta');
            var elAwal = document.getElementById('siakadadmHapusAwal');
            if (elNama) elNama.textContent = nama;
            if (elMeta) elMeta.textContent = btn.getAttribute('data-meta') || '—';
            if (elAwal) elAwal.textContent = (nama.charAt(0) || 'A').toUpperCase();
            overlay.classList.add('is-aktif');
        }

        Array.prototype.forEach.call(document.querySelectorAll('.siakadadm-btn-hapus'), function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                bukaHapus(btn);
            });
        });

        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) tutupHapus();
            });
            Array.prototype.forEach.call(overlay.querySelectorAll('[data-modal-close]'), function (el) {
                el.addEventListener('click', tutupHapus);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.classList.contains('is-aktif')) tutupHapus();
            });
        }

        /* Chip kategori */
        var tagInput = document.getElementById('siakadadmTag');

        function segarkanChip() {
            if (!tagInput) return;
            var nilai = tagInput.value.trim().toLowerCase();
            Array.prototype.forEach.call(document.querySelectorAll('.siakadadm-chip'), function (chip) {
                chip.classList.toggle('is-pilih', (chip.getAttribute('data-tag') || '').toLowerCase() === nilai);
            });
        }

        Array.prototype.forEach.call(document.querySelectorAll('.siakadadm-chip'), function (chip) {
            chip.addEventListener('click', function () {
                if (tagInput) tagInput.value = chip.getAttribute('data-tag') || '';
                segarkanChip();
            });
        });
        if (tagInput) {
            tagInput.addEventListener('input', segarkanChip);
            segarkanChip();
        }

        /* Nama berkas logo */
        var logoInput = document.getElementById('siakadadmLogo');
        var logoNama = document.getElementById('siakadadmLogoNama');
        if (logoInput && logoNama) {
            logoInput.addEventListener('change', function () {
                logoNama.textContent = (logoInput.files && logoInput.files.length)
                    ? logoInput.files[0].name
                    : 'Pilih berkas logo';
            });
        }
    })();
</script>
@endsection