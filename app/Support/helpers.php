<?php

/*
|--------------------------------------------------------------------------
| Helper Global TaskMate
|--------------------------------------------------------------------------
| Fungsi bantu yang dipakai di Controller dan Blade.
|
| LANGKAH 39 — Perbaikan hak akses tombol status (FR-013):
| kanban_moves() kini memakai pembanding (int) dan aturan member
| diperjelas: hanya maju satu tahap, tidak boleh menetapkan Done.
*/

use App\Models\Task;
use App\Models\WorkspaceMember;
use Illuminate\Support\Carbon;

if (! function_exists('user_role_in')) {
    /** Peran pengguna yang sedang login pada workspace: 'leader' | 'member' | null */
    function user_role_in(int $workspaceId): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        return WorkspaceMember::where('id_workspace', $workspaceId)
            ->where('id_user', $user->id_user)
            ->value('peran');
    }
}

if (! function_exists('is_leader_in')) {
    function is_leader_in(int $workspaceId): bool
    {
        return user_role_in($workspaceId) === 'leader';
    }
}

if (! function_exists('initial')) {
    /** Inisial nama untuk avatar fallback, contoh: "Fathur Ramadhan" -> "FR" */
    function initial(string $nama): string
    {
        $parts = preg_split('/\s+/', trim($nama));
        $first = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1));
        $second = isset($parts[1]) ? mb_strtoupper(mb_substr($parts[1], 0, 1)) : '';

        return $first.$second;
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(?int $bytes): string
    {
        $bytes = (int) ($bytes ?? 0);
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}

if (! function_exists('format_waktu')) {
    /** Format tanggal waktu DD/MM/YYYY HH:mm sesuai SRS 3.4 */
    function format_waktu($datetime): string
    {
        if (! $datetime) {
            return '-';
        }

        return Carbon::parse($datetime)->format('d/m/Y H:i');
    }
}

if (! function_exists('format_tanggal')) {
    function format_tanggal($datetime): string
    {
        if (! $datetime) {
            return '-';
        }

        return Carbon::parse($datetime)->format('d/m/Y');
    }
}

if (! function_exists('status_label')) {
    function status_label(string $status): string
    {
        return match ($status) {
            'todo' => 'To-Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
            default => ucfirst($status),
        };
    }
}

if (! function_exists('status_class')) {
    function status_class(string $status): string
    {
        return 'st-'.str_replace('_', '-', $status);
    }
}

if (! function_exists('jenis_label')) {
    function jenis_label(string $jenis): string
    {
        return match ($jenis) {
            'penugasan' => 'Penugasan',
            'perubahan' => 'Perubahan Tugas',
            'h3' => 'Pengingat H-3',
            'h1' => 'Pengingat H-1',
            'hari_h' => 'Deadline Hari Ini',
            'terlambat' => 'Tugas Terlambat',
            'review' => 'Masuk Review',
            'bergabung' => 'Anggota Baru',
            default => 'Notifikasi',
        };
    }
}

if (! function_exists('jenis_icon')) {
    function jenis_icon(string $jenis): string
    {
        return match ($jenis) {
            'penugasan' => 'clipboard',
            'perubahan' => 'edit',
            'h3', 'h1', 'hari_h' => 'clock',
            'terlambat' => 'alert',
            'review' => 'eye',
            'bergabung' => 'users',
            default => 'bell',
        };
    }
}

if (! function_exists('nav_active')) {
    /** Kelas CSS active untuk item navigasi */
    function nav_active(string|array $routes): string
    {
        return request()->routeIs($routes) ? 'active' : '';
    }
}

if (! function_exists('kanban_moves')) {
    /**
     * Tombol perpindahan status yang BOLEH dilakukan pengguna pada kartu tugas.
     *
     * Aturan sesuai SRS FR-013 / FR-014:
     * - Project Leader  : bebas memindahkan SEMUA tugas — maju, mundur,
     *                     termasuk menetapkan Done.
     * - Member          : hanya pada tugas MILIKNYA SENDIRI, hanya MAJU SATU
     *                     TAHAP (To-Do -> In Progress -> Review) dan TIDAK
     *                     BOLEH menetapkan Done (Done = persetujuan Leader).
     * - Pengguna lain   : tidak mendapat tombol apa pun.
     *
     * Nilai 'forward' / 'back' berisi slug status tujuan, atau null bila
     * tidak boleh — sehingga Blade cukup memakai @if untuk menyembunyikan
     * tombol yang tidak berhak tampil.
     */
    function kanban_moves(Task $task, ?string $role, int $userId): array
    {
        $nextMap = ['todo' => 'in_progress', 'in_progress' => 'review', 'review' => 'done'];
        $prevMap = ['in_progress' => 'todo', 'review' => 'in_progress', 'done' => 'review'];

        $next = $nextMap[$task->status] ?? null;
        $prev = $prevMap[$task->status] ?? null;

        // 1) Project Leader: semua tugas, maju & mundur, termasuk Done.
        if ($role === 'leader') {
            return ['forward' => $next, 'back' => $prev];
        }

        // 2) Member: hanya tugas milik sendiri, maju satu tahap, tanpa Done.
        //    Pembanding (int) agar aman meski salah satu nilai keluar sebagai string.
        if ((int) $task->id_penanggung_jawab === (int) $userId) {
            $maju = ($next !== null && $next !== 'done') ? $next : null;

            return ['forward' => $maju, 'back' => null];
        }

        // 3) Bukan penanggung jawab: tanpa tombol apa pun.
        return ['forward' => null, 'back' => null];
    }
}

if (! function_exists('deadline_chip')) {
    /** Chip deadline: kelas warna (kuning/merah) + label sesuai SRS 3.1 */
    function deadline_chip(Task $task): array
    {
        $labelTanggal = $task->deadline->format('d/m/Y H:i');

        if ($task->status === 'done') {
            return ['class' => 'dl-done', 'label' => $labelTanggal];
        }

        $selisih = $task->selisihHari();

        if ($selisih < 0) {
            $hari = abs($selisih);

            return ['class' => 'dl-merah', 'label' => 'Terlambat '.$hari.' hari · '.$labelTanggal];
        }
        if ($selisih === 0) {
            return ['class' => 'dl-merah', 'label' => 'Hari ini · '.$labelTanggal];
        }
        if ($selisih <= 3) {
            return ['class' => 'dl-kuning', 'label' => 'H-'.$selisih.' · '.$labelTanggal];
        }

        return ['class' => 'dl-netral', 'label' => $labelTanggal];
    }
}

if (! function_exists('file_icon')) {
    /** Nama ikon untuk ekstensi berkas */
    function file_icon(string $ext): string
    {
        return match ($ext) {
            'pdf' => 'file',
            'png', 'jpg', 'jpeg' => 'image',
            'zip' => 'box',
            default => 'doc',
        };
    }
}

if (! function_exists('preview_type')) {
    /** Jenis pratinjau bawaan untuk sebuah ekstensi: 'pdf' | 'image' | null */
    function preview_type(string $ext): ?string
    {
        return match (strtolower($ext)) {
            'pdf' => 'pdf',
            'png', 'jpg', 'jpeg' => 'image',
            default => null,
        };
    }
}