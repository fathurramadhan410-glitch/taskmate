<?php

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * TMT-006 / FR-021 s.d. FR-023:
 * Memeriksa deadline tugas yang belum Done dan membuat pengingat
 * pada H-3, H-1, hari-H, serta notifikasi keterlambatan.
 * Dijadwalkan setiap hari pukul 07.00 WITA (routes/console.php).
 */
class CheckDeadlines extends Command
{
    protected $signature = 'taskmate:check-deadlines';

    protected $description = 'Memeriksa deadline tugas (H-3, H-1, hari-H, terlambat) dan mengirim pengingat';

    public function handle(): int
    {
        $today = Carbon::today();
        $jumlah = 0;

        $tasks = Task::with(['workspace', 'penanggungJawab'])
            ->where('status', '!=', 'done')
            ->whereNotNull('deadline')
            ->get();

        foreach ($tasks as $task) {
            $dl = Carbon::parse($task->deadline)->startOfDay();
            $abs = (int) floor(abs($today->diffInDays($dl)));
            $selisih = $dl->lt($today) ? -$abs : $abs;

            $jenis = match (true) {
                $selisih === 3 => 'h3',
                $selisih === 1 => 'h1',
                $selisih === 0 => 'hari_h',
                $selisih < 0 => 'terlambat',
                default => null,
            };

            if ($jenis === null) {
                continue;
            }

            $judul = $task->judul;
            $workspace = $task->workspace?->nama_workspace ?? '-';

            // ---- Pengingat untuk penanggung jawab (hindari pengiriman ganda) ----
            $pj = $task->penanggungJawab;
            if ($pj && ! $this->sudahTerkirim($pj->id_user, $task->id_task, $jenis)) {
                $pesan = match ($jenis) {
                    'h3' => "Pengingat H-3: tugas \"{$judul}\" pada workspace {$workspace} memiliki deadline {$task->deadline->format('d/m/Y H:i')}.",
                    'h1' => "Pengingat H-1: besok deadline tugas \"{$judul}\" pada workspace {$workspace}. Segera selesaikan.",
                    'hari_h' => "Hari ini deadline tugas \"{$judul}\" pada workspace {$workspace}. Jangan lupa dikumpulkan.",
                    default => "Tugas \"{$judul}\" pada workspace {$workspace} telah melewati deadline ({$task->deadline->format('d/m/Y H:i')}).",
                };

                NotificationService::kirim($pj, $task, $jenis, $pesan);
                $jumlah++;
            }

            // ---- Tugas terlambat juga dinotifikasikan ke Project Leader ----
            if ($jenis === 'terlambat') {
                $leader = NotificationService::leaderOf($task->workspace);
                if ($leader && (! $pj || $leader->id_user !== $pj->id_user) && ! $this->sudahTerkirim($leader->id_user, $task->id_task, 'terlambat')) {
                    NotificationService::kirim(
                        $leader,
                        $task,
                        'terlambat',
                        "Tugas \"{$judul}\" yang ditugaskan kepada ".($pj?->nama ?? '(tanpa PJ)')." telah melewati deadline ({$task->deadline->format('d/m/Y H:i')})."
                    );
                    $jumlah++;
                }
            }
        }

        $this->info("Pemeriksaan deadline selesai. {$jumlah} notifikasi dibuat pada ".now()->format('d/m/Y H:i').' WITA.');

        return self::SUCCESS;
    }

    /** Cegah pengingat yang sama dikirim dua kali (SRS UC-06 langkah 4) */
    protected function sudahTerkirim(int $idUser, int $idTask, string $jenis): bool
    {
        return Notifikasi::where('id_user', $idUser)
            ->where('id_task', $idTask)
            ->where('jenis', $jenis)
            ->exists();
    }
}
