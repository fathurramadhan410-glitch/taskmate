@extends('layouts.app')

@php
    $isLeader = $role === 'leader';

    // Data riwayat versi (draf + materi) disematkan ke halaman untuk modal "Riwayat Versi"
    $dataVersi = $drafs->concat($materi)->mapWithKeys(function ($f) {
        return [$f->id_file => [
            'nama' => $f->nama_file,
            'tipe' => $f->tipePratinjau(),
            'versi' => $f->versions->map(function ($v) use ($f) {
                return [
                    'nomor' => $v->nomor_versi,
                    'ukuran' => format_bytes($v->ukuran_byte),
                    'oleh' => $v->uploader?->nama ?? '-',
                    'waktu' => format_waktu($v->waktu_unggah),
                    'catatan' => $v->catatan_revisi,
                    'unduh' => route('berkas.unduh', $v),
                    'pratinjau' => $f->tipePratinjau() ? route('berkas.pratinjau', $v) : null,
                ];
            })->values(),
        ]];
    });

    // Tab aktif & modal yang harus terbuka kembali ketika validasi gagal / datang dari detail tugas
    $errMateri = $errors->getBag('materi');
    $tabAktif = $errMateri->any() ? 'materi' : $tab;
    $bukaModal = null;
    $tugasDrafTerpilih = $errMateri->any() ? $tugasDipilih : old('id_task', $tugasDipilih);
    if ($errors->hasAny(['berkas', 'id_task', 'catatan_revisi']) || (request()->boolean('unggah') && $tugasDipilih)) {
        $bukaModal = 'modalUnggah';
        $tabAktif = 'draf';
    } elseif ($errMateri->any()) {
        $bukaModal = 'modalMateri';
    }
@endphp

@section('title', 'Repository — '.$workspace->nama_workspace)
@section('subtitle', 'Draf tugas tersimpan per versi (v1, v2, v3…) dan materi referensi dari Project Leader')

@section('content')
<div class="page-toolbar">
    <div class="toolbar-left">
        <h2>Repository Berkas</h2>
        <p>{{ $drafs->count() + $materi->count() }} berkas · {{ $totalVersi }} versi · total {{ format_bytes($totalUkuran) }}</p>
    </div>
    <div class="toolbar-right">
        @if ($isLeader)
            <button type="button" class="btn btn-outline" data-modal-open="modalMateri">
                <x-icon name="upload"/> Unggah Materi
            </button>
        @endif
        <button type="button" class="btn btn-primary" data-modal-open="modalUnggah">
            <x-icon name="upload"/> Unggah Draf
        </button>
    </div>
</div>

<div class="info-banner">
    <x-icon name="info"/>
    <div>
        <b>Batasan unggahan (SRS 2.5.d):</b> PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, TXT, ZIP, PNG, JPG — maksimal {{ round(config('taskmate.max_file_kb') / 1024) }} MB per berkas.
        Unggahan dengan nama sama otomatis menjadi <b>versi baru</b>. PDF &amp; gambar dapat dibaca langsung lewat pratinjau bawaan tanpa membuka tab baru.
        <br>
        <b>Draf Tugas</b> dapat diunggah anggota untuk tugas miliknya; <b>Materi &amp; Referensi</b> hanya dapat diunggah oleh Project Leader.
    </div>
</div>

<div class="repo-tabs" role="tablist" aria-label="Kategori berkas">
    <button type="button" class="repo-tab {{ $tabAktif === 'draf' ? 'active' : '' }}" role="tab"
        id="tabDraf" data-tab="draf" aria-selected="{{ $tabAktif === 'draf' ? 'true' : 'false' }}" aria-controls="panelDraf">
        <x-icon name="doc"/> Draf Tugas <span class="repo-tab-count">{{ $drafs->count() }}</span>
    </button>
    <button type="button" class="repo-tab {{ $tabAktif === 'materi' ? 'active' : '' }}" role="tab"
        id="tabMateri" data-tab="materi" aria-selected="{{ $tabAktif === 'materi' ? 'true' : 'false' }}" aria-controls="panelMateri">
        <x-icon name="folder"/> Materi &amp; Referensi <span class="repo-tab-count">{{ $materi->count() }}</span>
    </button>
</div>

<div class="card" id="panelDraf" role="tabpanel" aria-labelledby="tabDraf" data-panel="draf" @if ($tabAktif !== 'draf') hidden @endif>
    @include('repository._tabel', [
        'daftar' => $drafs,
        'jenis' => 'draf',
        'umumLabel' => '—',
        'kosongJudul' => 'Belum ada draf',
        'kosongTeks' => 'Unggah draf pertama Anda — setiap unggahan dengan nama sama akan tersimpan sebagai versi baru.',
    ])
