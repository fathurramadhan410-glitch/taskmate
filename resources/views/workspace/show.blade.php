@extends('layouts.app')

@section('title', $workspace->nama_workspace)
@section('subtitle', 'Ringkasan workspace, anggota, dan panel undangan')

@section('content')
@php($progress = $stats['total'] > 0 ? round($stats['done'] / $stats['total'] * 100) : 0)

<div class="page-toolbar">
    <div class="toolbar-left">
        <div class="ws-title-row">
            <span class="ws-avatar ws-avatar-lg">{{ strtoupper(mb_substr($workspace->nama_workspace, 0, 1)) }}</span>
            <div>
                <h2>{{ $workspace->nama_workspace }}</h2>
                @if ($workspace->mata_kuliah)<p><x-icon name="doc"/> {{ $workspace->mata_kuliah }}</p>@endif
            </div>
            <span class="badge {{ $role === 'leader' ? 'badge-leader' : 'badge-member' }}">
                {{ $role === 'leader' ? 'Project Leader' : 'Member' }}
            </span>
        </div>
    </div>
    <div class="toolbar-right">
        <a href="{{ route('kanban.show', $workspace) }}" class="btn btn-outline"><x-icon name="board"/> Buka Kanban</a>
        @if ($isLeader)
            <button type="button" class="btn btn-outline" data-modal-open="modalEditWs"><x-icon name="edit"/> Ubah</button>
            <button type="button" class="btn btn-danger-outline" data-modal-open="modalHapusWs"><x-icon name="trash"/> Hapus</button>
        @endif
    </div>
</div>

