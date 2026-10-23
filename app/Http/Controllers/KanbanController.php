<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskStatusLog;
use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * UC-04 / TMT-004: Kanban board dan task tracking.
 *
 * Sesuai permintaan pengguna: keempat kolom status (To-Do, In Progress,
 * Review, Done) disembunyikan secara default dan baru muncul ketika
 * statusnya diklik (lihat kanban/show.blade.php + js/kanban.js).
 */
class KanbanController extends Controller
{
    public function show(Request $request, Workspace $workspace)
    {
        $role = user_role_in($workspace->id_workspace);

        $anggota = $workspace->members()
            ->with('user:id_user,nama,avatar')
            ->get()
            ->sortBy(fn ($m) => $m->user->nama ?? '')
            ->values();

        // Jumlah tugas per status (tidak terpengaruh filter, untuk chip status)
        $counts = $workspace->tasks()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $query = $workspace->tasks()
            ->with('penanggungJawab:id_user,nama,avatar')
            ->orderBy('deadline');

        // FR-015: filter berdasarkan penanggung jawab + pencarian judul
        if ($request->filled('pj')) {
            $query->where('id_penanggung_jawab', (int) $request->input('pj'));
        }
        if ($request->filled('q')) {
            $query->where('judul', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $request->input('q')).'%');
        }

        $tasks = $query->get();

        return view('kanban.show', compact('workspace', 'role', 'anggota', 'counts', 'tasks'));
    }

    /**
     * FR-013 + FR-014: mengubah status tugas dengan validasi hak akses &
     * urutan, lalu mencatat riwayat perubahan (task tracking).
     */
    public function move(Request $request, Workspace $workspace, Task $task)
    {
        if ($task->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Tugas tidak ditemukan pada workspace ini.');
        }

        $data = $request->validate([
            'status' => ['required', 'in:todo,in_progress,review,done'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $role = user_role_in($workspace->id_workspace);
        $old = $task->status;
        $new = $data['status'];

        if ($old === $new) {
            return response()->json(['message' => 'Tugas sudah berada pada status tersebut.'], 422);
        }

        $boleh = false;
        $tolak = 'Anda tidak berhak mengubah status tugas ini.';

        if ($role === 'leader') {
            $boleh = true;
        } elseif ($task->id_penanggung_jawab === $user->id_user) {
            $maju = ['todo' => 'in_progress', 'in_progress' => 'review'];
            $boleh = ($maju[$old] ?? null) === $new;
            $tolak = $boleh ? $tolak : 'Member hanya dapat memindahkan tugas miliknya maju satu tahap (To-Do → In Progress → Review). Hanya Project Leader yang dapat menetapkan Done.';
        } else {
            $tolak = 'Anda hanya dapat memindahkan tugas milik Anda sendiri.';
        }

        if (! $boleh) {
            return response()->json(['message' => $tolak], 403);
        }

        DB::transaction(function () use ($task, $new, $user, $data, $old) {
            $task->update(['status' => $new]);

            TaskStatusLog::create([
                'id_task' => $task->id_task,
                'id_user' => $user->id_user,
                'status_lama' => $old,
                'status_baru' => $new,
                'catatan' => $data['catatan'] ?? null,
                'waktu' => now(),
            ]);
        });

        // Notifikasi terkait perpindahan status (TMT-006)
        if ($new === 'review') {
            $leader = NotificationService::leaderOf($workspace);
            if ($leader && $leader->id_user !== $user->id_user) {
                NotificationService::kirim($leader, $task, 'review',
                    'Tugas "'.$task->judul.'" masuk kolom Review menunggu persetujuan Anda.');
            }
        }

        if ($role === 'leader' && $old === 'review' && $new === 'in_progress') {
            NotificationService::kirim($task->id_penanggung_jawab, $task, 'perubahan',
                'Tugas "'.$task->judul.'" dikembalikan ke In Progress oleh Project Leader untuk diperbaiki.');
        }

        if ($new === 'done' && $task->id_penanggung_jawab && $task->id_penanggung_jawab !== $user->id_user) {
            NotificationService::kirim($task->id_penanggung_jawab, $task, 'perubahan',
                'Tugas "'.$task->judul.'" telah disetujui oleh Project Leader dan berstatus Done. Kerja bagus!');
        }

        return response()->json([
            'sukses' => true,
            'status' => $new,
            'pesan' => 'Status tugas diperbarui menjadi '.status_label($new).'.',
        ]);
    }
}
