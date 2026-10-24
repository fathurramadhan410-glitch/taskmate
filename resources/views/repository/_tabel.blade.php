{{--
    Tabel berkas repository. Variabel:
      $daftar (Collection<FileDokumen>), $workspace, $role,
      $kosongJudul, $kosongTeks, $umumLabel (teks bila tidak terkait tugas),
      $jenis ('draf' | 'materi') — materi terkait ke workspace, draf terkait ke tugas
--}}
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Nama Berkas</th>
                <th>{{ $jenis === 'materi' ? 'Workspace Terkait' : 'Tugas Terkait' }}</th>
                <th>Versi Terbaru</th>
                <th>Ukuran</th>
                <th>Pengunggah Terakhir</th>
                <th>Diunggah</th>
                <th class="th-actions">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftar as $f)
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
                        @if ($jenis === 'materi')
                            <span class="muted">{{ $workspace->nama_workspace }}</span>
                        @elseif ($task)
                            <a href="{{ route('kanban.show', $workspace) }}" class="link-soft" title="{{ $task->judul }}">{{ \Illuminate\Support\Str::limit($task->judul, 26) }}</a>
                        @else
                            <span class="muted">{{ $umumLabel }}</span>
                        @endif
                    </td>
                    <td><span class="badge badge-version">v{{ $v?->nomor_versi ?? '-' }}</span></td>
                    <td>{{ format_bytes($v?->ukuran_byte) }}</td>
                    <td>{{ $v?->uploader?->nama ?? '-' }}</td>
                    <td>{{ format_waktu($v?->waktu_unggah) }}</td>
                    <td>
                        <div class="row-actions">
                            @if ($v)
                                <x-preview-btn :version="$v" :file="$f" title="Pratinjau versi terbaru"/>
                                <a href="{{ route('berkas.unduh', $v) }}" class="icon-btn" title="Unduh versi terbaru"><x-icon name="download"/></a>
                            @endif
                            @if ($f->versions->count() > 1)
                                <button type="button" class="icon-btn" title="Riwayat versi"
                                    data-versi="{{ $f->id_file }}"
                                    data-nama="{{ $f->nama_file }}">{{ $f->versions->count() }}v</button>
                            @endif
                            {{-- Tombol hapus HANYA untuk Project Leader --}}
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
                        <h3>{{ $kosongJudul }}</h3>
                        <p>{{ $kosongTeks }}</p>
                    </div>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>