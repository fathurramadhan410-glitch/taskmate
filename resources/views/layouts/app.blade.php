<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · TaskMate</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    @stack('head')
</head>
<body class="app-body">
<div class="app-shell">

    <div class="sidebar-backdrop" data-sidebar-close></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-logo"><img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate"></span>
            <div class="brand-text">
                <strong>TaskMate</strong>
                <small>Collaboration Suite</small>
            </div>
            <button type="button" class="icon-btn sidebar-close only-mobile" data-sidebar-close aria-label="Tutup menu">
                <x-icon name="x"/>
            </button>
        </div>

        <nav class="sidebar-nav">
            <p class="nav-label">Menu Utama</p>
            <a href="{{ route('dashboard') }}" class="nav-item {{ nav_active('dashboard') }}">
                <x-icon name="dashboard"/><span>Dashboard</span>
            </a>
            <a href="{{ route('notifikasi.index') }}" class="nav-item {{ nav_active('notifikasi.*') }}">
                <x-icon name="bell"/><span>Notifikasi</span>
                @if ($unreadCount > 0)
                    <span class="nav-badge" data-notif-badge>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </a>

            @if (isset($workspace) && $workspace)
                <p class="nav-label" title="{{ $workspace->nama_workspace }}">{{ \Illuminate\Support\Str::limit($workspace->nama_workspace, 20) }}</p>
                <a href="{{ route('workspace.show', $workspace) }}" class="nav-item {{ nav_active('workspace.show') }}">
                    <x-icon name="info"/><span>Ringkasan</span>
                </a>
                <a href="{{ route('kanban.show', $workspace) }}" class="nav-item {{ nav_active('kanban.show') }}">
                    <x-icon name="board"/><span>Kanban</span>
                </a>
                <a href="{{ route('chat.show', $workspace) }}" class="nav-item {{ nav_active('chat.show') }}">
                    <x-icon name="chat"/><span>Chat</span>
                </a>
                <a href="{{ route('repository.index', $workspace) }}" class="nav-item {{ nav_active('repository.index') }}">
                    <x-icon name="folder"/><span>Repository</span>
                </a>
            @endif

            <p class="nav-label">Workspace Saya</p>
            @forelse ($navWorkspaces as $w)
                @php($isCurrent = isset($workspace) && $workspace && $workspace->id_workspace === $w->id_workspace)
                <a href="{{ route('workspace.show', $w) }}" class="nav-item nav-item-ws {{ $isCurrent ? 'active' : '' }}">
                    <span class="nav-ws-dot">{{ strtoupper(mb_substr($w->nama_workspace, 0, 1)) }}</span>
                    <span class="nav-ws-name">{{ \Illuminate\Support\Str::limit($w->nama_workspace, 24) }}</span>
                </a>
            @empty
                <p class="nav-empty">Belum mengikuti workspace.</p>
            @endforelse
        </nav>

        <div class="sidebar-foot">
            <a href="{{ route('profil.show') }}" class="nav-user">
                <x-avatar :user="auth()->user()" size="sm"/>
                <div class="nav-user-text">
                    <strong>{{ auth()->user()->nama }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-block">
                    <x-icon name="logout"/><span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="icon-btn only-mobile" data-sidebar-open aria-label="Buka menu">
                <x-icon name="menu"/>
            </button>
            <div class="topbar-title">
                <h1>@yield('title')</h1>
                @hasSection('subtitle')<p>@yield('subtitle')</p>@endif
            </div>
            <div class="topbar-actions">
                <div class="dropdown" data-dropdown>
                    <button type="button" class="icon-btn has-badge" data-dropdown-toggle aria-label="Notifikasi">
                        <x-icon name="bell"/>
                        @if ($unreadCount > 0)
                            <span class="dot-badge" data-notif-badge>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-notif">
                        <div class="dd-head">
                            <strong>Notifikasi</strong>
                            <a href="{{ route('notifikasi.index') }}">Lihat semua</a>
                        </div>
                        @forelse ($recentNotifs as $n)
                            @php($ico = jenis_icon($n->jenis))
                            <a class="dd-item @unless($n->status_baca) dd-unread @endunless" href="{{ route('notifikasi.index') }}">
                                <span class="dd-icon jenis-{{ $n->jenis }}"><x-icon name="{{ $ico }}"/></span>
                                <span class="dd-body">
                                    <span class="dd-text">{{ \Illuminate\Support\Str::limit($n->pesan, 72) }}</span>
                                    <small>{{ format_waktu($n->waktu_kirim) }}</small>
                                </span>
                            </a>
                        @empty
                            <p class="dd-empty">Belum ada notifikasi.</p>
                        @endforelse
                    </div>
                </div>

                <div class="dropdown" data-dropdown>
                    <button type="button" class="avatar-btn" data-dropdown-toggle aria-label="Menu akun">
                        <x-avatar :user="auth()->user()" size="sm"/>
                    </button>
                    <div class="dropdown-menu dropdown-right">
                        <div class="dd-user">
                            <strong>{{ auth()->user()->nama }}</strong>
                            <small>{{ auth()->user()->email }}</small>
                        </div>
                        <a href="{{ route('profil.show') }}" class="dd-action">
                            <x-icon name="user"/> Profil Saya
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dd-action dd-danger">
                                <x-icon name="logout"/> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-content">
            @yield('content')
        </main>
    </div>
</div>

@if (session('sukses'))<div data-toast="sukses" data-msg="{{ session('sukses') }}"></div>@endif
@if (session('gagal'))<div data-toast="gagal" data-msg="{{ session('gagal') }}"></div>@endif
@if (session('info'))<div data-toast="info" data-msg="{{ session('info') }}"></div>@endif

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
