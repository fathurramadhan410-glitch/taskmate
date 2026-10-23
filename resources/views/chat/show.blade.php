@extends('layouts.app')

@section('title', 'Chat — '.$workspace->nama_workspace)
@section('subtitle', 'Obrolan singkat anggota workspace, pesan muncul otomatis tanpa refresh')

@section('content')
<div class="chat-wrap" data-ws="{{ $workspace->id_workspace }}" data-last="{{ $lastId }}">
    <div class="chat-panel">
        <header class="chat-head">
            <div class="chat-title">
                <span class="ws-avatar">{{ strtoupper(mb_substr($workspace->nama_workspace, 0, 1)) }}</span>
                <div>
                    <b>{{ $workspace->nama_workspace }}</b>
                    <small><span class="live-dot"></span> Realtime · anggota workspace</small>
                </div>
            </div>
        </header>

        <div class="chat-body" id="chatBody">
            @forelse ($messages as $m)
                @include('chat._bubble', ['msg' => [
                    'id' => $m->id_chat,
                    'pesan' => $m->pesan,
                    'waktu' => $m->created_at?->format('H:i'),
                    'tanggal' => $m->created_at?->format('d/m/Y'),
                    'own' => $m->id_user === auth()->id(),
                    'user' => [
                        'nama' => $m->user->nama ?? 'Akun terhapus',
                        'avatar' => $m->user?->avatar_url,
                        'initial' => initial($m->user->nama ?? '?'),
                    ],
                ], 'sep' => true])
            @empty
                <div class="chat-empty">
                    <x-icon name="chat"/>
                    <h3>Belum ada pesan</h3>
                    <p>Sapa rekan kerja Anda — pesan pertama akan langsung muncul di sini.</p>
                </div>
            @endforelse
        </div>

        <footer class="chat-input">
            <form id="chatForm" autocomplete="off">
                <textarea id="chatText" rows="1" maxlength="1000" placeholder="Tulis pesan… (Enter untuk kirim, Shift+Enter untuk baris baru)"></textarea>
                <button type="submit" class="btn btn-primary btn-icon" id="chatSend" title="Kirim pesan">
                    <x-icon name="send"/>
                </button>
            </form>
        </footer>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/chat.js') }}"></script>
@endpush
