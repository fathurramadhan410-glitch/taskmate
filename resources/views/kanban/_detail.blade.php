<div class="task-detail">
    <div class="td-head">
        <h2>{{ $task->judul }}</h2>
        <span class="badge {{ status_class($task->status) }}">{{ status_label($task->status) }}</span>
    </div>

    @php($chip = deadline_chip($task))

    <dl class="td-meta">
        <div>
            <dt>Penanggung Jawab</dt>
            <dd class="td-pj">
                <x-avatar :user="$task->penanggungJawab" size="xs"/>
                {{ $task->penanggungJawab?->nama ?? 'Tanpa PJ' }}
            </dd>
        </div>
        <div>
            <dt>Deadline</dt>
            <dd><span class="chip {{ $chip['class'] }}"><x-icon name="clock"/> {{ $chip['label'] }}</span></dd>
        </div>
        <div>
            <dt>Dibuat oleh</dt>
            <dd>{{ $task->pembuat?->nama ?? '-' }}</dd>
        </div>
        <div>
            <dt>Terakhir diubah</dt>
            <dd>{{ format_waktu($task->updated_at) }}</dd>
        </div>
    </dl>

    @if ($task->deskripsi)
        <div class="td-section">
            <h4>Deskripsi Pekerjaan</h4>
            <p class="pre-wrap">{{ $task->deskripsi }}</p>
        </div>
    @endif

    {{-- Aksi status --}}
    @if ($moves['forward'] || $moves['back'])
        <div class="td-section">
            <h4>Perbarui Status</h4>
            <div class="td-moves">
                @if ($moves['back'])
                    <button type="button" class="btn btn-outline btn-sm td-move" data-task="{{ $task->id_task }}" data-move="{{ $moves['back'] }}">
                        <x-icon name="chevron-left"/> Ke {{ status_label($moves['back']) }}
                    </button>
                @endif
                @if ($moves['forward'])
                    <button type="button" class="btn btn-primary btn-sm td-move" data-task="{{ $task->id_task }}" data-move="{{ $moves['forward'] }}">
                        <x-icon name="chevron-right"/> Ke {{ status_label($moves['forward']) }}
                    </button>
                @endif
            </div>
            <div class="form-group td-catatan">
                <label for="catatanStatus">Catatan peninjauan (opsional, tersimpan di riwayat)</label>
                <input type="text" id="catatanStatus" maxlength="255" placeholder="cth: Revisi bagian metodologi">
            </div>
        </div>
    @else
        <div class="td-section">
            <p class="muted"><x-icon name="info"/> Anda tidak dapat mengubah status tugas ini.
                @if ($role !== 'leader') Member hanya dapat memindahkan tugas miliknya maju satu tahap (To-Do → In Progress → Review). @endif</p>
        </div>
    @endif

    {{-- Berkas terkait --}}
    <div class="td-section">
        <h4>Berkas Draf Terkait ({{ $files->count() }})</h4>
        @if ($files->count() > 0)
            <ul class="td-files">
                @foreach ($files as $f)
                    @php($v = $f->versiTerbaru())
                    <li>
                        <span class="td-file-icon"><x-icon name="{{ file_icon($f->ekstensi()) }}"/></span>
                        <div class="td-file-info">
                            <b>{{ $f->nama_file }}</b>
                            <small>v{{ $v?->nomor_versi ?? '-' }} · {{ format_bytes($v?->ukuran_byte) }} · oleh {{ $v?->uploader?->nama ?? '-' }}</small>
                        </div>
                        @if ($v)
                            <a href="{{ route('berkas.unduh', $v) }}" class="icon-btn" title="Unduh versi terbaru"><x-icon name="download"/></a>
                            @if (in_array($f->ekstensi(), ['pdf', 'png', 'jpg', 'jpeg']))
                                <a href="{{ route('berkas.pratinjau', $v) }}" target="_blank" class="icon-btn" title="Pratinjau"><x-icon name="eye"/></a>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted">Belum ada berkas yang dikaitkan dengan tugas ini. Unggah melalui menu Repository dan pilih tugas ini pada kolom "Tugas terkait".</p>
        @endif
    </div>

    {{-- Riwayat status --}}
    <div class="td-section">
        <h4>Riwayat Perubahan Status (Task Tracking)</h4>
        <ul class="timeline">
            @forelse ($logs as $log)
                <li>
                    <span class="tl-dot {{ status_class($log->status_baru) }}"></span>
                    <div class="tl-body">
                        <p>
                            {{ $log->status_lama ? status_label($log->status_lama).' → ' : '' }}<b>{{ status_label($log->status_baru) }}</b>
                            oleh <b>{{ $log->user?->nama ?? 'Akun terhapus' }}</b>
                        </p>
                        @if ($log->catatan)<small class="tl-note">“{{ $log->catatan }}”</small>@endif
                        <small>{{ format_waktu($log->waktu) }}</small>
                    </div>
                </li>
            @empty
                <li class="muted">Belum ada riwayat perubahan status.</li>
            @endforelse
        </ul>
    </div>

    {{-- Aksi leader --}}
    @if ($role === 'leader')
        <div class="td-section td-admin">
            <button type="button" class="btn btn-outline btn-sm" data-task-edit="{{ $task->id_task }}">
                <x-icon name="edit"/> Ubah Tugas
            </button>
            <form method="POST" action="{{ route('tugas.destroy', [$workspace, $task]) }}"
                data-confirm="Hapus tugas '{{ $task->judul }}'? Riwayat status ikut terhapus.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-outline btn-sm"><x-icon name="trash"/> Hapus Tugas</button>
            </form>
        </div>
    @endif
</div>
