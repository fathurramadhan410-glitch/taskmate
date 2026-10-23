{{-- Satu baris berkas pada modal detail tugas. Variabel: $f (FileDokumen), opsional $tandaiUmum --}}
@php($v = $f->versiTerbaru())
<li>
    <span class="td-file-icon"><x-icon name="{{ file_icon($f->ekstensi()) }}"/></span>
    <div class="td-file-info">
        <b>{{ $f->nama_file }}
            @if (! empty($tandaiUmum) && ! $f->id_task)
                <span class="badge badge-umum">Umum</span>
            @endif
        </b>
        <small>v{{ $v?->nomor_versi ?? '-' }} · {{ format_bytes($v?->ukuran_byte) }} · oleh {{ $v?->uploader?->nama ?? '-' }}</small>
        @if ($v?->catatan_revisi)
            <small class="td-file-note">“{{ $v->catatan_revisi }}”</small>
        @endif
    </div>
    @if ($v)
        <x-preview-btn :version="$v" :file="$f"/>
        <a href="{{ route('berkas.unduh', $v) }}" class="icon-btn" title="Unduh versi terbaru"><x-icon name="download"/></a>
    @endif
</li>