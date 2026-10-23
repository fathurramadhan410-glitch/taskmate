@extends('layouts.app')

@section('title', 'Notifikasi')
@section('subtitle', 'Pengingat deadline dan pemberitahuan penting tugas (TMT-006)')

@section('content')
<div class="page-toolbar">
    <div class="toolbar-left">
        <h2>Daftar Notifikasi</h2>
        <p>Notifikasi yang belum dibaca ditandai dengan titik biru.</p>
    </div>
    <div class="toolbar-right">
        <form method="POST" action="{{ route('notifikasi.bacaSemua') }}">
            @csrf
            <button type="submit" class="btn btn-outline"><x-icon name="check"/> Tandai Semua Dibaca</button>
        </form>
    </div>
</div>

<div class="card">
    <ul class="notif-list">
        @forelse ($notifikasis as $n)
            <li class="notif-item @unless($n->status_baca) notif-unread @endunless">
                <form method="POST" action="{{ route('notifikasi.baca', $n) }}" class="notif-link">
                    @csrf
                    <button type="submit" class="notif-btn">
                        <span class="dd-icon jenis-{{ $n->jenis }}"><x-icon name="{{ jenis_icon($n->jenis) }}"/></span>
                        <span class="notif-body">
                            <span class="notif-jenis">{{ jenis_label($n->jenis) }}</span>
                            <p>{{ $n->pesan }}</p>
                            <small>
                                {{ format_waktu($n->waktu_kirim) }}
                                · email: {{ $n->status_email }}
                                @if ($n->task)
                                    · tugas: {{ $n->task->judul }}
                                @endif
                            </small>
                        </span>
                        @unless($n->status_baca)
                            <span class="notif-dot" title="Belum dibaca"></span>
                        @endunless
                    </button>
                </form>
            </li>
        @empty
            <li>
                <div class="empty-state">
                    <x-icon name="bell"/>
                    <h3>Tidak ada notifikasi</h3>
                    <p>Pengingat deadline (H-3, H-1, hari-H) dan pemberitahuan tugas akan muncul di sini.</p>
                </div>
            </li>
        @endforelse
    </ul>
</div>

@if ($notifikasis->hasPages())
    <div class="pagination-wrap">
        {{ $notifikasis->withQueryString()->links() }}
    </div>
@endif
@endsection