</div>

<div class="card" id="panelMateri" role="tabpanel" aria-labelledby="tabMateri" data-panel="materi" @if ($tabAktif !== 'materi') hidden @endif>
    @include('repository._tabel', [
        'daftar' => $materi,
        'jenis' => 'materi',
        'umumLabel' => '—',
        'kosongJudul' => 'Belum ada materi atau referensi',
        'kosongTeks' => $isLeader
            ? 'Unggah materi atau referensi agar dapat dilihat dan diunduh seluruh anggota workspace.'
            : 'Project Leader belum mengunggah materi atau referensi.',
    ])
</div>

<!-- Modal: Unggah Draf -->
<div class="modal-overlay" id="modalUnggah" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="upload"/> Unggah Draf Baru</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('repository.upload', $workspace) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="berkas">Berkas <b>*</b></label>
                    <input type="file" id="berkas" name="berkas" required
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.png,.jpg,.jpeg">
                    <p class="field-hint">Maksimal {{ round(config('taskmate.max_file_kb') / 1024) }} MB. Jika nama berkas sudah ada, sistem menyimpannya sebagai versi berikutnya (FR-018).</p>
                    @error('berkas')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="id_task">Tugas Terkait (opsional — FR-016)</label>
                    <select id="id_task" name="id_task">
                        <option value="">— Tanpa tugas —</option>
                        @foreach ($tugasDraf as $t)
                            <option value="{{ $t->id_task }}" @selected((string) $tugasDrafTerpilih === (string) $t->id_task)>
                                {{ $t->judul }}@if ($isLeader && $t->penanggungJawab) — PJ: {{ $t->penanggungJawab->nama }}@endif
                            </option>
                        @endforeach
                    </select>
                    @if ($isLeader)
                        <p class="field-hint">Sebagai Project Leader, seluruh tugas workspace dapat dipilih.</p>
                    @elseif ($tugasDraf->isEmpty())
                        <p class="field-hint">Anda belum ditunjuk sebagai penanggung jawab tugas apa pun, sehingga draf akan disimpan tanpa tugas terkait.</p>
                    @else
                        <p class="field-hint">Hanya tugas yang ditugaskan kepada Anda yang ditampilkan.</p>
                    @endif
                    @error('id_task')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="catatan_revisi">Catatan Revisi</label>
                    <input type="text" id="catatan_revisi" name="catatan_revisi" maxlength="255" value="{{ old('catatan_revisi') }}" placeholder="cth: Revisi bab 2 sesuai masukan dosen">
                    @error('catatan_revisi')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Unggah</button>
            </div>
        </form>
    </div>
</div>

