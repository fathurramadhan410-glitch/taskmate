@extends('layouts.app')

@php
    $dataVersi = $files->mapWithKeys(function ($f) {
        return [$f->id_file => [
            'nama' => $f->nama_file,
            'versi' => $f->versions->map(function ($v) use ($f) {
                return [
                    'nomor' => $v->nomor_versi,
                    'ukuran' => format_bytes($v->ukuran_byte),
                    'oleh' => $v->uploader?->nama ?? '-',
                    'waktu' => format_waktu($v->waktu_unggah),
                    'catatan' => $v->catatan_revisi,
                    'unduh' => route('berkas.unduh', $v),
                    'pratinjau' => in_array(strtolower(pathinfo($f->nama_file, PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg'])
                        ? route('berkas.pratinjau', $v) : null,
                ];
            }),
        ]];
    });
@endphp

@section('title', 'Repository — '.$workspace->nama_workspace)
@section('subtitle', 'Draf tugas tersimpan per versi (v1, v2, v3…) tanpa saling menimpa')

@section('content')
<div class="page-toolbar">
    <div class="toolbar-left">
        <h2>Repository Berkas</h2>
        <p>{{ $files->count() }} berkas · {{ $totalVersi }} versi · total {{ format_bytes($totalUkuran) }}</p>
    </div>
    <div class="toolbar-right">
        <button type="button" class="btn btn-primary" data-modal-open="modalUnggah">
            <x-icon name="upload"/> Unggah Draf
        </button>
    </div>
</div>

<div class="info-banner">
    <x-icon name="info"/>
    <div>
        <b>Batasan unggahan (SRS 2.5.d):</b> PDF, DOC/DOCX, PPT/PPTX, XLS/XLSX, TXT, ZIP, PNG, JPG — maksimal {{ round(config('taskmate.max_file_kb') / 1024) }} MB per berkas.
        Unggahan dengan nama sama otomatis menjadi <b>versi baru</b>. PDF &amp; gambar dapat dibaca langsung dengan pratinjau bawaan.
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Berkas</th>
                    <th>Tugas Terkait</th>
                    <th>Versi Terbaru</th>
                    <th>Ukuran</th>
                    <th>Pengunggah Terakhir</th>
                    <th>Diunggah</th>
                    <th class="th-actions">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($files as $f)
                    @php($v = $f->versiTerbaru())
                    @php($task = $f->task)
                    <tr>
                        <td>
                            <span class="file-cell">
                                <span class="file-icon ext-{{ $f->ekstensi() }}"><x-icon name="{{ file_icon($f->ekstensi()) }}"/></span>
                                <div>
                                    <b>{{ $f->nama_file }}</b>
                                    <small>{{ $f->versions->count() }} versi tersimpan</small>
                                </div>
                            </span>
                        </td>
                        <td>
                            @if ($task)
                                <a href="{{ route('kanban.show', $workspace) }}" class="link-soft" title="{{ $task->judul }}">{{ \Illuminate\Support\Str::limit($task->judul, 26) }}</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge badge-version">v{{ $v?->nomor_versi ?? '-' }}</span></td>
                        <td>{{ format_bytes($v?->ukuran_byte) }}</td>
                        <td>{{ $v?->uploader?->nama ?? '-' }}</td>
                        <td>{{ format_waktu($v?->waktu_unggah) }}</td>
                        <td>
                            <div class="row-actions">
                                @if ($v)
                                    <a href="{{ route('berkas.unduh', $v) }}" class="icon-btn" title="Unduh versi terbaru"><x-icon name="download"/></a>
                                    @if (in_array($f->ekstensi(), ['pdf', 'png', 'jpg', 'jpeg']))
                                        <a href="{{ route('berkas.pratinjau', $v) }}" target="_blank" class="icon-btn" title="Pratinjau PDF/gambar"><x-icon name="eye"/></a>
                                    @endif
                                @endif
                                @if ($f->versions->count() > 1)
                                    <button type="button" class="icon-btn" title="Riwayat versi"
                                        data-versi="{{ $f->id_file }}"
                                        data-nama="{{ $f->nama_file }}">{{ $f->versions->count() }}v</button>
                                @endif
                                @if ($role === 'leader')
                                    <button type="button" class="icon-btn icon-danger" title="Hapus berkas"
                                        data-hapus-berkas="{{ route('berkas.hapus', [$workspace, $f]) }}"
                                        data-nama="{{ $f->nama_file }}">
                                        <x-icon name="trash"/>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <x-icon name="folder"/>
                            <h3>Repository masih kosong</h3>
                            <p>Unggah draf pertama Anda — setiap unggahan dengan nama sama akan tersimpan sebagai versi baru.</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
                        @foreach ($tugas as $t)
                            <option value="{{ $t->id_task }}">{{ $t->judul }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="catatan_revisi">Catatan Revisi</label>
                    <input type="text" id="catatan_revisi" name="catatan_revisi" maxlength="255" placeholder="cth: Revisi bab 2 sesuai masukan dosen">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><x-icon name="check"/> Unggah</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Riwayat Versi (isi diambil via AJAX) -->
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
    // Modal riwayat versi berkas: data versi disematkan langsung dari server
    document.addEventListener('DOMContentLoaded', function () {
        var dataVersi = @json($dataVersi);

        document.querySelectorAll('[data-versi]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-versi');
                var nama = btn.getAttribute('data-nama');
                var berkas = (dataVersi[id] || { nama: nama, versi: [] });
                var html = '<p class="modal-note">Riwayat versi untuk <b>' + berkas.nama + '</b> — setiap versi dapat diunduh terpisah.</p>';
                html += '<ul class="versi-list">';
                berkas.versi.forEach(function (v) {
                    html += '<li class="versi-item">'
                        + '<span class="badge badge-version">v' + v.nomor + '</span>'
                        + '<div class="versi-info"><b>' + v.waktu + '</b>'
                        + '<small>' + v.ukuran + ' · oleh ' + v.oleh + (v.catatan ? ' · “' + v.catatan + '”' : '') + '</small></div>'
                        + (v.pratinjau ? '<a href="' + v.pratinjau + '" target="_blank" class="icon-btn" title="Pratinjau">👁</a>' : '')
                        + '<a href="' + v.unduh + '" class="btn btn-outline btn-sm">Unduh</a>'
                        + '</li>';
                });
                html += '</ul>';
                document.getElementById('versiBody').innerHTML = html;
                window.tmOpenModal('modalVersi');
            });
        });

        // Konfirmasi hapus berkas (FR-020)
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
    });
</script>
@endpush
