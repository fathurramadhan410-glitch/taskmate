@extends('layouts.app')

@section('title', 'Aplikasi SIAKAD')

@section('content')
@php
    $apps    = $apps    ?? collect();
    $editApp = $editApp ?? null;
@endphp

<style>
    /* ====== Gaya khusus halaman Aplikasi SIAKAD (admin) ====== */
    .siakadadm-wrap { max-width: 1140px; margin: 0 auto; }

    .siakadadm-head {
        display: flex; flex-wrap: wrap; gap: 12px 20px;
        justify-content: space-between; align-items: flex-end;
        margin-bottom: 20px;
    }
    .siakadadm-head h1 { margin: 0; font-size: 1.6rem; font-weight: 700; line-height: 1.2; }
    .siakadadm-head p  { margin: 6px 0 0; font-size: .92rem; opacity: .75; }
    .siakadadm-total {
        display: inline-block; padding: 6px 14px; border-radius: 999px;
        background: rgba(13, 148, 136, .12); color: #0d9488;
        font-weight: 600; font-size: .85rem; white-space: nowrap;
    }

    .siakadadm-grid {
        display: grid; grid-template-columns: 390px minmax(0, 1fr);
        gap: 20px; align-items: start;
    }
    @media (max-width: 920px) { .siakadadm-grid { grid-template-columns: 1fr; } }

    .siakadadm-card-title {
        display: flex; flex-wrap: wrap; gap: 10px;
        justify-content: space-between; align-items: center;
        padding: 14px 18px; border-bottom: 1px solid rgba(0,0,0,.07);
        font-weight: 700;
    }
    .siakadadm-badge-edit {
        font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .4px; padding: 3px 10px; border-radius: 999px;
        background: #fef3c7; color: #92400e;
    }

    .siakadadm-errors {
        border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c;
        border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; font-size: .88rem;
    }
    .siakadadm-errors ul { margin: 6px 0 0; padding-left: 18px; }

    .siakadadm-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
    @media (max-width: 520px) { .siakadadm-form-row { grid-template-columns: 1fr; } }

    .siakadadm-hint { font-size: .78rem; opacity: .65; margin-top: 4px; }

    .siakadadm-actions { display: flex; gap: 10px; margin-top: 4px; }
    .siakadadm-actions > * { flex: 1; text-align: center; }

    .siakadadm-btn {
        display: inline-block; padding: 10px 16px; border-radius: 10px;
        border: 1px solid transparent; font-weight: 600; font-size: .92rem;
        cursor: pointer; text-decoration: none; line-height: 1.4;
        transition: filter .15s ease;
    }
    .siakadadm-btn:hover { filter: brightness(.95); }
    .siakadadm-btn-simpan { background: #0d9488; color: #fff; }
    .siakadadm-btn-batal  { background: transparent; color: inherit; border-color: rgba(0,0,0,.2); }

    .siakadadm-tablewrap { overflow-x: auto; }
    .siakadadm-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .siakadadm-table th {
        text-align: left; font-size: .74rem; text-transform: uppercase; letter-spacing: .5px;
        opacity: .6; padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,.08);
        white-space: nowrap;
    }
    .siakadadm-table td { padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,.05); vertical-align: middle; }
    .siakadadm-table tr:last-child td { border-bottom: none; }

    .siakadadm-app { display: flex; align-items: center; gap: 12px; min-width: 220px; }
    .siakadadm-logo {
        width: 42px; height: 42px; border-radius: 10px; object-fit: cover;
        background: #fff; border: 1px solid rgba(0,0,0,.08); flex: 0 0 auto;
    }
    .siakadadm-inisial {
        width: 42px; height: 42px; border-radius: 10px; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center;
        background: #0d9488; color: #fff; font-weight: 700;
    }
    .siakadadm-nama { font-weight: 600; }
    .siakadadm-desk { font-size: .8rem; opacity: .65; margin-top: 2px; }

    .siakadadm-link { color: #0d9488; text-decoration: none; word-break: break-all; }
    .siakadadm-link:hover { text-decoration: underline; }

    .siakadadm-urutan {
        display: inline-block; min-width: 34px; text-align: center;
        padding: 3px 10px; border-radius: 999px;
        background: rgba(13,148,136,.1); color: #0d9488; font-weight: 700;
    }

    .siakadadm-row-actions { display: flex; gap: 8px; align-items: center; }
    .siakadadm-row-actions form { display: inline; margin: 0; }

    .siakadadm-kosong { text-align: center; padding: 34px 14px !important; opacity: .7; }
</style>

<div class="siakadadm-wrap">

    {{-- ===== Kepala halaman ===== --}}
    <div class="siakadadm-head">
        <div>
            <h1>Aplikasi SIAKAD</h1>
            <p>Kelola daftar aplikasi SIAKAD &amp; E-Learning yang tampil untuk mahasiswa.</p>
        </div>
        <span class="siakadadm-total">Total: {{ $apps->count() }} aplikasi</span>
    </div>

    <div class="siakadadm-grid">

        {{-- ===== KOLOM KIRI: FORM TAMBAH / EDIT ===== --}}
        <div class="card">
            <div class="siakadadm-card-title">
                <span>{{ $editApp ? 'Edit Aplikasi: ' . $editApp->nama : 'Tambah Aplikasi Baru' }}</span>
                @if($editApp)
                    <span class="siakadadm-badge-edit">Mode Edit</span>
                @endif
            </div>
            <div class="card-body">

                @if($errors->any())
                    <div class="siakadadm-errors">
                        <b>Periksa kembali isian berikut:</b>
                        <ul>
                            @foreach($errors->all() as $pesanError)
                                <li>{{ $pesanError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    action="{{ $editApp ? route('admin.siakad.update', $editApp->id) : route('admin.siakad.store') }}"
                    method="POST"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label>Nama Aplikasi <b>*</b></label>
                        <input
                            type="text"
                            name="nama"
                            value="{{ old('nama', $editApp->nama ?? '') }}"
                            required
                            maxlength="100"
                            placeholder="mis. Mooc Poliban">
                    </div>

                    <div class="form-group">
                        <label>Tautan / URL</label>
                        <input
                            type="text"
                            name="url"
                            value="{{ old('url', $editApp->url ?? '') }}"
                            maxlength="255"
                            placeholder="https://mooc.poliban.ac.id">
                        <div class="siakadadm-hint">Mulai dengan https:// agar bisa dibuka langsung.</div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi Singkat</label>
                        <textarea
                            name="deskripsi"
                            rows="3"
                            maxlength="1000"
                            placeholder="mis. Platform pembelajaran daring Poliban">{{ old('deskripsi', $editApp->deskripsi ?? '') }}</textarea>
                    </div>

                    <div class="siakadadm-form-row">
                        <div class="form-group">
                            <label>Logo</label>
                            <input type="file" name="logo" accept="image/*">
                            <div class="siakadadm-hint">PNG/JPG/SVG/WebP, maksimal 2 MB.{{ $editApp && !empty($editApp->logo) ? ' Biarkan kosong bila tidak ingin mengganti.' : '' }}</div>
                        </div>
                        <div class="form-group">
                            <label>Urutan Tampil</label>
                            <input
                                type="number"
                                name="urutan"
                                min="0"
                                value="{{ old('urutan', $editApp->urutan ?? '') }}"
                                placeholder="Otomatis">
                            <div class="siakadadm-hint">Kosongkan untuk otomatis di urutan terakhir.</div>
                        </div>
                    </div>

                    <div class="siakadadm-actions">
                        <button type="submit" class="siakadadm-btn siakadadm-btn-simpan">
                            {{ $editApp ? 'Perbarui Aplikasi' : 'Simpan Aplikasi' }}
                        </button>
                        @if($editApp)
                            <a href="{{ route('admin.siakad') }}" class="siakadadm-btn siakadadm-btn-batal">Batal</a>
                        @endif
                    </div>
                </form>

            </div>
        </div>

        {{-- ===== KOLOM KANAN: DAFTAR APLIKASI ===== --}}
        <div class="card">
            <div class="siakadadm-card-title">
                <span>Daftar Aplikasi</span>
            </div>
            <div class="card-body">

                <div class="siakadadm-tablewrap">
                    <table class="siakadadm-table">
                        <thead>
                            <tr>
                                <th style="width:46px;">No</th>
                                <th>Aplikasi</th>
                                <th>Tautan</th>
                                <th style="width:80px;">Urutan</th>
                                <th style="width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($apps as $app)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        <div class="siakadadm-app">
                                            @if(!empty($app->logo))
                                                <img class="siakadadm-logo" src="{{ asset($app->logo) }}" alt="Logo {{ $app->nama }}">
                                            @else
                                                <span class="siakadadm-inisial">{{ \Illuminate\Support\Str::substr($app->nama ?? 'A', 0, 1) }}</span>
                                            @endif
                                            <div>
                                                <div class="siakadadm-nama">{{ $app->nama }}</div>
                                                @if(!empty($app->deskripsi))
                                                    <div class="siakadadm-desk">{{ \Illuminate\Support\Str::limit($app->deskripsi, 70) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if(!empty($app->url))
                                            <a class="siakadadm-link" href="{{ $app->url }}" target="_blank" rel="noopener noreferrer">
                                                {{ \Illuminate\Support\Str::limit($app->url, 40) }}
                                            </a>
                                        @else
                                            <span style="opacity:.5;">&mdash;</span>
                                        @endif
                                    </td>
                                    <td><span class="siakadadm-urutan">{{ $app->urutan ?? '-' }}</span></td>
                                    <td>
                                        <div class="siakadadm-row-actions">
                                            <a
                                                class="btn btn-sm btn-warning"
                                                href="{{ route('admin.siakad', ['edit' => $app->id]) }}">
                                                Edit
                                            </a>
                                            <form
                                                action="{{ route('admin.siakad.hapus', $app->id) }}"
                                                method="POST"
                                                onsubmit="return confirm({{ json_encode('Yakin ingin menghapus aplikasi ' . $app->nama . '?') }});">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="siakadadm-kosong">
                                        Belum ada aplikasi SIAKAD. Tambahkan lewat form "Tambah Aplikasi Baru".
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection