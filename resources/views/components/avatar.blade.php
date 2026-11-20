@props(['user' => null, 'size' => 'md'])

@php($url = $user?->avatar_url)
@php($inisial = initial($user->nama ?? '?'))

<span class="avatar avatar-{{ $size }} @unless($url) avatar-fallback @endunless"
    title="{{ $user?->nama ?? '' }}">
    @if ($url)
        {{-- data-avatar-fallback: bila foto gagal dimuat (storage:link belum
             dijalankan / URL Google kedaluwarsa), app.js otomatis mengganti
             tampilan dengan inisial nama — tidak pernah tampil gambar rusak. --}}
        <img src="{{ $url }}" alt="{{ $user->nama ?? '' }}" loading="lazy"
            data-avatar-fallback="{{ $inisial }}">
    @else
        {{ $inisial }}
    @endif
</span>