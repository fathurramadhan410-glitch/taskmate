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
            <span class="logo-chip"><img src="{{ asset('images/logo.png') }}" alt="Logo TaskMate"></span>
            <span>TaskMate</span>
        </a>
        <div class="landing-links">
            <a href="#fitur">Fitur</a>
            <a href="#alur">Cara Kerja</a>
            <a href="#testimoni">Testimoni</a>
            <a href="#faq">FAQ</a>
            <a href="#keamanan">Keamanan</a>
        </div>
        <div class="landing-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-light">Buka Dashboard</a>
            @endauth
            @guest
                <a href="{{ route('login') }}" class="btn btn-light">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-light">Daftar Gratis</a>
            @endguest
        </div>
        <button type="button" class="nav-burger" id="navBurger"
                aria-expanded="false" aria-controls="navMobile" aria-label="Buka menu navigasi">
            <x-icon name="menu" class="i-menu"/>
            <x-icon name="x" class="i-close"/>
        </button>
    </div>

    <div class="nav-mobile" id="navMobile">
        <a href="#fitur">Fitur</a>
        <a href="#alur">Cara Kerja</a>
        <a href="#testimoni">Testimoni</a>
        <a href="#faq">FAQ</a>
        <a href="#keamanan">Keamanan</a>
        <div class="nav-mobile-cta">
            @guest
                <a href="{{ route('login') }}" class="btn btn-light">Masuk</a>
                <a href="{{ route('register') }}" class="btn btn-light">Daftar</a>
            @endguest
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-light">Buka Dashboard</a>
            @endauth
        </div>
    </div>
</nav>

<header class="landing-hero">
    <div class="landing-container hero-grid">
        <div class="hero-copy">
            <span class="hero-eyebrow"><x-icon name="check-circle"/> Untuk pelajar SMP, SMA/SMK, &amp; mahasiswa</span>
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
            <div class="hero-proof">
                <div class="hp-avatars" aria-hidden="true">
                    <span>N</span><span>B</span><span>A</span><span>+</span>
                </div>
                <p><b>Dipakai lintas jenjang</b> — dari pelajar SMP, SMA/SMK, sampai mahasiswa dalam satu platform yang sama.</p>
            </div>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="hero-blob"></div>
            <div class="hero-board">
                <div class="hero-board-head">
                    <span class="hb-dot"></span><span class="hb-dot"></span><span class="hb-dot"></span>
                    <span class="hb-title">Workspace: Proyek Akhir UI/UX</span>
                </div>
                <div class="hero-board-cols">
                    <div class="hb-col">
                        <p>To-Do <b>3</b></p>
                        <div class="hb-card hb-blue"><b>Wireframe homepage</b><span>Dimas · H-4</span></div>
                        <div class="hb-card hb-blue"><b>Riset kompetitor</b><span>Bagas · H-6</span></div>
                    </div>
                    <div class="hb-col">
                        <p>In Progress <b>2</b></p>
                        <div class="hb-card hb-cyan"><b>Desain mockup</b><span>Ayu · H-2</span></div>
                    </div>
                    <div class="hb-col">
                        <p>Review <b>1</b></p>
                        <div class="hb-card hb-purple"><b>Laporan bab 1</b><span>Raka · H-1</span></div>
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

<section class="jenjang-strip" aria-label="Jenjang yang didukung TaskMate">
    <div class="landing-container jenjang-inner">
        <span class="jenjang-label">Mendukung semua jenjang</span>
        <div class="jenjang-chips">
            <span class="jj-chip"><i class="jj-dot jd-blue"></i> SMP</span>
            <span class="jj-chip"><i class="jj-dot jd-cyan"></i> SMA</span>
            <span class="jj-chip"><i class="jj-dot jd-amber"></i> SMK</span>
            <span class="jj-chip"><i class="jj-dot jd-purple"></i> Mahasiswa</span>
            <span class="jj-chip"><i class="jj-dot jd-green"></i> Komunitas belajar</span>
        </div>
    </div>
</section>

<section class="landing-section landing-stats">
    <div class="landing-container stats-grid">
        <div class="stat-card reveal">
            <span class="stat-ico si-blue"><x-icon name="users"/></span>
            <b>1 Tempat</b>
            <span>Semua kebutuhan kerja kelompok</span>
        </div>
        <div class="stat-card reveal" style="transition-delay:.07s">
            <span class="stat-ico si-purple"><x-icon name="board"/></span>
            <b>4 Status</b>
            <span>To-Do, In Progress, Review, Done</span>
        </div>
        <div class="stat-card reveal" style="transition-delay:.14s">
            <span class="stat-ico si-teal"><x-icon name="folder"/></span>
            <b>∞ Versi</b>
            <span>Draf tersimpan rapi tanpa tertimpa</span>
        </div>
        <div class="stat-card reveal" style="transition-delay:.21s">
            <span class="stat-ico si-red"><x-icon name="clock"/></span>
            <b>07.00 WITA</b>
            <span>Pengingat deadline otomatis H-3, H-1, hari-H</span>
        </div>
    </div>
</section>

<section class="landing-section" id="fitur">
    <div class="landing-container">
        <div class="section-head reveal">
            <span class="section-eyebrow">Fitur Unggulan</span>
            <h2>Semua yang kelompok Anda butuhkan, sudah ada di sini</h2>
            <p>Dirancang khusus untuk alur kerja pelajar dan mahasiswa: dari pembagian jobdesk sampai pengumpulan draf final.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card reveal">
                <span class="feature-icon fi-blue"><x-icon name="users"/></span>
                <h3>Workspace Kelompok</h3>
                <p>Satu ruang kerja per mata pelajaran, mata kuliah, atau judul proyek. Isinya: anggota, tugas, berkas, dan chat — terpisah rapi antar kelompok.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay:.07s">
                <span class="feature-icon fi-cyan"><x-icon name="qr"/></span>
                <h3>Undangan Link &amp; QR Code</h3>
                <p>Undang anggota lewat link bertanda tangan atau QR Code yang tinggal discan. Ada saklar aktif, masa berlaku, dan kode baru untuk keamanan.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay:.14s">
                <span class="feature-icon fi-purple"><x-icon name="board"/></span>
                <h3>Kanban Board Fokus</h3>
                <p>Kolom status disembunyikan dulu — klik status To-Do, In Progress, Review, atau Done untuk menampilkannya. Bebas dari papan yang penuh sesak.</p>
            </div>
            <div class="feature-card reveal">
                <span class="feature-icon fi-teal"><x-icon name="folder"/></span>
                <h3>Repository Ber-versi</h3>
                <p>Unggah draf dengan nama sama dan TaskMate menyimpannya sebagai v1, v2, v3… Berkas lama tidak pernah tertimpa. PDF bisa dibaca langsung.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay:.07s">
                <span class="feature-icon fi-green"><x-icon name="chat"/></span>
                <h3>Chat Realtime</h3>
                <p>Obrolan singkat per workspace yang terhubung dengan tugas. Pesan muncul otomatis tanpa perlu refresh halaman.</p>
            </div>
            <div class="feature-card reveal" style="transition-delay:.14s">
                <span class="feature-icon fi-red"><x-icon name="clock"/></span>
                <h3>Pengingat Deadline</h3>
                <p>Sistem memantau deadline setiap hari pukul 07.00 WITA dan mengirim pengingat H-3, H-1, hari-H, plus peringatan keterlambatan.</p>
            </div>
        </div>
    </div>
</section>

<section class="landing-section landing-alt" id="alur">
    <div class="landing-container">
        <div class="section-head reveal">
            <span class="section-eyebrow">Cara Kerja</span>
            <h2>Empat langkah, tugas kelompok langsung terpantau</h2>
        </div>
        <div class="steps-grid">
            <div class="step-card reveal"><span>1</span><h3>Buat workspace</h3><p>Project Leader membuat ruang kerja untuk satu pelajaran, mata kuliah, atau judul tugas, lalu membagikan link/QR undangan.</p></div>
            <div class="step-card reveal" style="transition-delay:.07s"><span>2</span><h3>Bagi jobdesk &amp; deadline</h3><p>Tiap tugas punya penanggung jawab dan deadline. Sistem menolak deadline yang sudah lewat dari hari ini.</p></div>
            <div class="step-card reveal" style="transition-delay:.14s"><span>3</span><h3>Kerjakan &amp; pantau</h3><p>Member memindahkan status tugasnya di Kanban, berdiskusi di chat, dan mengunggah draf ke repository.</p></div>
            <div class="step-card reveal" style="transition-delay:.21s"><span>4</span><h3>Review &amp; selesai</h3><p>Tugas masuk Review, Project Leader menyetujui (Done) atau mengembalikannya. Draf final tersimpan rapi per versi.</p></div>
        </div>
    </div>
