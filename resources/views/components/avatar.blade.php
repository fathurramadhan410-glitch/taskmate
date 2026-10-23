@props(['user' => null, 'size' => 'md'])

@php($url = $user?->avatar_url)

<span class="avatar avatar-{{ $size }} @unless($url) avatar-fallback @endunless"
    title="{{ $user?->nama ?? '' }}">
    @if ($url)
        <img src="{{ $url }}" alt="{{ $user->nama ?? '' }}" loading="lazy">
    @else
        {{ initial($user->nama ?? '?') }}
    @endif
</span>
