{{--
    Tombol pratinjau PDF/gambar. Dibuka pada modal di halaman yang sama
    (lihat public/js/app.js → tmPreview). Tanpa JavaScript, tautan tetap
    berfungsi dengan membuka berkas di tab baru. Tidak tampil untuk format
    yang tidak mendukung pratinjau.
--}}
@props(['version', 'file', 'label' => null])

@php($tipe = $file->tipePratinjau())

@if ($tipe)
    <a href="{{ route('berkas.pratinjau', $version) }}" target="_blank" rel="noopener"
        {{ $attributes->merge(['class' => $label ? 'btn btn-outline btn-sm' : 'icon-btn', 'title' => 'Pratinjau versi ini']) }}
        data-preview="{{ route('berkas.pratinjau', $version) }}"
        data-preview-tipe="{{ $tipe }}"
        data-preview-nama="{{ $file->nama_file }} (v{{ $version->nomor_versi }})"
        data-preview-unduh="{{ route('berkas.unduh', $version) }}">
        <x-icon name="eye"/>@if ($label) {{ $label }}@endif
    </a>
@endif