@if ($isLeader)
<!-- Modal: Unggah Materi / Referensi (khusus Project Leader) -->
<div class="modal-overlay" id="modalMateri" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="folder"/> Unggah Materi / Referensi</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <form method="POST" action="{{ route('repository.materi', $workspace) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <p class="modal-note">Materi dan referensi dapat dibaca &amp; diunduh seluruh anggota workspace. Hanya Project Leader yang dapat mengunggahnya.</p>
                <div class="form-group">
                    <label for="berkas_materi">Berkas <b>*</b></label>
                    <input type="file" id="berkas_materi" name="berkas" required
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.png,.jpg,.jpeg">
                    <p class="field-hint">Maksimal {{ round(config('taskmate.max_file_kb') / 1024) }} MB. Nama berkas yang sama disimpan sebagai versi baru.</p>
                    @if ($errMateri->has('berkas'))<p class="field-error">{{ $errMateri->first('berkas') }}</p>@endif
                </div>
                                <div class="form-group">
                    <label for="id_workspace_materi">Workspace Terkait <b>*</b></label>
                    <select id="id_workspace_materi" name="id_workspace" required>
                        @foreach ($workspaceLeader as $w)
                            <option value="{{ $w->id_workspace }}" @selected((string) ($errMateri->any() ? old('id_workspace') : $workspace->id_workspace) === (string) $w->id_workspace)>{{ $w->nama_workspace }}</option>
                        @endforeach
                    </select>
                    <p class="field-hint">Materi tersimpan di workspace yang dipilih. Seluruh anggota workspace tersebut dapat melihat dan mengunduhnya; hanya Project Leader yang dapat menghapusnya.</p>
                    @if ($errMateri->has('id_workspace'))<p class="field-error">{{ $errMateri->first('id_workspace') }}</p>@endif
                </div>
                <div class="form-group">
                    <label for="catatan_materi">Keterangan</label>
                    <input type="text" id="catatan_materi" name="catatan_revisi" maxlength="255" value="{{ $errMateri->any() ? old('catatan_revisi') : '' }}" placeholder="cth: Panduan format penulisan laporan">
                    @if ($errMateri->has('catatan_revisi'))<p class="field-error">{{ $errMateri->first('catatan_revisi') }}</p>@endif
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Unggah Materi</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Modal: Riwayat Versi (isi dibangun dari data yang disematkan server) -->
<div class="modal-overlay" id="modalVersi" hidden>
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-head">
            <h3><x-icon name="refresh"/> Riwayat Versi Berkas</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><x-icon name="x"/></button>
        </div>
        <div class="modal-body" id="versiBody">
            <p class="muted">Memuat riwayat versi…</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var dataVersi = @json($dataVersi);

        /* ---------- Tab Draf Tugas / Materi & Referensi ---------- */
        var tabs = document.querySelectorAll('.repo-tab');
        var panels = document.querySelectorAll('[data-panel]');

        function pilihTab(nama) {
            tabs.forEach(function (t) {
                var aktif = t.getAttribute('data-tab') === nama;
                t.classList.toggle('active', aktif);
                t.setAttribute('aria-selected', aktif ? 'true' : 'false');
            });
            panels.forEach(function (p) {
                p.hidden = p.getAttribute('data-panel') !== nama;
            });
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', nama);
                url.searchParams.delete('unggah');
                url.searchParams.delete('tugas');
                window.history.replaceState(null, '', url.toString());
            } catch (e) { /* abaikan */ }
        }

        tabs.forEach(function (t) {
            t.addEventListener('click', function () { pilihTab(t.getAttribute('data-tab')); });
        });

        /* ---------- Riwayat versi (DOM dibangun aman, tanpa innerHTML dari data pengguna) ---------- */
        function el(tag, className, text) {
            var n = document.createElement(tag);
            if (className) n.className = className;
            if (text !== undefined) n.textContent = text;
            return n;
        }

        document.querySelectorAll('[data-versi]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-versi');
                var berkas = dataVersi[id] || { nama: btn.getAttribute('data-nama'), tipe: null, versi: [] };
                var body = document.getElementById('versiBody');
                body.innerHTML = '';

                var note = el('p', 'modal-note');
                note.appendChild(document.createTextNode('Riwayat versi untuk '));
                note.appendChild(el('b', '', berkas.nama));
                note.appendChild(document.createTextNode(' — setiap versi dapat dipratinjau (PDF/gambar) dan diunduh terpisah.'));
                body.appendChild(note);

                var list = el('ul', 'versi-list');
                berkas.versi.forEach(function (v) {
                    var li = el('li', 'versi-item');
                    li.appendChild(el('span', 'badge badge-version', 'v' + v.nomor));

                    var info = el('div', 'versi-info');
                    info.appendChild(el('b', '', v.waktu));
                    info.appendChild(el('small', '', v.ukuran + ' · oleh ' + v.oleh + (v.catatan ? ' · “' + v.catatan + '”' : '')));
                    li.appendChild(info);

                    if (v.pratinjau) {
                        var p = el('a', 'btn btn-outline btn-sm', 'Pratinjau');
                        p.href = v.pratinjau;
                        p.target = '_blank';
                        p.rel = 'noopener';
                        p.setAttribute('data-preview', v.pratinjau);
                        p.setAttribute('data-preview-tipe', berkas.tipe || 'pdf');
                        p.setAttribute('data-preview-nama', berkas.nama + ' (v' + v.nomor + ')');
                        p.setAttribute('data-preview-unduh', v.unduh);
                        li.appendChild(p);
                    }

                    var u = el('a', 'btn btn-outline btn-sm', 'Unduh');
                    u.href = v.unduh;
                    li.appendChild(u);
                    list.appendChild(li);
                });
                body.appendChild(list);
                window.tmOpenModal('modalVersi');
            });
        });

        /* ---------- Konfirmasi hapus berkas (FR-020) ---------- */
        document.querySelectorAll('[data-hapus-berkas]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-hapus-berkas');
                var nama = btn.getAttribute('data-nama');
                window.tmConfirm('Hapus berkas "' + nama + '" beserta seluruh versinya?', function () {
                    var f = document.createElement('form');
                    f.method = 'POST';
                    f.action = url;
                    f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
                    document.body.appendChild(f);
                    f.submit();
                });
            });
        });

        @if ($bukaModal)
        window.tmOpenModal(@json($bukaModal));
        @endif
    });
</script>
@endpush