</section>

<section class="landing-section landing-testi" id="testimoni">
    <div class="landing-container">
        <div class="section-head reveal">
            <span class="section-eyebrow">Testimoni</span>
            <h2>Kata mereka yang sudah mencoba</h2>
            <p>Cerita singkat pengguna dari berbagai jenjang — dari tugas harian sampai proyek akhir.</p>
        </div>
        <div class="testi-grid">
            <figure class="testi-card reveal">
                <div class="testi-stars" aria-label="Rating 5 dari 5">★★★★★</div>
                <blockquote class="testi-quote">
                    “Dulu jobdesk kelompok selalu hilang di antara ratusan chat WA. Sekarang semua tugas ada statusnya,
                    jelas siapa ngerjain apa dan kapan deadline-nya.”
                </blockquote>
                <figcaption class="testi-person">
                    <span class="tp-ava t-blue" aria-hidden="true">N</span>
                    <div><b>Nabila S.</b><span>Siswa SMA — Kelompok projek P5</span></div>
                </figcaption>
            </figure>
            <figure class="testi-card reveal" style="transition-delay:.07s">
                <div class="testi-stars" aria-label="Rating 5 dari 5">★★★★★</div>
                <blockquote class="testi-quote">
                    “Fitur repository ber-versi juara. Laporan PKL kami sampai revisi 4 kali dan draf lamanya masih
                    semua bisa dibuka — nggak ada lagi file ‘final-final-revisi.docx’.”
                </blockquote>
                <figcaption class="testi-person">
                    <span class="tp-ava t-amber" aria-hidden="true">B</span>
                    <div><b>Bagas R.</b><span>Siswa SMK — Tim laporan PKL</span></div>
                </figcaption>
            </figure>
            <figure class="testi-card reveal" style="transition-delay:.14s">
                <div class="testi-stars" aria-label="Rating 5 dari 5">★★★★★</div>
                <blockquote class="testi-quote">
                    “Pengingat H-1 tiap pagi jam 7 itu penyelamat. Anggota kelompok jadi ingat sendiri deadline-nya
                    tanpa saya harus chase satu per satu di grup.”
                </blockquote>
                <figcaption class="testi-person">
                    <span class="tp-ava t-purple" aria-hidden="true">A</span>
                    <div><b>Ayu P.</b><span>Mahasiswa — Ketua kelompok skripsi</span></div>
                </figcaption>
            </figure>
        </div>
    </div>
</section>

<section class="landing-section landing-security" id="keamanan">
    <div class="landing-container security-grid">
        <div class="security-copy">
            <span class="section-eyebrow reveal" style="display:inline-block">Keamanan Undangan</span>
            <h2 class="reveal">Bergabung itu mudah, tapi tidak untuk sembarang orang</h2>
            <ul class="security-list">
                <li class="reveal"><x-icon name="shield"/><div><b>Link bertanda tangan (signed URL)</b><span>Tanda tangan digital membuat link tidak bisa dimanipulasi oleh pihak lain.</span></div></li>
                <li class="reveal" style="transition-delay:.07s"><x-icon name="lock"/><div><b>Wajib punya akun</b><span>Link hanya membuka halaman konfirmasi setelah pengunjung masuk/daftar.</span></div></li>
                <li class="reveal" style="transition-delay:.14s"><x-icon name="eye"/><div><b>Halaman konfirmasi transparan</b><span>Pengguna melihat nama workspace &amp; leader dulu, baru memutuskan bergabung.</span></div></li>
                <li class="reveal" style="transition-delay:.21s"><x-icon name="refresh"/><div><b>Saklar aktif, masa berlaku, &amp; kode baru</b><span>Leader bisa mematikan undangan, membatasi masa berlaku, atau mencabut semua link lama kapan pun.</span></div></li>
            </ul>
        </div>
        <div class="security-visual reveal" aria-hidden="true">
            <div class="qr-card">
                <div class="qr-frame"><x-icon name="qr"/></div>
                <b>Scan untuk bergabung</b>
                <span>workspace: Proyek Akhir UI/UX</span>
            </div>
        </div>
    </div>
</section>

<section class="landing-section landing-faq" id="faq">
    <div class="landing-container">
        <div class="section-head reveal">
            <span class="section-eyebrow">FAQ</span>
            <h2>Pertanyaan yang sering diajukan</h2>
        </div>
        <div class="faq-list">
            <details class="faq-item reveal">
                <summary>Apakah TaskMate gratis?<span class="faq-plus" aria-hidden="true"></span></summary>
                <p class="faq-body">Ya. TaskMate gratis dipakai untuk semua jenjang — pelajar SMP, SMA/SMK, mahasiswa, maupun komunitas belajar. Cukup daftar dengan email, tanpa biaya apa pun.</p>
            </details>
            <details class="faq-item reveal" style="transition-delay:.06s">
                <summary>Saya pelajar SMP/SMA/SMK, apakah bisa ikut memakai?<span class="faq-plus" aria-hidden="true"></span></summary>
                <p class="faq-body">Tentu. TaskMate memang dirancang lintas jenjang. Bahasa antarmukanya sederhana dan alur Kanban-nya mudah dipahami, jadi cocok untuk tugas kelompok di sekolah maupun kampus.</p>
            </details>
            <details class="faq-item reveal" style="transition-delay:.12s">
                <summary>Bagaimana cara teman satu kelompok bergabung?<span class="faq-plus" aria-hidden="true"></span></summary>
                <p class="faq-body">Project Leader membagikan link undangan atau QR Code workspace. Teman cukup scan/link, masuk atau daftar dulu, lalu konfirmasi bergabung — namanya langsung muncul di anggota workspace.</p>
            </details>
            <details class="faq-item reveal" style="transition-delay:.18s">
                <summary>Apakah draf lama bisa hilang kalau diunggah ulang?<span class="faq-plus" aria-hidden="true"></span></summary>
                <p class="faq-body">Tidak. Repository TaskMate menyimpan berkas dengan nama sama sebagai versi baru (v1, v2, v3…). Semua revisi tetap tersimpan dan bisa dibuka kapan pun, termasuk PDF-nya.</p>
            </details>
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
            <p>Student Collaboration &amp; Task Management — aplikasi manajemen tugas dan berkas kerja kelompok untuk semua jenjang pelajar.</p>
        </div>
        <div class="footer-col">
            <b>Navigasi</b>
            <a href="#fitur">Fitur</a>
            <a href="#alur">Cara Kerja</a>
            <a href="#testimoni">Testimoni</a>
            <a href="#faq">FAQ</a>
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
<script>
    /* Nurunin nav mobile + animasi reveal khusus landing page */
    (function () {
        'use strict';

        var burger = document.getElementById('navBurger');
        var menu = document.getElementById('navMobile');

        function tutupMenu() {
            if (!menu || !burger) return;
            menu.classList.remove('open');
            burger.setAttribute('aria-expanded', 'false');
            burger.setAttribute('aria-label', 'Buka menu navigasi');
        }

        if (burger && menu) {
            burger.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = menu.classList.toggle('open');
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
                burger.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
            });
            menu.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', tutupMenu);
            });
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.landing-nav')) tutupMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') tutupMenu();
            });
        }

        /* Reveal on scroll — elemen .reveal muncul halus saat masuk viewport */
        var targets = document.querySelectorAll('.reveal');
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if ('IntersectionObserver' in window && !reduced) {
            targets.forEach(function (el) { el.classList.add('reveal-armed'); });
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) {
                    if (en.isIntersecting) {
                        en.target.classList.add('reveal-in');
                        io.unobserve(en.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            targets.forEach(function (el) { io.observe(el); });
        }
        /* tanpa IO / prefer gerak minim: elemen tetap tampil normal (tidak di-armed) */
    })();
</script>
</body>
</html>