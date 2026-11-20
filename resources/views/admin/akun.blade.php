@extends('layouts.app')

@section('content')
<div class="admin-page">
    <div class="admin-head">
        <div>
            <h1 class="admin-title">Daftar Akun</h1>
            <p class="admin-sub">Semua akun terdaftar di TaskMate beserta status dan perannya di workspace.</p>
        </div>
        <a href="{{ route('admin.akun.create') }}" class="admin-btn admin-btn-primary">+ Tambah Akun</a>
    </div>

    <form class="admin-search" method="GET" action="{{ route('admin.akun') }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau email..." class="admin-input">
        <button type="submit" class="admin-btn">Cari</button>
    </form>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Akun</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Workspace</th>
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
                        <td>
                            <span class="admin-badge {{ $user->role === 'admin' ? 'admin-badge-admin' : 'admin-badge-mahasiswa' }}">
                                {{ $user->role === 'admin' ? 'Admin' : 'Mahasiswa' }}
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge {{ ($user->status ?? 'aktif') === 'diblokir' ? 'admin-badge-diblokir' : 'admin-badge-aktif' }}">
                                {{ ($user->status ?? 'aktif') === 'diblokir' ? 'Diblokir' : 'Aktif' }}
                            </span>
                        </td>
                        <td>
                            @if(!empty($anggotaMap[$user->id]))
                                <div class="ws-chips">
                                    @foreach($anggotaMap[$user->id] as $m)
                                        <span class="ws-chip">
                                            {{ $m['nama'] }}
                                            <b class="ws-peran {{ $m['peran'] === 'Project Manager' ? 'is-pm' : 'is-anggota' }}">{{ $m['peran'] }}</b>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="ws-kosong">Belum ikut workspace</span>
                            @endif
                        </td>
                        <td>
                            @if(($user->status ?? 'aktif') === 'diblokir')
                                <form method="POST" action="{{ route('admin.akun.buka', $user) }}" onsubmit="return confirm('Buka blokir akun ini?')">
                                    @csrf
                                    <button type="submit" class="admin-btn admin-btn-ghost">Buka Blokir</button>
                                </form>
                            @elseif($user->role !== 'admin' && $user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.akun.blokir', $user) }}" onsubmit="return confirm('Blokir akun {{ $user->email }}?')">
                                    @csrf
                                    <button type="submit" class="admin-btn admin-btn-danger">Blokir</button>
                                </form>
                            @else
                                <span class="ws-kosong">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="admin-kosong">Tidak ada akun yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection