@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Daftar workspace kelompok Anda beserta perannya (FR-008)')

@section('content')
<div class="page-toolbar">
    <div class="toolbar-left">
        <h2>Halo, {{ auth()->user()->nama }} <span class="wave">👋</span></h2>
        <p>Pilih workspace untuk mulai bekerja, atau bergabung lewat kode undangan.</p>
    </div>
    <div class="toolbar-right">
        <button type="button" class="btn btn-outline" data-modal-open="modalGabungKode">
            <x-icon name="link"/> Gabung dengan Kode
        </button>
        <button type="button" class="btn btn-primary" data-modal-open="modalBuatWs">
            <x-icon name="plus"/> Buat Workspace
        </button>
    </div>
</div>

@if ($tugasAktif->count() > 0)
    <div class="card card-tugas-aktif">
        <div class="card-head">
            <h3><x-icon name="clock"/> Deadline Terdekat Milik Anda</h3>
        </div>
        <div class="tugas-aktif-list">
            @foreach ($tugasAktif as $t)
                @php($chip = deadline_chip($t))
                <a href="{{ route('kanban.show', $t->id_workspace) }}" class="tugas-aktif-item">
                    <span class="chip {{ $chip['class'] }}">{{ $chip['label'] }}</span>
                    <div class="ta-info">
                        <b>{{ $t->judul }}</b>
                        <small>{{ $t->workspace->nama_workspace ?? '' }}</small>
                    </div>
                    <span class="badge {{ status_class($t->status) }}">{{ status_label($t->status) }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

<div class="ws-grid">
    @forelse ($workspaces as $ws)
        @php($peran = $roles[$ws->id_workspace] ?? 'member')
        @php($progress = $ws->tasks_count > 0 ? round($ws->tugas_selesai_count / $ws->tasks_count * 100) : 0)
        <a href="{{ route('workspace.show', $ws) }}" class="ws-card">
            <div class="ws-card-head">
                <span class="ws-avatar">{{ strtoupper(mb_substr($ws->nama_workspace, 0, 1)) }}</span>
                <span class="badge {{ $peran === 'leader' ? 'badge-leader' : 'badge-member' }}">
                    {{ $peran === 'leader' ? 'Project Leader' : 'Member' }}
                </span>
            </div>
            <h3>{{ $ws->nama_workspace }}</h3>
            @if ($ws->mata_kuliah)<span class="ws-matkul"><x-icon name="doc"/> {{ $ws->mata_kuliah }}</span>@endif
            @if ($ws->deskripsi)<p>{{ \Illuminate\Support\Str::limit($ws->deskripsi, 90) }}</p>@endif
            <div class="ws-progress">
                <div class="wp-bar"><span style="width: {{ $progress }}%"></span></div>
                <small>{{ $ws->tugas_selesai_count }}/{{ $ws->tasks_count }} tugas selesai</small>
            </div>
            <div class="ws-stats">
                <span title="Total tugas"><x-icon name="clipboard"/> {{ $ws->tasks_count }}</span>
                <span title="Anggota"><x-icon name="users"/> {{ $ws->members_count }}</span>
                <span title="Berkas"><x-icon name="folder"/> {{ $ws->files_count }}</span>
            </div>
        </a>
    @empty
        <div class="empty-state">
            <x-icon name="folder"/>
            <h3>Belum ada workspace</h3>
            <p>Buat workspace baru untuk kelompok Anda, atau bergabung dengan kode undangan dari Project Leader.</p>
        </div>
    @endforelse
</div>

<!-- Modal: Buat Workspace -->
<div class="modal-overlay" id="modalBuatWs" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="plus"/> Buat Workspace Baru</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('workspace.store') }}" data-confirm-submit="0">
            @csrf
            <div class="modal-body">
                <p class="modal-note">Anda akan otomatis menjadi <b>Project Leader</b> pada workspace ini dan menerima kode undangan unik (FR-004).</p>
                <div class="form-group">
                    <label for="nama_workspace">Nama Workspace <b>*</b></label>
                    <input type="text" id="nama_workspace" name="nama_workspace" required maxlength="100" placeholder="cth: Proyek Akhir Basis Data — Kelompok 5">
                    @error('nama_workspace')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="mata_kuliah">Mata Kuliah / Judul Tugas</label>
                    <input type="text" id="mata_kuliah" name="mata_kuliah" maxlength="100" placeholder="cth: Sistem Perancangan Perangkat Lunak">
                    @error('mata_kuliah')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="1000" placeholder="Ceritakan singkat tujuan workspace ini…"></textarea>
                    @error('deskripsi')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Simpan Workspace</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Gabung dengan Kode -->
<div class="modal-overlay" id="modalGabungKode" hidden>
    <div class="modal modal-sm" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="link"/> Gabung dengan Kode Undangan</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('dashboard.gabung') }}">
            @csrf
            <div class="modal-body">
                <p class="modal-note">Masukkan kode undangan dari Project Leader (8 karakter). Anda juga bisa memakai <b>link/QR undangan</b> yang dikirim leader.</p>
                <div class="form-group">
                    <label for="kode">Kode Undangan</label>
                    <input type="text" id="kode" name="kode" required maxlength="12" class="input-mono" placeholder="cth: TM7KQ2XZ" style="text-transform: uppercase">
                    @error('kode')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Gabung</button>
            </div>
        </form>
    </div>
</div>
@endsection
