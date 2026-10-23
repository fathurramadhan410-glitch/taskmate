<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

/**
 * UC-06 / TMT-006: daftar notifikasi dalam aplikasi.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifikasis = $request->user()->notifikasis()
            ->with('task:id_task,judul,id_workspace')
            ->orderByDesc('id_notif')
            ->paginate(15);

        return view('notifikasi.index', compact('notifikasis'));
    }

    /** API kecil untuk memperbarui penanda lonceng secara berkala */
    public function badge(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadCount(),
        ]);
    }

    /** Tandai satu notifikasi sudah dibaca lalu buka tugas terkait */
    public function baca(Request $request, Notifikasi $notifikasi)
    {
        abort_unless($notifikasi->id_user === $request->user()->id_user, 403, 'Notifikasi ini bukan milik Anda.');

        if (! $notifikasi->status_baca) {
            $notifikasi->update(['status_baca' => true]);
        }

        $task = $notifikasi->task;

        if ($task) {
            return redirect()->route('kanban.show', $task->id_workspace)
                ->with('info', 'Notifikasi ditandai sudah dibaca. Kartu tugas "'.$task->judul.'" dapat dilihat pada Kanban.');
        }

        return redirect()->route('notifikasi.index');
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->notifikasis()
            ->where('status_baca', false)
            ->update(['status_baca' => true]);

        return back()->with('sukses', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
