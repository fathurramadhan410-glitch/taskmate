@extends('layouts.app')

@section('content')
<div class="admin-page">
    <div class="admin-head">
        <div>
            <h1 class="admin-title">Akun Terblokir</h1>
            <p class="admin-sub">Akun yang diblokir karena salah sandi berkali-kali atau diblokir manual oleh admin.</p>
        </div>
        <a href="{{ route('admin.akun') }}" class="admin-btn admin-btn-ghost">&larr; Daftar Akun</a>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Akun</th>
                    <th>Diblokir Pada</th>
                    <th>Alasan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="admin-akun-nama">{{ $user->name }}</div>
                            <div class="admin-akun-email">{{ $user->email }}</div>
                        </td>
                        <td>{{ $user->diblokir_pada ? \Carbon\Carbon::parse($user->diblokir_pada)->format('d M Y H:i') : '&mdash;' }}</td>
                        <td>{{ $user->alasan_blokir ?? '&mdash;' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.akun.buka', $user) }}" onsubmit="return confirm('Buka blokir akun {{ $user->email }}?')">
                                @csrf
                                <button type="submit" class="admin-btn admin-btn-ghost">Buka Blokir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="admin-kosong">Tidak ada akun yang diblokir.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection