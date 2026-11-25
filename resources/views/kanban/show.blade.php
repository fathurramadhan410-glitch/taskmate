@extends('layouts.app')

{{-- ============================================================
     LANGKAH 43 - PERBAIKAN DRAG & DROP "memindahkan tugas ke null"
     ============================================================
     AKAR MASALAH: pada penanganan drop, status kolom tujuan diambil
     dari elemen yang SALAH, yaitu section.kanban-col:
       var target = kolom.getAttribute('data-drop-kolom');
     Atribut data-drop-kolom TIDAK ADA pada section.kanban-col
     (atribut itu milik div.kc-body di dalamnya), sehingga hasilnya
     SELALU null. Akibatnya pemeriksaan hak akses selalu gagal dan
     muncul toast "Anda tidak berhak memindahkan tugas ke null."
     SOLUSI DI VERSI INI:
     1) Status kolom tujuan kini diambil dari zona drop yang benar:
        zona.getAttribute('data-drop-kolom') - zona adalah elemen yang
        benar-benar membawa atribut tersebut.
     2) Teks toast dijaga agar tidak pernah menampilkan kata "null".
     FITUR TETAP SAMA (LANGKAH 36 s.d. 42):
     a) Hak akses FR-013: Leader bebas maju/mundur/Done; Member hanya
        tugas miliknya maju satu tahap tanpa Done; pengguna lain
        tanpa tombol. Berlaku di kartu, modal detail, dan drag-drop;
        server tetap penjaga terakhir.
     b) Tombol "+ Tambah Tugas", "Ubah Tugas", "Hapus Tugas" hanya
        untuk Leader; "Unggah Draf" untuk Leader dan PJ tugas.
     c) Perpindahan status via AJAX ke kanban.move dengan toast
        sukses/gagal tanpa pindah halaman.
     d) Perbaikan LANGKAH 42: nilai status pada TOMBOL SUBMIT yang
        diklik kini ikut terkirim (perbaikan "The status field is
        required.").
     e) Semua status selalu tampil saat kanban dibuka; menyembunyikan
        kolom hanya berlaku pada sesi tersebut dan tidak tersimpan.
     CATATAN: KanbanController TIDAK perlu diubah.
     ============================================================ --}}

@section('title', 'Kanban — ' . ($workspace->nama ?? $workspace->name ?? 'Workspace'))
@section('page-title', 'Kanban — ' . ($workspace->nama ?? $workspace->name ?? 'Workspace'))
@section('page-subtitle', 'Semua status selalu tampil setiap kali kanban dibuka — menyembunyikan hanya aksi Anda sendiri dan tidak tersimpan')

@section('content')

