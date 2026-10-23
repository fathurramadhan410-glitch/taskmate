@extends('layouts.auth')

@section('title', 'Gabung ke Workspace')

@section('content')
<div class="gabung-wrap">
    <div class="gabung-card">
        <a href="{{ route('landing') }}" class="gabung-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate">
        </a>

        @if (in_array($state, ['invalid', 'nonaktif', 'kadaluarsa']))
            <div class="gabung-error">
                <span class="ge-icon"><x-icon name="alert"/></span>
                <h1>Undangan Tidak Dapat Digunakan</h1>
                <p>
                    @if ($state === 'invalid')
                        Link atau kode undangan tidak ditemukan. Mungkin salah ketik, atau kode sudah diganti oleh Project Leader.
                    @elseif ($state === 'nonaktif')
                        Undangan untuk workspace ini sedang <b>dinonaktifkan</b> oleh Project Leader. Hubungi leader untuk mengaktifkannya kembali.
                    @else
                        Masa berlaku undangan ini sudah <b>kadaluarsa</b>. Minta Project Leader memperbarui masa berlaku atau membuat kode baru.
                    @endif
                </p>
            </div>
            <a href="{{ route('landing') }}" class="btn btn-primary btn-block mt-16">Kembali ke Halaman Utama</a>
        @else
            <span class="gabung-verified"><x-icon name="shield"/> Link undangan terverifikasi</span>
            <h1>Anda Diundang ke Workspace</h1>
            <p class="gabung-sub">Periksa informasi berikut sebelum bergabung.</p>

            <div class="gabung-ws">
                <span class="ws-avatar ws-avatar-lg">{{ strtoupper(mb_substr($workspace->nama_workspace, 0, 1)) }}</span>
                <div class="gabung-ws-info">
                    <h2>{{ $workspace->nama_workspace }}</h2>
                    @if ($workspace->mata_kuliah)<span class="ws-matkul"><x-icon name="doc"/> {{ $workspace->mata_kuliah }}</span>@endif
                    @if ($workspace->deskripsi)<p>{{ \Illuminate\Support\Str::limit($workspace->deskripsi, 140) }}</p>@endif
                </div>
            </div>

            <dl class="meta-grid gabung-meta">
                <div><dt>Project Leader</dt><dd>{{ $workspace->leaderMember()?->user?->nama ?? '-' }}</dd></div>
                <div><dt>Jumlah anggota</dt><dd>{{ $workspace->members()->count() }} orang</dd></div>
                <div><dt>Kode undangan</dt><dd><code class="invite-code">{{ $workspace->kode_undangan }}</code></dd></div>
                @if ($workspace->undangan_berlaku_sampai)
                    <div><dt>Berlaku sampai</dt><dd>{{ format_waktu($workspace->undangan_berlaku_sampai) }}</dd></div>
                @endif
            </dl>

            <p class="gabung-note">
                <x-icon name="lock"/> Dengan bergabung, Anda akan menjadi <b>Member</b> dan dapat mengakses tugas,
                chat, serta repository workspace ini. Keanggotaan Anda tidak akan terlihat oleh workspace lain.
            </p>

            <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('gabung.konfirmasi', ['kode' => $workspace->kode_undangan]) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <x-icon name="check"/> Ya, Gabung ke Workspace Ini
                </button>
            </form>
            <a href="{{ auth()->check() ? route('dashboard') : route('landing') }}" class="btn btn-ghost btn-block mt-8">Batal</a>
        @endif
    </div>
</div>
@endsection
