@extends('layouts.app')

@section('title', 'Kanban — '.$workspace->nama_workspace)
@section('subtitle', 'Klik status untuk menampilkan / menyembunyikan kolomnya')

@section('content')
@php($jumlahTugas = $tasks->count())

<div class="page-toolbar">
    <div class="toolbar-left">
        <h2>Kanban Board</h2>
        <p>@if ($jumlahTugas > 0) {{ $jumlahTugas }} tugas pada workspace ini. @else Belum ada tugas pada workspace ini. @endif</p>
    </div>
    <div class="toolbar-right">
        <button type="button" class="btn btn-ghost" id="toggleSemua" title="Tampilkan atau sembunyikan semua kolom status">
            <x-icon name="board"/> Tampilkan Semua
        </button>
        @if ($role === 'leader')
            <button type="button" class="btn btn-primary" data-modal-open="modalTugas">
                <x-icon name="plus"/> Tambah Tugas
            </button>
        @endif
    </div>
</div>

<form class="filter-bar" method="GET" action="{{ route('kanban.show', $workspace) }}" id="filterForm">
    <div class="filter-field">
        <x-icon name="search"/>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul tugas…">
    </div>
    <div class="filter-field">
        <x-icon name="users"/>
        <select name="pj" data-autosubmit>
            <option value="">Semua penanggung jawab</option>
            @foreach ($anggota as $a)
                @if ($a->user)
                    <option value="{{ $a->id_user }}" @selected(request('pj') == $a->id_user)>{{ $a->user->nama }}</option>
                @endif
            @endforeach
        </select>
    </div>
    @if (request()->filled('pj') || request()->filled('q'))
        <a href="{{ route('kanban.show', $workspace) }}" class="btn btn-ghost btn-sm"><x-icon name="x"/> Reset</a>
    @endif
</form>

{{-- Chip status: klik untuk menampilkan/menyembunyikan kolom (default SEMUA TERSEMBUNYI) --}}
@php($statuses = ['todo' => ['label' => 'To-Do', 'icon' => 'clipboard'], 'in_progress' => ['label' => 'In Progress', 'icon' => 'clock'], 'review' => ['label' => 'Review', 'icon' => 'eye'], 'done' => ['label' => 'Done', 'icon' => 'check-circle']])
<div class="kanban-chips" id="kanbanChips">
    <p class="kanban-hint"><x-icon name="info"/> Klik sebuah status untuk melihat kartu tugas di dalamnya — status yang tidak diklik tetap tersembunyi.</p>
    <div class="chips-row">
        @foreach ($statuses as $key => $meta)
            <button type="button" class="k-chip chip-{{ $key }}" data-kolom="{{ $key }}">
                <span class="k-dot"></span>
                {{ $meta['label'] }}
                <b class="k-count">{{ $counts[$key] ?? 0 }}</b>
                <x-icon name="chevron-down" class="k-chev"/>
            </button>
        @endforeach
    </div>
</div>