@php
    /* ==================================================================
       BLOK PENYESUAIAN — nama route di bawah sudah DISAMAKAN dengan
       routes/web.php project TaskMate (tanpa perubahan lagi).
       ================================================================== */
    $rStore   = 'tugas.store';    // POST   : simpan tugas baru
    $rMove    = 'kanban.move';    // PATCH  : /tugas/{task}/status — ubah status
    $rEdit    = 'tugas.edit';     // GET    : data JSON untuk mengisi form ubah
    $rDestroy = 'tugas.destroy';  // DELETE : hapus tugas

    if (!function_exists('tmTanggal')) {
        function tmTanggal($nilai) {
            if (empty($nilai)) return '—';
            try { return \Carbon\Carbon::parse($nilai)->format('d/m/Y H:i'); }
            catch (\Throwable $e) { return '—'; }
        }
    }
    if (!function_exists('tmInisial')) {
        function tmInisial($nama) {
            $nama = trim((string) $nama);
            if ($nama === '') return '?';
            $kata = preg_split('/\s+/', $nama);
            if (count($kata) === 1) return strtoupper(mb_substr($kata[0], 0, 2));
            return strtoupper(mb_substr($kata[0], 0, 1) . mb_substr($kata[1], 0, 1));
        }
    }
    if (!function_exists('tmNamaUser')) {
        function tmNamaUser($user) {
            if (!$user) return '—';
            return $user->nama ?? $user->name ?? '—';
        }
    }
    if (!function_exists('tmStatus')) {
        function tmStatus($tugas) {
            $s = strtolower(trim((string) ($tugas->status ?? 'todo')));
            $s = str_replace([' ', '-'], '_', $s);
            return in_array($s, ['todo', 'in_progress', 'review', 'done'], true) ? $s : 'todo';
        }
    }
    if (!function_exists('tmLabelStatus')) {
        function tmLabelStatus($status) {
            $map = ['todo' => 'To-Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
            return $map[$status] ?? ucfirst((string) $status);
        }
    }
    if (!function_exists('tmKelasStatus')) {
        function tmKelasStatus($status) { return 'st-' . str_replace('_', '-', (string) $status); }
    }
    if (!function_exists('tmInfoDeadline')) {
        function tmInfoDeadline($tugas) {
            $dl = $tugas->deadline ?? null;
            if (tmStatus($tugas) === 'done') return ['dl-done', tmTanggal($dl)];
            if (empty($dl)) return ['dl-netral', 'Tanpa deadline'];
            try { $batas = \Carbon\Carbon::parse($dl); } catch (\Throwable $e) { return ['dl-netral', '—']; }
            /* SRS 3.1: hari-H dan terlambat = merah, H-3 s.d. H-1 = kuning */
            if ($batas->isToday()) return ['dl-merah', 'Hari ini · ' . tmTanggal($dl)];
            if (now()->greaterThan($batas)) {
                /* LANGKAH 42: abs() karena Carbon 3 mengembalikan nilai
                   bertanda (negatif bila batas sudah lewat) */
                $lewat = (int) floor(abs(now()->diffInHours($batas)) / 24);
                return ['dl-merah', 'Terlambat ' . max(1, $lewat) . ' hari · ' . tmTanggal($dl)];
            }
            $sisa = (int) now()->startOfDay()->diffInDays($batas->copy()->startOfDay());
            if ($sisa <= 3) return ['dl-kuning', 'H-' . max(1, $sisa) . ' · ' . tmTanggal($dl)];
            return ['dl-netral', tmTanggal($dl)];
        }
    }
    if (!function_exists('tmPjDari')) {
        function tmPjDari($tugas) { return $tugas->penanggungJawab ?? $tugas->pj ?? $tugas->user ?? null; }
    }
    if (!function_exists('tmJudulDari')) {
        function tmJudulDari($tugas) { return $tugas->judul ?? $tugas->title ?? 'Tanpa judul'; }
    }
    if (!function_exists('tmIdTugas')) {
        /* Kunci utama tabel tasks adalah id_task — JANGAN memakai ->id */
        function tmIdTugas($tugas) { return $tugas->id_task ?? $tugas->id ?? null; }
    }
    if (!function_exists('tmPindahanBoleh')) {
        /**
         * Daftar status tujuan yang BOLEH dipilih pengguna untuk sebuah tugas
         * (SRS FR-013). Dipakai untuk kartu, modal detail, dan drag & drop.
         * Hasil dimatching (memoisasi) agar konsisten dan hemat.
         */
        function tmPindahanBoleh($tugas, $peran, $idUser) {
            static $cache = [];
            $kunci = tmIdTugas($tugas) . '|' . (string) $peran . '|' . (string) $idUser;
            if (array_key_exists($kunci, $cache)) return $cache[$kunci];

            $urutanTm = ['todo', 'in_progress', 'review', 'done'];
            $peta = [
                'todo'        => ['in_progress'],
                'in_progress' => ['todo', 'review'],
                'review'      => ['in_progress', 'done'],
                'done'        => ['review'],
            ];

            $status = tmStatus($tugas);
            $pos = array_search($status, $urutanTm, true);
            if ($pos === false) { $cache[$kunci] = []; return $cache[$kunci]; }

            /* 1) Project Leader: semua arah, termasuk Done */
            if ($peran === 'leader') { $cache[$kunci] = $peta[$status]; return $cache[$kunci]; }

            /* 2) Member: hanya tugas milik sendiri, maju satu tahap, tanpa Done */
            $pjId = $tugas->id_penanggung_jawab ?? $tugas->penanggung_jawab_id ?? null;
            if ($idUser === null || $pjId === null || (string) $pjId !== (string) $idUser) {
                $cache[$kunci] = [];
                return $cache[$kunci];
            }
            $hasil = [];
            foreach ($peta[$status] as $sk) {
                $posTarget = array_search($sk, $urutanTm, true);
                if ($posTarget === false || $posTarget <= $pos) continue; /* hanya maju */
                if ($sk === 'done') continue;                             /* Done = hak Leader */
                $hasil[] = $sk;
            }
            $cache[$kunci] = $hasil;
            return $cache[$kunci];
        }
    }

    $statusKanban = [
        ['key' => 'todo',        'label' => 'To-Do',       'chip' => 'chip-todo'],
        ['key' => 'in_progress', 'label' => 'In Progress', 'chip' => 'chip-in_progress'],
        ['key' => 'review',      'label' => 'Review',      'chip' => 'chip-review'],
        ['key' => 'done',        'label' => 'Done',        'chip' => 'chip-done'],
    ];
    $urutan = array_column($statusKanban, 'key');

    /* Identitas & peran pengguna saat ini — dipakai untuk hak akses */
    $wsId      = $workspace->id_workspace ?? $workspace->id ?? null;
    $peranSaya = $peranSaya ?? ($role ?? (!empty($wsId) && function_exists('user_role_in') ? user_role_in((int) $wsId) : null));
    $idSaya    = $idSaya ?? auth()->id();

    $daftarTugas = $tasks ?? $tugases ?? collect();
    $anggotaWs   = $anggota ?? $anggotas ?? $members ?? collect();

    /* Filter sederhana (q = judul, pj = id penanggung jawab) dari form di bawah */
    $q  = trim((string) request('q'));
    $pj = request('pj');
    $daftarTugas = $daftarTugas->filter(function ($t) use ($q, $pj) {
        if ($q !== '' && stripos((string) tmJudulDari($t), $q) === false) return false;
        if ($pj !== null && $pj !== '') {
            $pjUser = tmPjDari($t);
            $pjId   = $pjUser->id_user ?? $pjUser->id ?? ($t->id_penanggung_jawab ?? $t->penanggung_jawab_id ?? null);
            if ((string) $pjId !== (string) $pj) return false;
        }
        return true;
    })->values();

    $perStatus = $daftarTugas->groupBy(fn ($t) => tmStatus($t));
@endphp

<div class="page-toolbar">
    <div class="toolbar-left">
        <h2>Kanban Board</h2>
        <p>{{ $daftarTugas->count() }} tugas pada workspace ini.</p>
    </div>
    <div class="toolbar-right">
        <button type="button" class="btn btn-outline" id="btnKolomSemua" title="Sembunyikan semua kolom status — hanya berlaku di sesi ini, tidak tersimpan">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon ic-hide" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5M15 15l5.25 5.25"/></svg>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon ic-show" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v-4.5m0 4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
            <span class="lb-hide">Sembunyikan Semua</span>
            <span class="lb-show">Tampilkan Semua</span>
        </button>
        @if (($peranSaya ?? null) === 'leader' && Route::has($rStore))
            <button type="button" class="btn btn-primary" id="btnTambahTugas" data-modal-open="modalTugas">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Tugas
            </button>
        @endif
    </div>
</div>

<form class="filter-bar" method="GET" action="{{ route('kanban.show', $workspace) }}">
    <div class="filter-field">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul tugas…">
    </div>
    <div class="filter-field">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        <select name="pj" onchange="this.form.submit()">
            <option value="">Semua penanggung jawab</option>
            @foreach($anggotaWs as $a)
                @php $uid = $a->id_user ?? $a->user_id ?? $a->id; @endphp
                <option value="{{ $uid }}" {{ (string) request('pj') === (string) $uid ? 'selected' : '' }}>{{ tmNamaUser($a->user ?? $a) }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="kanban-chips" id="kanbanChips">
    <div class="chips-row">
        @foreach($statusKanban as $kolom)
            <button type="button" class="k-chip {{ $kolom['chip'] }}" data-kolom="{{ $kolom['key'] }}" aria-expanded="true">
                <span class="k-dot"></span>
                {{ $kolom['label'] }}
                <b class="k-count">{{ $perStatus->get($kolom['key'], collect())->count() }}</b>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon k-chev" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </button>
        @endforeach
    </div>
    <p class="kanban-hint">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        Semua status selalu tampil setiap kali kanban dibuka. Menyembunyikan kolom hanya aksi Anda di sesi ini — begitu pindah halaman lalu kembali ke kanban, semua status muncul lagi otomatis.
    </p>
</div>

<div class="kanban-board" id="kanbanBoard">
    @foreach($statusKanban as $kolom)
        @php $tugasKolom = $perStatus->get($kolom['key'], collect()); @endphp
        <section class="kanban-col" data-kolom="{{ $kolom['key'] }}">
            <header class="kc-head {{ $kolom['chip'] }}">
                <span class="kc-title"><span class="k-dot"></span> {{ $kolom['label'] }}</span>
                <span class="kc-count">{{ $tugasKolom->count() }}</span>
                <button type="button" class="icon-btn kc-close" data-kolom-tutup="{{ $kolom['key'] }}" title="Sembunyikan kolom ini">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </header>
            <div class="kc-body" data-drop-kolom="{{ $kolom['key'] }}">
                @forelse($tugasKolom as $tugas)
                    @php
                        [$dlKelas, $dlTeks] = tmInfoDeadline($tugas);
                        $pjUser = tmPjDari($tugas);
                        $posSekarang = array_search(tmStatus($tugas), $urutan);
                        $pindahanKartu = tmPindahanBoleh($tugas, $peranSaya ?? null, $idSaya ?? null);
                    @endphp
                    <article class="task-card {{ $dlKelas === 'dl-merah' ? 'tc-merah' : ($dlKelas === 'dl-kuning' ? 'tc-kuning' : '') }}" draggable="true"
                        data-tugas="{{ tmIdTugas($tugas) }}"
                        data-pindah="{{ implode(',', $pindahanKartu) }}"
                        data-move-url="{{ Route::has($rMove) ? route($rMove, [$workspace, $tugas]) : '' }}">
                        <button type="button" class="tc-main" data-modal-open="modalDetail{{ tmIdTugas($tugas) }}">
                            <h4>{{ tmJudulDari($tugas) }}</h4>
                            <span class="tc-deadline chip {{ $dlKelas }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                {{ $dlTeks }}
                            </span>
                            <span class="tc-pj">
                                <span class="avatar avatar-xs avatar-fallback" title="{{ tmNamaUser($pjUser) }}">{{ tmInisial(tmNamaUser($pjUser)) }}</span>
                                {{ tmNamaUser($pjUser) }}
                            </span>
                        </button>
                        @if (Route::has($rMove) && count($pindahanKartu) > 0)
                            <form method="POST" action="{{ route($rMove, [$workspace, $tugas]) }}" class="tc-actions" data-status-form aria-label="Aksi status tugas">
                                @csrf
                                @method('PATCH')
                                @foreach($pindahanKartu as $sk)
                                    @php
                                        $posTarget = array_search($sk, $urutan);
                                        $maju = $posTarget !== false && $posTarget > $posSekarang;
                                    @endphp
                                    <button type="submit" name="status" value="{{ $sk }}" class="tc-move {{ $maju ? 'tc-move-fwd' : '' }}" title="Pindah ke {{ tmLabelStatus($sk) }}" aria-label="Pindah ke {{ tmLabelStatus($sk) }}">
                                        @if($maju)
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="kc-empty">Belum ada tugas di sini</p>
                @endforelse
                <p class="kc-drop-hint">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    Tarik kartu ke sini
                </p>
            </div>
        </section>
    @endforeach
</div>

<div class="kanban-empty" id="kanbanEmpty" hidden>
    <p><b>Semua kolom status disembunyikan.</b></p>
    <p class="muted">Klik tombol di bawah untuk memunculkannya kembali — atau cukup pindah halaman lalu kembali ke kanban, semua status akan tampil otomatis.</p>
    <button type="button" class="btn btn-primary btn-sm" data-kolom-semua="tampil">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
        Tampilkan Semua
    </button>
</div>

<p class="kanban-legend">
    <span class="legend-item"><span class="legend-dot ld-kuning"></span> Deadline dekat (H-3 s.d. H-1)</span>
    <span class="legend-item"><span class="legend-dot ld-merah"></span> Hari-H / terlambat</span>
    <span class="legend-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Kartu dapat ditarik antar kolom yang terbuka
    </span>
</p>

{{-- Modal: Tambah / Ubah Tugas — hanya Project Leader --}}
@if (($peranSaya ?? null) === 'leader' && Route::has($rStore))
<div class="modal-overlay" id="modalTugas" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judulModalTugas">
        <div class="modal-head">
            <h3 id="judulModalTugas">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span id="judulModalTugasTeks">Tambah Tugas Baru</span>
            </h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" id="formTugas" action="{{ route($rStore, $workspace) }}" data-url-store="{{ route($rStore, $workspace) }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-body">
                <p class="modal-note">Tugas baru otomatis masuk kolom <b>To-Do</b> dan penanggung jawab menerima notifikasi penugasan.</p>
                <div class="form-group">
                    <label>Judul Tugas <b>*</b></label>
                    <input type="text" name="judul" maxlength="150" value="{{ old('judul') }}" placeholder="cth: Menyusun laporan bab pendahuluan" required>
                    @error('judul')<small style="color:#E53E3E;display:block;margin-top:5px;font-size:12px">{{ $message }}</small>@enderror
                </div>
                <div class="form-group">
                    <label>Deskripsi Pekerjaan</label>
                    <textarea name="deskripsi" rows="3" maxlength="2000" placeholder="Jelaskan hasil yang diharapkan…">{{ old('deskripsi') }}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Penanggung Jawab <b>*</b></label>
                        <select name="id_penanggung_jawab" required>
                            <option value="">— Pilih anggota —</option>
                            @foreach($anggotaWs as $a)
                                @php $uid = $a->id_user ?? $a->user_id ?? $a->id; @endphp
                                <option value="{{ $uid }}" {{ (string) old('id_penanggung_jawab') === (string) $uid ? 'selected' : '' }}>{{ tmNamaUser($a->user ?? $a) }}</option>
                            @endforeach
                        </select>
                        @error('id_penanggung_jawab')<small style="color:#E53E3E;display:block;margin-top:5px;font-size:12px">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Deadline <b>*</b></label>
                        <input type="datetime-local" name="deadline" value="{{ old('deadline') }}" required>
                        @error('deadline')<small style="color:#E53E3E;display:block;margin-top:5px;font-size:12px">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    Simpan Tugas
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal: Detail Tugas (satu per tugas, isi di-render server side) --}}
@foreach($daftarTugas as $tugas)
    @php
        [$dlKelas, $dlTeks] = tmInfoDeadline($tugas);
        $pjUser  = tmPjDari($tugas);
        $pembuat = $tugas->pembuat ?? $tugas->creator ?? null;
        $riwayat = $tugas->logs ?? $tugas->riwayatStatus ?? $tugas->histories ?? collect();
        $semuaBerkas = $tugas->files ?? $tugas->berkas ?? $tugas->dokumen ?? collect();
        $drafList   = $semuaBerkas->filter(fn ($b) => strtolower((string) ($b->kategori ?? '')) === 'draf')->values();
        $materiList = $semuaBerkas->filter(fn ($b) => strtolower((string) ($b->kategori ?? '')) === 'materi')->values();
        $statusTugas = tmStatus($tugas);
        $posSekarang = array_search($statusTugas, $urutan);
        $pindahan   = tmPindahanBoleh($tugas, $peranSaya ?? null, $idSaya ?? null);
        $bisaUnggah = ($peranSaya ?? null) === 'leader'
            || (($idSaya ?? null) !== null && (string) ($tugas->id_penanggung_jawab ?? '') === (string) ($idSaya ?? ''));
    @endphp
    <div class="modal-overlay" id="modalDetail{{ tmIdTugas($tugas) }}" hidden>
        <div class="modal modal-lg" role="dialog" aria-modal="true" aria-labelledby="judulDetail{{ tmIdTugas($tugas) }}">
            <div class="modal-head">
                <h3 id="judulDetail{{ tmIdTugas($tugas) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12"/></svg>
                    Detail Tugas
                </h3>
                <button type="button" class="icon-btn" data-modal-close aria-label="Tutup">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="task-detail">
                    <div class="td-head">
                        <h2>{{ tmJudulDari($tugas) }}</h2>
                        <span class="badge {{ tmKelasStatus($statusTugas) }}">{{ tmLabelStatus($statusTugas) }}</span>
                    </div>

                    {{-- Perbaikan LANGKAH 36: info dalam kartu 2x2 — teks Deadline panjang
                         dan Dibuat oleh tidak pernah saling menumpuk lagi --}}
                    <dl class="td-meta">
                        <div>
                            <dt>Penanggung Jawab</dt>
                            <dd class="td-pj">
                                <span class="avatar avatar-xs avatar-fallback" title="{{ tmNamaUser($pjUser) }}">{{ tmInisial(tmNamaUser($pjUser)) }}</span>
                                {{ tmNamaUser($pjUser) }}
                            </dd>
                        </div>
                        <div>
                            <dt>Deadline</dt>
                            <dd>
                                <span class="chip {{ $dlKelas }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    {{ $dlTeks }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt>Dibuat oleh</dt>
                            <dd>{{ tmNamaUser($pembuat) }}</dd>
                        </div>
                        <div>
                            <dt>Terakhir diubah</dt>
                            <dd>{{ tmTanggal($tugas->updated_at) }}</dd>
                        </div>
                    </dl>

                    <div class="td-section">
                        <h4>Deskripsi Pekerjaan</h4>
                        <p class="pre-wrap">{{ $tugas->deskripsi ?? $tugas->description ?? 'Tidak ada deskripsi.' }}</p>
                    </div>

                    <div class="td-section">
                        <h4>Perbarui Status</h4>
                        @if(Route::has($rMove) && count($pindahan) > 0)
                        <form method="POST" action="{{ route($rMove, [$workspace, $tugas]) }}" data-status-form>
                            @csrf
                            @method('PATCH')
                            <div class="td-moves">
                                @foreach($pindahan as $sk)
                                    @php
                                        $posTarget = array_search($sk, $urutan);
                                        $maju = $posTarget !== false && $posTarget > $posSekarang;
                                    @endphp
                                    <button type="submit" name="status" value="{{ $sk }}" class="btn btn-{{ $maju ? 'primary' : 'outline' }} btn-sm td-move">
                                        @if(!$maju)
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                        @endif
                                        Ke {{ tmLabelStatus($sk) }}
                                        @if($maju)
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            <div class="form-group td-catatan">
                                <label for="catatan{{ tmIdTugas($tugas) }}">Catatan peninjauan (opsional, tersimpan di riwayat)</label>
                                <input type="text" id="catatan{{ tmIdTugas($tugas) }}" name="catatan" maxlength="255" placeholder="cth: Revisi bagian metodologi">
                            </div>
                        </form>
                        @else
                        <p class="muted">Anda tidak dapat mengubah status tugas ini.
                            @if(($peranSaya ?? null) !== 'leader' && ($idSaya ?? null) !== null && (string) ($tugas->id_penanggung_jawab ?? '') === (string) ($idSaya ?? ''))
                                Tugas ini milik Anda — Anda dapat memajukannya satu tahap (To-Do → In Progress → Review), sedangkan Done hanya ditetapkan oleh Project Leader.
                            @elseif(($peranSaya ?? null) !== 'leader')
                                Member hanya dapat memajukan tugas miliknya sendiri satu tahap; Done ditetapkan oleh Project Leader.
                            @endif
                        </p>
                        @endif
                    </div>

                    <div class="td-section">
                        <div class="td-section-head">
                            <h4>Draf &amp; Berkas Tugas ({{ $drafList->count() }})</h4>
                            @if($bisaUnggah && Route::has('repository.index'))
                                <a href="{{ route('repository.index', [$workspace, 'tugas' => tmIdTugas($tugas), 'unggah' => 1]) }}" class="btn btn-outline btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                                    Unggah Draf
                                </a>
                            @endif
                        </div>
                        @if($drafList->isEmpty())
                            <p class="muted">Belum ada draf yang dikaitkan dengan tugas ini.@if($bisaUnggah) Unggah melalui menu Repository, lalu pilih tugas ini pada kolom "Tugas terkait".@endif</p>
                        @else
                            <ul class="td-files">
                                @foreach($drafList as $b)
                                    @php $versi = $b->versions->first() ?? null; @endphp
                                    <li>
                                        <span class="td-file-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                        </span>
                                        <div class="td-file-info">
                                            <b>{{ $b->nama_file ?? $b->nama ?? $b->name ?? 'Berkas' }}</b>
                                            <small>@if($versi && !empty($versi->ukuran_byte)){{ format_bytes($versi->ukuran_byte) }} · @endif diunggah {{ tmNamaUser($versi?->uploader ?? $b->pembuat ?? $pembuat) }}</small>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="td-section">
                        <div class="td-section-head">
                            <h4>Materi &amp; Referensi ({{ $materiList->count() }})</h4>
                        </div>
                        @if($materiList->isEmpty())
                            <p class="muted">Belum ada materi &amp; referensi yang dikaitkan dengan tugas ini.</p>
                        @else
                            <ul class="td-files">
                                @foreach($materiList as $b)
                                    @php $versi = $b->versions->first() ?? null; @endphp
                                    <li>
                                        <span class="td-file-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                        </span>
                                        <div class="td-file-info">
                                            <b>{{ $b->nama_file ?? $b->nama ?? $b->name ?? 'Berkas' }}</b>
                                            <small>@if($versi && !empty($versi->ukuran_byte)){{ format_bytes($versi->ukuran_byte) }} · @endif diunggah {{ tmNamaUser($versi?->uploader ?? $b->pembuat ?? $pembuat) }}</small>
                                        </div>
                                        <span class="badge badge-umum">Materi Umum</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="td-section">
                        <h4>Riwayat Perubahan Status (Task Tracking)</h4>
                        @if($riwayat->isEmpty())
                            <p class="muted">Belum ada perubahan status yang tercatat.</p>
                        @else
                            <ul class="timeline">
                                @foreach($riwayat as $r)
                                    @php
                                        $dari = $r->status_lama ?? null;
                                        $ke   = $r->status_baru ?? null;
                                        $oleh = tmNamaUser($r->user ?? null);
                                    @endphp
                                    <li>
                                        <span class="tl-dot {{ tmKelasStatus($ke) }}"></span>
                                        <div class="tl-body">
                                            <p>
                                                @if($dari && $dari !== $ke){{ tmLabelStatus($dari) }} → @endif<b>{{ tmLabelStatus($ke) }}</b> oleh <b>{{ $oleh }}</b>
                                            </p>
                                            <small>{{ tmTanggal($r->waktu ?? $r->created_at) }}</small>
                                            @if(!empty($r->catatan))<span class="tl-note">“{{ $r->catatan }}”</span>@endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Aksi Project Leader --}}
                    @if (($peranSaya ?? null) === 'leader')
                    <div class="td-admin">
                        @if(Route::has($rEdit))
                            <button type="button" class="btn btn-outline btn-sm" data-tugas-edit data-url-edit="{{ route($rEdit, [$workspace, $tugas]) }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                Ubah Tugas
                            </button>
                        @endif
                        @if(Route::has($rDestroy))
                        <form method="POST" action="{{ route($rDestroy, [$workspace, $tugas]) }}" data-confirm="Hapus tugas '{{ tmJudulDari($tugas) }}' secara permanen? Riwayat status ikut terhapus.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger-outline btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="icon" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                Hapus Tugas
                            </button>
                        </form>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
/* LANGKAH 40-43: alur kanban — semua status SELALU tampil saat halaman dibuka (default sistem).
   Tombol tunggal di toolbar: "Sembunyikan Semua" <-> "Tampilkan Semua" (aksi user, hanya
   sesi ini, TIDAK tersimpan — begitu pindah halaman lalu kembali, semua kolom tampil lagi).
   Perpindahan status (tombol & drag-drop) via AJAX ke kanban.move dengan pemeriksaan
   hak akses; server tetap penjaga terakhir.
   LANGKAH 42: nilai "status" pada TOMBOL SUBMIT yang diklik kini ikut terkirim
   (perbaikan "The status field is required.").
   LANGKAH 43: perbaikan drag & drop — status kolom tujuan diambil dari zona drop
   yang benar (div.kc-body), bukan dari section.kanban-col yang tidak memiliki
   atribut data-drop-kolom (sebelumnya selalu null -> toast "ke null"). */
(function () {
    /* ---------- Tampilkan / sembunyikan kolom (desain tetap sama) ---------- */
    function setKolom(key, tampil) {
        var col = document.querySelector('.kanban-col[data-kolom="' + key + '"]');
        var chip = document.querySelector('.k-chip[data-kolom="' + key + '"]');
        if (!col) return;
        col.hidden = !tampil;
        if (chip) {
            chip.classList.toggle('off', !tampil);
            chip.setAttribute('aria-expanded', tampil ? 'true' : 'false');
        }
    }
    function sinkronPapan() {
        var tampils = document.querySelectorAll('.kanban-col[data-kolom]:not([hidden])').length;
        var empty = document.getElementById('kanbanEmpty');
        var board = document.getElementById('kanbanBoard');
        if (empty) empty.hidden = tampils > 0;
        if (board) board.style.display = tampils > 0 ? '' : 'none';
        var btn = document.getElementById('btnKolomSemua');
        if (btn) {
            var jadiTampil = tampils === 0;
            btn.classList.toggle('is-show', jadiTampil);
            btn.title = jadiTampil
                ? 'Tampilkan semua kolom status'
                : 'Sembunyikan semua kolom status — hanya berlaku di sesi ini, tidak tersimpan';
            btn.setAttribute('aria-label', jadiTampil ? 'Tampilkan semua kolom status' : 'Sembunyikan semua kolom status');
        }
    }
    function setSemua(tampil) {
        document.querySelectorAll('.kanban-col[data-kolom]').forEach(function (col) {
            setKolom(col.getAttribute('data-kolom'), tampil);
        });
        sinkronPapan();
    }
    document.querySelectorAll('.k-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var key = chip.getAttribute('data-kolom');
            var col = document.querySelector('.kanban-col[data-kolom="' + key + '"]');
            setKolom(key, col.hidden);
            sinkronPapan();
        });
    });
    document.querySelectorAll('[data-kolom-tutup]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setKolom(btn.getAttribute('data-kolom-tutup'), false);
            sinkronPapan();
        });
    });
    var btnSemua = document.getElementById('btnKolomSemua');
    if (btnSemua) {
        btnSemua.addEventListener('click', function () {
            var tampils = document.querySelectorAll('.kanban-col[data-kolom]:not([hidden])').length;
            setSemua(tampils === 0);
        });
    }
    var emptyBtn = document.querySelector('#kanbanEmpty [data-kolom-semua="tampil"]');
    if (emptyBtn) emptyBtn.addEventListener('click', function () { setSemua(true); });

    /* Default sistem: semua kolom tampil setiap kali kanban dibuka */
    setSemua(true);

    /* ---------- Perpindahan status via AJAX (toast + muat ulang) ---------- */
    var LABEL = { todo: 'To-Do', in_progress: 'In Progress', review: 'Review', done: 'Done' };

    function kirimStatus(url, fd) {
        if (!url) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': String(window.tmCsrf || ''),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: fd
        })
            .then(function (r) {
                return r.json().catch(function () { return { message: 'Terjadi kesalahan server.' }; })
                    .then(function (data) { return { ok: r.ok, data: data }; });
            })
            .then(function (res) {
                if (res.ok) {
                    window.tmToast(res.data.pesan || 'Status diperbarui.', 'sukses');
                    setTimeout(function () { window.location.reload(); }, 600);
                } else {
                    window.tmToast(res.data.message || 'Tidak dapat memindahkan tugas.', 'gagal');
                }
            })
            .catch(function () {
                window.tmToast('Koneksi bermasalah. Coba lagi.', 'gagal');
            });
    }

    /* Form status pada kartu & modal detail.
       PENTING (LANGKAH 42): nilai "status" tertera pada TOMBOL SUBMIT yang
       diklik, sedangkan new FormData(form) standar TIDAK menyertakan nilai
       tombol submit. Karena itu tombol yang diklik disisipkan manual ke
       dalam FormData sebelum request dikirim. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-status-form')) return;
        e.preventDefault();

        var fd = new FormData(form);

        /* 1) Browser modern: tombol yang diklik tersedia di e.submitter */
        var tombol = (e.submitter && e.submitter.name) ? e.submitter : null;

        /* 2) Fallback browser lama: tombol submit yang sedang aktif */
        if (!tombol) {
            var aktif = document.activeElement;
            if (aktif && aktif.form === form && aktif.type === 'submit' && aktif.name) tombol = aktif;
        }

        /* 3) Fallback terakhir: tombol submit pertama pada form (sama dengan
              perilaku browser saat form dikirim lewat tombol Enter) */
        if (!tombol) {
            tombol = form.querySelector('button[type="submit"][name]');
        }

        if (tombol && tombol.name) {
            if (fd.has(tombol.name)) fd.delete(tombol.name);
            fd.append(tombol.name, tombol.value);
        }

        /* Pengaman: jangan kirim request yang pasti ditolak server */
        if (!fd.get('status')) {
            window.tmToast('Status tujuan tidak terbaca. Silakan coba lagi.', 'gagal');
            return;
        }

        kirimStatus(form.getAttribute('action'), fd);
    }, true);

    /* ---------- Drag & drop kartu antar kolom (dengan pemeriksaan hak akses) ---------- */
    var idTugasDigeser = null;
    document.addEventListener('dragstart', function (e) {
        var kartu = e.target.closest ? e.target.closest('.task-card') : null;
        if (!kartu) return;
        idTugasDigeser = kartu.getAttribute('data-tugas');
        kartu.classList.add('dragging');
        if (e.dataTransfer) e.dataTransfer.setData('text/plain', idTugasDigeser);
    });
    document.addEventListener('dragend', function () {
        document.querySelectorAll('.task-card.dragging').forEach(function (k) { k.classList.remove('dragging'); });
        document.querySelectorAll('.kc-body.drag-over').forEach(function (z) { z.classList.remove('drag-over'); });
        idTugasDigeser = null;
    });
    document.querySelectorAll('[data-drop-kolom]').forEach(function (zona) {
        zona.addEventListener('dragover', function (e) {
            var kolom = zona.closest('.kanban-col');
            if (!kolom || kolom.hidden) return;
            e.preventDefault();
            zona.classList.add('drag-over');
        });
        zona.addEventListener('dragleave', function (e) {
            if (!e.relatedTarget || !zona.contains(e.relatedTarget)) zona.classList.remove('drag-over');
        });
        zona.addEventListener('drop', function (e) {
            e.preventDefault();
            zona.classList.remove('drag-over');
            var kolom = zona.closest('.kanban-col');
            if (!kolom || kolom.hidden) return;
            var id = (e.dataTransfer && e.dataTransfer.getData('text/plain')) || idTugasDigeser;
            if (!id) return;
            var kartu = document.querySelector('.task-card[data-tugas="' + id + '"]');
            if (!kartu) return;
            var asal = kartu.closest('.kanban-col');
            if (asal === kolom) return;
            /* LANGKAH 43: ambil status tujuan dari ZONA DROP (elemen yang
               benar-benar membawa atribut data-drop-kolom), bukan dari
               section.kanban-col yang tidak punya atribut itu. */
            var target = zona.getAttribute('data-drop-kolom');
            if (!target) return;
            var boleh = (kartu.getAttribute('data-pindah') || '').split(',').filter(function (s) { return s; });
            if (boleh.indexOf(target) === -1) {
                window.tmToast('Anda tidak berhak memindahkan tugas ke ' + (LABEL[target] || target || 'kolom tujuan') + '.', 'gagal');
                return;
            }
            var url = kartu.getAttribute('data-move-url');
            if (!url) return;
            var fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('status', target);
            kirimStatus(url, fd);
        });
    });

    /* ---------- Tombol "Ubah Tugas" (Leader): isi modal form dari data server ---------- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-tugas-edit]');
        if (!btn) return;
        e.preventDefault();
        fetch(btn.getAttribute('data-url-edit'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var form = document.getElementById('formTugas');
                if (!form || !data || !data.url_update) return;
                form.setAttribute('action', data.url_update);
                var m = form.querySelector('[name="_method"]');
                if (m) m.value = 'PATCH';
                form.querySelector('[name="judul"]').value = data.judul || '';
                form.querySelector('[name="deskripsi"]').value = data.deskripsi || '';
                form.querySelector('[name="id_penanggung_jawab"]').value = data.id_penanggung_jawab || '';
                form.querySelector('[name="deadline"]').value = data.deadline || '';
                var teks = document.getElementById('judulModalTugasTeks');
                if (teks) teks.textContent = 'Ubah Tugas';
                window.tmOpenModal('modalTugas');
            })
            .catch(function () {
                window.tmToast('Gagal memuat data tugas.', 'gagal');
            });
    });

    /* Tombol "+ Tambah Tugas": kembalikan modal ke mode tambah */
    var btnTambah = document.getElementById('btnTambahTugas');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            var form = document.getElementById('formTugas');
            if (!form) return;
            var urlStore = form.getAttribute('data-url-store');
            if (urlStore) form.setAttribute('action', urlStore);
            var m = form.querySelector('[name="_method"]');
            if (m) m.value = 'POST';
            form.reset();
            var teks = document.getElementById('judulModalTugasTeks');
            if (teks) teks.textContent = 'Tambah Tugas Baru';
        });
    }
})();
</script>

@endsection