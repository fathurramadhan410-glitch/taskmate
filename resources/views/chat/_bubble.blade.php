@php($own = $msg['own'])
<div class="chat-msg {{ $own ? 'own' : '' }}" data-id="{{ $msg['id'] }}">
    @if (!$own)
        <span class="avatar avatar-xs avatar-fallback">{{ $msg['user']['initial'] }}</span>
    @endif
    <div class="bubble {{ $own ? 'bubble-own' : '' }}">
        @if (!$own)
            <span class="bubble-name">{{ $msg['user']['nama'] }}</span>
        @endif
        <p class="bubble-text">{{ $msg['pesan'] }}</p>
        <span class="bubble-time">{{ $msg['tanggal'] }} {{ $msg['waktu'] }}</span>
    </div>
</div>