<div class="grid-2-1">
    <div class="grid-main">
        <div class="card">
            <div class="card-head">
                <h3><x-icon name="info"/> Tentang Workspace</h3>
            </div>
            <div class="card-body">
                @if ($workspace->deskripsi)
                    <p class="pre-wrap">{{ $workspace->deskripsi }}</p>
                @else
                    <p class="muted">Belum ada deskripsi. {{ $isLeader ? 'Ubah workspace untuk menambahkan deskripsi.' : '' }}</p>
                @endif
                <dl class="meta-grid">
                    <div><dt>Dibuat oleh</dt><dd>{{ $workspace->pembuat?->nama ?? '-' }}</dd></div>
                    <div><dt>Dibuat pada</dt><dd>{{ format_waktu($workspace->created_at) }}</dd></div>
                    <div><dt>Total anggota</dt><dd>{{ $members->count() }} orang</dd></div>
                    <div><dt>Total berkas</dt><dd>{{ $workspace->files()->count() }} berkas</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><x-icon name="board"/> Progres Tugas</h3>
                <a href="{{ route('kanban.show', $workspace) }}" class="btn btn-ghost btn-sm">Kelola di Kanban <x-icon name="chevron-right"/></a>
            </div>
            <div class="card-body">
                <div class="progress-line">
                    <div class="pl-bar"><span style="width: {{ $progress }}%"></span></div>
                    <b>{{ $progress }}% selesai</b>
                </div>
                <div class="status-tiles">
                    <div class="st-tile st-todo"><b>{{ $stats['todo'] }}</b><span>To-Do</span></div>
                    <div class="st-tile st-in-progress"><b>{{ $stats['in_progress'] }}</b><span>In Progress</span></div>
                    <div class="st-tile st-review"><b>{{ $stats['review'] }}</b><span>Review</span></div>
                    <div class="st-tile st-done"><b>{{ $stats['done'] }}</b><span>Done</span></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><x-icon name="users"/> Anggota ({{ $members->count() }})</h3>
            </div>
            <div class="card-body">
                <ul class="member-list">
                    @forelse ($members as $m)
                        <li>
                            <x-avatar :user="$m->user" size="md"/>
                            <div class="member-info">
                                <b>{{ $m->user?->nama ?? 'Akun terhapus' }}</b>
                                <small>{{ $m->user?->email ?? '-' }} · bergabung {{ format_tanggal($m->tanggal_bergabung) }}</small>
                            </div>
                            <span class="badge {{ $m->peran === 'leader' ? 'badge-leader' : 'badge-member' }}">
                                {{ $m->peran === 'leader' ? 'Leader' : 'Member' }}
                            </span>
                            @if ($isLeader && $m->peran !== 'leader')
                                <form method="POST" action="{{ route('workspace.keluarkan', [$workspace, $m]) }}"
                                    data-confirm="Keluarkan {{ $m->user?->nama }} dari workspace ini?">
                                    @csrf
                                    <button type="submit" class="btn btn-danger-outline btn-sm" title="Keluarkan anggota"><x-icon name="x"/> Keluarkan</button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="muted">Belum ada anggota.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="grid-side">
        @if ($isLeader)
            <div class="card card-invite">
                <div class="card-head">
                    <h3><x-icon name="qr"/> Undang Anggota</h3>
                </div>
                <div class="card-body">
                    <p class="invite-hint">Bagikan kode, link, atau QR berikut. Link bertanda tangan digital — hanya bisa dipakai dengan persetujuan pengguna &amp; selama undangan aktif.</p>

                    <label class="invite-label">Kode Undangan</label>
                    <div class="invite-code-row">
                        <code class="invite-code" id="kodeUndangan">{{ $workspace->kode_undangan }}</code>
                        <button type="button" class="icon-btn" data-copy="{{ $workspace->kode_undangan }}" title="Salin kode"><x-icon name="copy"/></button>
                    </div>

                    <label class="invite-label">Link Undangan</label>
                    <div class="invite-link-row">
                        <input type="text" readonly value="{{ $link }}" class="input-mono" id="linkUndangan" onclick="this.select()">
                        <button type="button" class="icon-btn" data-copy="{{ $link }}" title="Salin link"><x-icon name="copy"/></button>
                    </div>

                    <div class="qr-wrap">
                        <div class="qr-box">{!! $qrSvg !!}</div>
                        <p><x-icon name="qr"/> Scan QR untuk membuka halaman bergabung</p>
                    </div>

                    <form method="POST" action="{{ route('workspace.undangan', $workspace) }}" class="invite-form">
                        @csrf
                        <input type="hidden" name="aksi" value="berlaku">
                        <div class="form-group">
                            <label for="hari">Masa Berlaku Undangan</label>
                            <select name="hari" id="hari">
                                <option value="0" @selected(!$workspace->undangan_berlaku_sampai)>Tanpa batas waktu</option>
                                <option value="1" @selected($workspace->undangan_berlaku_sampai && now()->diffInDays($workspace->undangan_berlaku_sampai) <= 1.5)>1 hari</option>
                                <option value="3" @selected($workspace->undangan_berlaku_sampai && now()->diffInDays($workspace->undangan_berlaku_sampai) > 1.5 && now()->diffInDays($workspace->undangan_berlaku_sampai) <= 3.5)>3 hari</option>
                                <option value="7" @selected($workspace->undangan_berlaku_sampai && now()->diffInDays($workspace->undangan_berlaku_sampai) > 3.5 && now()->diffInDays($workspace->undangan_berlaku_sampai) <= 8)>7 hari</option>
                                <option value="30" @selected($workspace->undangan_berlaku_sampai && now()->diffInDays($workspace->undangan_berlaku_sampai) > 8)>30 hari</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm btn-block"><x-icon name="clock"/> Simpan Masa Berlaku</button>
                    </form>

                    <div class="invite-actions">
                        <form method="POST" action="{{ route('workspace.undangan', $workspace) }}"
                            data-confirm="Buat kode baru? Kode, link, dan QR yang lama tidak akan berlaku lagi.">
                            @csrf
                            <input type="hidden" name="aksi" value="{{ $workspace->undangan_aktif ? 'nonaktifkan' : 'aktifkan' }}">
                            <button type="submit" class="btn {{ $workspace->undangan_aktif ? 'btn-outline' : 'btn-primary' }} btn-sm btn-block">
                                <x-icon name="{{ $workspace->undangan_aktif ? 'lock' : 'check-circle' }}"/>
                                {{ $workspace->undangan_aktif ? 'Nonaktifkan Undangan' : 'Aktifkan Undangan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('workspace.undangan', $workspace) }}"
                            data-confirm="Buat kode undangan baru? Semua link dan QR lama otomatis dicabut.">
                            @csrf
                            <input type="hidden" name="aksi" value="regenerate">
                            <button type="submit" class="btn btn-ghost btn-sm btn-block">
                                <x-icon name="refresh"/> Buat Kode Baru
                            </button>
                        </form>
                    </div>

                    <p class="invite-status">
                        Status: <b>{{ $workspace->undangan_aktif ? 'AKTIF' : 'NONAKTIF' }}</b>
                        @if ($workspace->undangan_berlaku_sampai)
                            · berlaku sampai {{ format_waktu($workspace->undangan_berlaku_sampai) }}
                        @endif
                    </p>
                </div>
            </div>
        @else
            <div class="card card-invite">
                <div class="card-head">
                    <h3><x-icon name="lock"/> Mengundang Anggota</h3>
                </div>
                <div class="card-body">
                    <p class="muted">Kode undangan hanya dikelola oleh Project Leader. Minta kode, link, atau QR kepada leader untuk mengundang anggota baru.</p>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-head">
                <h3><x-icon name="clock"/> Aktivitas Terbaru</h3>
            </div>
            <div class="card-body">
                <ul class="timeline">
                    @forelse ($aktivitas as $log)
                        <li>
                            <span class="tl-dot {{ status_class($log->status_baru) }}"></span>
                            <div class="tl-body">
                                <p><b>{{ $log->user?->nama ?? 'Akun terhapus' }}</b> memindahkan
                                    “{{ \Illuminate\Support\Str::limit($log->task?->judul ?? 'Tugas', 32) }}” ke
                                    <b>{{ status_label($log->status_baru) }}</b></p>
                                @if ($log->catatan)<small class="tl-note">“{{ $log->catatan }}”</small>@endif
                                <small>{{ format_waktu($log->waktu) }}</small>
                            </div>
                        </li>
                    @empty
                        <li class="muted">Belum ada perubahan status tugas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Workspace -->
@if ($isLeader)
<div class="modal-overlay" id="modalEditWs" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="edit"/> Ubah Workspace</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('workspace.update', $workspace) }}">
            @csrf
            @method('PATCH')
            <div class="modal-body">
                <div class="form-group">
                    <label for="u_nama">Nama Workspace <b>*</b></label>
                    <input type="text" id="u_nama" name="nama_workspace" required maxlength="100" value="{{ old('nama_workspace', $workspace->nama_workspace) }}">
                    @error('nama_workspace')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="u_matkul">Mata Kuliah / Judul Tugas</label>
                    <input type="text" id="u_matkul" name="mata_kuliah" maxlength="100" value="{{ old('mata_kuliah', $workspace->mata_kuliah) }}">
                </div>
                <div class="form-group">
                    <label for="u_deskripsi">Deskripsi</label>
                    <textarea id="u_deskripsi" name="deskripsi" rows="3" maxlength="1000">{{ old('deskripsi', $workspace->deskripsi) }}</textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Hapus Workspace -->
<div class="modal-overlay" id="modalHapusWs" hidden>
    <div class="modal modal-sm modal-danger" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="alert"/> Hapus Workspace?</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('workspace.destroy', $workspace) }}">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Workspace <b>{{ $workspace->nama_workspace }}</b> akan dihapus beserta seluruh <b>tugas</b>, <b>berkas</b>, dan <b>riwayat chat</b> di dalamnya. Tindakan ini tidak dapat dibatalkan.</p>
                <label class="checkbox">
                    <input type="checkbox" name="setuju" value="1" required>
                    <span>Saya mengerti dan ingin menghapus workspace ini</span>
                </label>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-danger"><x-icon name="trash"/> Hapus Permanen</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
