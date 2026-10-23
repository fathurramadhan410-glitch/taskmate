<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TaskMate — Student Collaboration &amp; Task Management</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
</head>
<body class="landing-body">

<nav class="landing-nav">
    <div class="landing-container landing-nav-inner">
        <a href="{{ route('landing') }}" class="landing-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate">
            <span>TaskMate</span>
        </a>
        <div class="landing-links">
            <a href="#fitur">Fitur</a>
            <a href="#alur">Cara Kerja</a>
            <a href="#keamanan">Keamanan</a>
        </div>
        <div class="landing-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Buka Dashboard</a>
            @endauth
            @guest
                <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Daftar Gratis</a>
            @endguest
        </div>
    </div>
</nav>

<header class="landing-hero">
    <div class="landing-container hero-grid">
        <div class="hero-copy">
            <span class="hero-eyebrow"><x-icon name="check-circle"/> Untuk mahasiswa &amp; kelompok belajar</span>
            <h1>Kelola tugas kelompok <em>tanpa drama</em>, dari jobdesk sampai draf final.</h1>
            <p class="hero-sub">
                TaskMate menyatukan workspace kelompok, pembagian tugas, Kanban board, repository draf
                ber-versi, chat realtime, dan pengingat deadline dalam <strong>satu aplikasi</strong> —
                berhenti mencari jawaban di ribuan pesan grup.
            </p>
            <div class="hero-cta">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Mulai Sekarang <x-icon name="arrow-right"/></a>
                    <a href="{{ route('login') }}" class="btn btn-outline btn-lg">
                        <svg class="icon" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                        Masuk dengan Google
                    </a>
                @endguest
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">Buka Workspace Saya <x-icon name="arrow-right"/></a>
                @endauth
            </div>
            <ul class="hero-points">
                <li><x-icon name="check"/> Workspace + undangan QR Code</li>
                <li><x-icon name="check"/> Kanban 4 status + riwayat</li>
                <li><x-icon name="check"/> File versioning v1, v2, v3…</li>
                <li><x-icon name="check"/> Pengingat deadline otomatis</li>
            </ul>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="hero-board">
                <div class="hero-board-head">
                    <span class="hb-dot"></span><span class="hb-dot"></span><span class="hb-dot"></span>
                    <span class="hb-title">Workspace: Proyek Akhir UI/UX</span>
                </div>
                <div class="hero-board-cols">
                    <div class="hb-col">
                        <p>To-Do <b>3</b></p>
                        <div class="hb-card hb-blue"><b>Wireframe homepage</b><span>Fitri · H-4</span></div>
                        <div class="hb-card hb-blue"><b>Riset kompetitor</b><span>Idhafi · H-6</span></div>
                    </div>
                    <div class="hb-col">
                        <p>In Progress <b>2</b></p>
                        <div class="hb-card hb-cyan"><b>Desain mockup</b><span>Nazalah · H-2</span></div>
                    </div>
                    <div class="hb-col">
                        <p>Review <b>1</b></p>
                        <div class="hb-card hb-purple"><b>Laporan bab 1</b><span>Sholatiah · H-1</span></div>
                    </div>
                    <div class="hb-col">
                        <p>Done <b>5</b></p>
                        <div class="hb-card hb-green"><b>Proposal tim</b><span>✓ Disetujui</span></div>
                    </div>
                </div>
            </div>
            <div class="hero-float hf-1">
                <x-icon name="bell"/><div><b>Pengingat H-1</b><span>Bab 2 dikumpulkan besok!</span></div>
            </div>
            <div class="hero-float hf-2">
                <x-icon name="qr"/><div><b>Undangan QR</b><span>Scan untuk bergabung</span></div>
            </div>
        </div>
    </div>
</header>

<section class="landing-section landing-stats">
    <div class="landing-container stats-grid">
        <div class="stat-card"><b>1 Tempat</b><span>Semua kebutuhan kerja kelompok</span></div>
        <div class="stat-card"><b>4 Status</b><span>To-Do, In Progress, Review, Done</span></div>
        <div class="stat-card"><b>∞ Versi</b><span>Draf tersimpan rapi tanpa tertimpa</span></div>
        <div class="stat-card"><b>07.00 WITA</b><span>Pengingat deadline otomatis H-3, H-1, hari-H</span></div>
    </div>
</section>

<section class="landing-section" id="fitur">
    <div class="landing-container">
        <div class="section-head">
            <span class="section-eyebrow">Fitur Unggulan</span>
            <h2>Semua yang kelompok Anda butuhkan, sudah ada di sini</h2>
            <p>Dirancang khusus untuk alur kerja mahasiswa: dari pembagian jobdesk sampai pengumpulan draf final.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <span class="feature-icon fi-blue"><x-icon name="users"/></span>
                <h3>Workspace Kelompok</h3>
                <p>Satu ruang kerja per mata kuliah atau judul tugas. Isinya: anggota, tugas, berkas, dan chat — terpisah rapi antar kelompok.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon fi-cyan"><x-icon name="qr"/></span>
                <h3>Undangan Link &amp; QR Code</h3>
                <p>Undang anggota lewat link bertanda tangan atau QR Code yang tinggal discan. Ada saklar aktif, masa berlaku, dan kode baru untuk keamanan.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon fi-purple"><x-icon name="board"/></span>
                <h3>Kanban Board Fokus</h3>
                <p>Kolom status disembunyikan dulu — klik status To-Do, In Progress, Review, atau Done untuk menampilkannya. Bebas dari papan yang penuh sesak.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon fi-teal"><x-icon name="folder"/></span>
                <h3>Repository Ber-versi</h3>
                <p>Unggah draf dengan nama sama dan TaskMate menyimpannya sebagai v1, v2, v3… Berkas lama tidak pernah tertimpa. PDF bisa dibaca langsung.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon fi-green"><x-icon name="chat"/></span>
                <h3>Chat Realtime</h3>
                <p>Obrolan singkat per workspace yang terhubung dengan tugas. Pesan muncul otomatis tanpa perlu refresh halaman.</p>
            </div>
            <div class="feature-card">
                <span class="feature-icon fi-red"><x-icon name="clock"/></span>
                <h3>Pengingat Deadline</h3>
                <p>Sistem memantau deadline setiap hari pukul 07.00 WITA dan mengirim pengingat H-3, H-1, hari-H, plus peringatan keterlambatan.</p>
            </div>
        </div>
    </div>
