<?php

namespace App\Services;

use App\Mail\ReminderMail;
use App\Models\Notifikasi;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pusat pembuatan & pengiriman notifikasi TaskMate (TMT-006).
 * Setiap notifikasi disimpan ke database (ikon lonceng) lalu dikirim via email
 * dengan percobaan ulang hingga 3 kali sesuai SRS UC-06.
 */
class NotificationService
{
    public static function kirim(User|int|null $penerima, ?Task $task, string $jenis, string $pesan): ?Notifikasi
    {
        $user = $penerima instanceof User ? $penerima : ($penerima ? User::find($penerima) : null);

        if (! $user || ! in_array($jenis, Notifikasi::JENIS, true)) {
            return null;
        }

        $notif = Notifikasi::create([
            'id_user' => $user->id_user,
            'id_task' => $task?->id_task,
            'jenis' => $jenis,
            'pesan' => mb_substr($pesan, 0, 255),
            'status_baca' => false,
            'status_email' => 'menunggu',
            'waktu_kirim' => now(),
        ]);

        self::kirimEmail($user, $task, $pesan, $jenis, $notif);

        return $notif;
    }

    /** Kirim email pengingat dengan maksimal 3 kali percobaan (SRS UC-06 3a) */
    protected static function kirimEmail(User $user, ?Task $task, string $pesan, string $jenis, Notifikasi $notif): void
    {
        if (empty($user->email)) {
            $notif->update(['status_email' => 'gagal']);

            return;
        }

        $terkirim = false;

        for ($i = 1; $i <= 3; $i++) {
            try {
                Mail::to($user->email)->send(new ReminderMail($user, $task, $pesan, $jenis));
                $terkirim = true;
                break;
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim email notifikasi (percobaan '.$i.'/3): '.$e->getMessage());
            }
        }

        $notif->update(['status_email' => $terkirim ? 'terkirim' : 'gagal']);
    }

    /** Project Leader dari sebuah workspace */
    public static function leaderOf(Workspace|int $workspace): ?User
    {
        $idWorkspace = $workspace instanceof Workspace ? $workspace->id_workspace : $workspace;

        $member = Workspace::find($idWorkspace)?->leaderMember();

        return $member?->user;
    }
}