{{-- Kolom-kolom Kanban (tersembunyi sampai status diklik) --}}
<div class="kanban-board" id="kanbanBoard" data-workspace="{{ $workspace->id_workspace }}">
    @foreach ($statuses as $key => $meta)
        @php($tugasKolom = $tasks->where('status', $key))
        <section class="kanban-col" data-kolom="{{ $key }}" hidden>
            <header class="kc-head chip-{{ $key }}">
                <span class="kc-title"><span class="k-dot"></span> {{ $meta['label'] }}</span>
                <span class="kc-count">{{ $tugasKolom->count() }}</span>
                <button type="button" class="icon-btn kc-close" data-kolom-tutup="{{ $key }}" title="Sembunyikan kolom ini"><x-icon name="x"/></button>
            </header>

            <div class="kc-body" data-drop-kolom="{{ $key }}">
                @forelse ($tugasKolom as $t)
                    @php($chip = deadline_chip($t))
                    @php($moves = kanban_moves($t, $role, auth()->id()))
                    <article class="task-card {{ $chip['class'] === 'dl-merah' ? 'tc-merah' : '' }} {{ $chip['class'] === 'dl-kuning' ? 'tc-kuning' : '' }}"
                        draggable="true" data-task="{{ $t->id_task }}" data-judul="{{ mb_strtolower($t->judul) }}">
                        <button type="button" class="tc-main" data-task-detail="{{ $t->id_task }}">
                            <h4>{{ $t->judul }}</h4>
                            <span class="tc-deadline chip {{ $chip['class'] }}"><x-icon name="clock"/> {{ $chip['label'] }}</span>
                            <span class="tc-pj">
                                <x-avatar :user="$t->penanggungJawab" size="xs"/>
                                {{ $t->penanggungJawab?->nama ?? 'Tanpa PJ' }}
                            </span>
                        </button>
                        <footer class="tc-actions">
                            @if ($moves['back'])
                                <button type="button" class="tc-move" data-move="{{ $moves['back'] }}" data-task="{{ $t->id_task }}" title="Pindah ke {{ status_label($moves['back']) }}">
                                    <x-icon name="chevron-left"/>
                                </button>
                            @endif
                            @if ($moves['forward'])
                                <button type="button" class="tc-move tc-move-fwd" data-move="{{ $moves['forward'] }}" data-task="{{ $t->id_task }}" title="Pindah ke {{ status_label($moves['forward']) }}">
                                    <x-icon name="chevron-right"/>
                                </button>
                            @endif
                        </footer>
                    </article>
                @empty
                    <p class="kc-empty">Tidak ada tugas pada status ini.</p>
                @endforelse
                <p class="kc-drop-hint"><x-icon name="download"/> Tarik kartu ke sini</p>
            </div>
        </section>
    @endforeach
</div>

<p class="kanban-legend">
    <span class="legend-item"><span class="legend-dot ld-kuning"></span> Deadline dekat (H-3 s.d. H-1)</span>
    <span class="legend-item"><span class="legend-dot ld-merah"></span> Hari-H / terlambat</span>
    <span class="legend-item"><x-icon name="download"/> Kartu dapat ditarik antar kolom yang terbuka</span>
</p>

<!-- Modal: Tambah / Edit Tugas -->
@if ($role === 'leader')
<div class="modal-overlay" id="modalTugas" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="plus"/> Tambah Tugas Baru</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('tugas.store', $workspace) }}" id="formTugasBaru">
            @csrf
            <div class="modal-body">
                <p class="modal-note">Tugas baru otomatis masuk kolom <b>To-Do</b> dan penanggung jawab menerima notifikasi penugasan (UC-03).</p>
                <div class="form-group">
                    <label for="t_judul">Judul Tugas <b>*</b></label>
                    <input type="text" id="t_judul" name="judul" required maxlength="150" placeholder="cth: Menyusun laporan bab pendahuluan">
                    @error('judul')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="t_deskripsi">Deskripsi Pekerjaan</label>
                    <textarea id="t_deskripsi" name="deskripsi" rows="3" maxlength="2000" placeholder="Jelaskan hasil yang diharapkan…"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="t_pj">Penanggung Jawab <b>*</b></label>
                        <select id="t_pj" name="id_penanggung_jawab" required>
                            <option value="">— Pilih anggota —</option>
                            @foreach ($anggota as $a)
                                @if ($a->user)
                                    <option value="{{ $a->id_user }}">{{ $a->user->nama }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('id_penanggung_jawab')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="t_deadline">Deadline <b>*</b></label>
                        <input type="datetime-local" id="t_deadline" name="deadline" required>
                        @error('deadline')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Simpan Tugas</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Modal: Detail Tugas (isi diambil via AJAX) -->
<div class="modal-overlay" id="modalDetail" hidden>
    <div class="modal modal-lg" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="clipboard"/> Detail Tugas</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <div class="modal-body modal-loading" id="detailBody">
            <p class="muted">Memuat detail tugas…</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/kanban.js') }}"></script>
@endpush