</section>

<section class="landing-section landing-alt" id="alur">
    <div class="landing-container">
        <div class="section-head">
            <span class="section-eyebrow">Cara Kerja</span>
            <h2>Empat langkah, tugas kelompok langsung terpantau</h2>
        </div>
        <div class="steps-grid">
            <div class="step-card"><span>1</span><h3>Buat workspace</h3><p>Project Leader membuat ruang kerja untuk satu mata kuliah atau judul tugas, lalu membagikan link/QR undangan.</p></div>
            <div class="step-card"><span>2</span><h3>Bagi jobdesk &amp; deadline</h3><p>Tiap tugas punya penanggung jawab dan deadline. Sistem menolak deadline yang sudah lewat dari hari ini.</p></div>
            <div class="step-card"><span>3</span><h3>Kerjakan &amp; pantau</h3><p>Member memindahkan status tugasnya di Kanban, berdiskusi di chat, dan mengunggah draf ke repository.</p></div>
            <div class="step-card"><span>4</span><h3>Review &amp; selesai</h3><p>Tugas masuk Review, Project Leader menyetujui (Done) atau mengembalikannya. Draf final tersimpan rapi per versi.</p></div>
        </div>
    </div>
</section>

<section class="landing-section landing-security" id="keamanan">
    <div class="landing-container security-grid">
        <div class="security-copy">
            <span class="section-eyebrow">Keamanan Undangan</span>
            <h2>Bergabung itu mudah, tapi tidak untuk sembarang orang</h2>
            <ul class="security-list">
                <li><x-icon name="shield"/><div><b>Link bertanda tangan (signed URL)</b><span>Tanda tangan digital membuat link tidak bisa dimanipulasi oleh pihak lain.</span></div></li>
                <li><x-icon name="lock"/><div><b>Wajib punya akun</b><span>Link hanya membuka halaman konfirmasi setelah pengunjung masuk/daftar.</span></div></li>
                <li><x-icon name="eye"/><div><b>Halaman konfirmasi transparan</b><span>Pengguna melihat nama workspace &amp; leader dulu, baru memutuskan bergabung.</span></div></li>
                <li><x-icon name="refresh"/><div><b>Saklar aktif, masa berlaku, &amp; kode baru</b><span>Leader bisa mematikan undangan, membatasi masa berlaku, atau mencabut semua link lama kapan pun.</span></div></li>
            </ul>
        </div>
        <div class="security-visual" aria-hidden="true">
            <div class="qr-card">
                <div class="qr-frame"><x-icon name="qr"/></div>
                <b>Scan untuk bergabung</b>
                <span>workspace: Proyek Akhir UI/UX</span>
            </div>
        </div>
    </div>
</section>

<section class="landing-cta">
    <div class="landing-container">
        <h2>Siap membuat kelompok Anda jauh lebih rapi?</h2>
        <p>Daftar gratis, buat workspace pertama, dan undang teman-teman Anda dalam hitungan detik.</p>
        <div class="hero-cta hero-cta-center">
            @guest
                <a href="{{ route('register') }}" class="btn btn-light btn-lg">Daftar Sekarang</a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Masuk</a>
            @endguest
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg">Buka Dashboard</a>
            @endauth
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div class="landing-container footer-grid">
        <div class="footer-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate">
            <p>Student Collaboration &amp; Task Management — aplikasi manajemen tugas dan berkas kerja kelompok.</p>
        </div>
        <div class="footer-col">
            <b>Navigasi</b>
            <a href="#fitur">Fitur</a>
            <a href="#alur">Cara Kerja</a>
            <a href="#keamanan">Keamanan</a>
        </div>
        <div class="footer-col">
            <b>Aplikasi</b>
            <a href="{{ route('login') }}">Masuk</a>
            <a href="{{ route('register') }}">Daftar</a>
        </div>
        <div class="footer-col">
            <b>Tim Pengembang</b>
            <span>Kelompok 5 — Sistem Informasi Kota Cerdas</span>
            <span>Politeknik Negeri Banjarmasin, 2026</span>
            <span>Fathur · Idhafi · Nazalah · Sholatiah</span>
        </div>
    </div>
    <div class="landing-container footer-bottom">
        <span>© {{ date('Y') }} TaskMate. Seluruh hak cipta dilindungi.</span>
    </div>
</footer>

<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
