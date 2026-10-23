<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * UC-03 / TMT-003: pembagian tugas (jobdesk) dan penetapan deadline.
 */
class TaskController extends Controller
{
    /** FR-009 s.d. FR-011: membuat tugas baru dengan status awal To-Do */
    public function store(Request $request, Workspace $workspace)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat menambah tugas.');

        $data = $this->validasi($request);

        if (! $workspace->members()->where('id_user', $data['id_penanggung_jawab'])->exists()) {
            return back()->withErrors(['id_penanggung_jawab' => 'Penanggung jawab harus merupakan anggota workspace ini.'])->withInput();
        }

        $task = Task::create([
            'id_workspace' => $workspace->id_workspace,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'status' => 'todo',
            'deadline' => $data['deadline'],
            'id_penanggung_jawab' => $data['id_penanggung_jawab'],
            'dibuat_oleh' => $request->user()->id_user,
        ]);

        NotificationService::kirim($data['id_penanggung_jawab'], $task, 'penugasan',
            'Anda ditugaskan sebagai penanggung jawab tugas "'.$task->judul.'" pada workspace '.$workspace->nama_workspace
            .'. Deadline: '.format_waktu($task->deadline).'. Bareng ya!');

        return back()->with('sukses', 'Tugas "'.$task->judul.'" berhasil dibuat dan masuk kolom To-Do.');
    }

    /** FR-010: mengubah tugas + notifikasi perubahan (FR-023) */
    public function update(Request $request, Workspace $workspace, Task $task)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat mengubah tugas.');

        if ($task->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Tugas tidak ditemukan pada workspace ini.');
        }

        $data = $this->validasi($request);

        if (! $workspace->members()->where('id_user', $data['id_penanggung_jawab'])->exists()) {
            return back()->withErrors(['id_penanggung_jawab' => 'Penanggung jawab harus merupakan anggota workspace ini.'])->withInput();
        }

        $pjLama = $task->id_penanggung_jawab;
        $deadlineLama = $task->deadline->format('d/m/Y H:i');

        $task->update([
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'deadline' => $data['deadline'],
            'id_penanggung_jawab' => $data['id_penanggung_jawab'],
        ]);

        $deadlineBaru = $task->deadline->format('d/m/Y H:i');

        if ($pjLama !== $data['id_penanggung_jawab']) {
            NotificationService::kirim($data['id_penanggung_jawab'], $task, 'penugasan',
                'Anda kini menjadi penanggung jawab tugas "'.$task->judul.'" pada workspace '.$workspace->nama_workspace
                .'. Deadline: '.$deadlineBaru.'.');
        } elseif ($deadlineLama !== $deadlineBaru) {
            NotificationService::kirim($task->id_penanggung_jawab, $task, 'perubahan',
                'Deadline tugas "'.$task->judul.'" diubah menjadi '.$deadlineBaru.' oleh Project Leader.');
        } else {
            NotificationService::kirim($task->id_penanggung_jawab, $task, 'perubahan',
                'Detail tugas "'.$task->judul.'" diperbarui oleh Project Leader. Mohon dicek kembali.');
        }

        return back()->with('sukses', 'Tugas "'.$task->judul.'" berhasil diperbarui.');
    }

    /** Fragment HTML untuk modal detail tugas (dipanggil via AJAX) */
    public function detail(Request $request, Workspace $workspace, Task $task)
    {
        if ($task->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Tugas tidak ditemukan pada workspace ini.');
        }

        $role = user_role_in($workspace->id_workspace);
        $task->load(['penanggungJawab:id_user,nama,avatar', 'pembuat:id_user,nama']);
        $logs = $task->logs()->with('user:id_user,nama')->orderByDesc('id_log')->limit(20)->get();
                $userId = $request->user()->id_user;

        // Draf / berkas pekerjaan yang dikaitkan dengan tugas ini
        $files = $task->files()->draf()
            ->with(['versions.uploader:id_user,nama', 'pembuat:id_user,nama'])
            ->orderByDesc('id_file')->get();

        // Materi & referensi dari Project Leader: yang dikaitkan ke tugas ini
        // (ditampilkan lebih dulu) ditambah materi umum workspace.
        $materi = $workspace->files()->materi()
            ->where(fn ($q) => $q->where('id_task', $task->id_task)->orWhereNull('id_task'))
            ->with(['versions.uploader:id_user,nama', 'pembuat:id_user,nama'])
            ->orderByRaw('id_task IS NULL')
            ->orderByDesc('id_file')->get();

        $moves = kanban_moves($task, $role, $userId);

        // Leader & penanggung jawab tugas boleh mengunggah draf untuk tugas ini
        $bisaUnggah = $role === 'leader' || (int) $task->id_penanggung_jawab === (int) $userId;

        return view('kanban._detail', compact('task', 'logs', 'files', 'materi', 'role', 'moves', 'workspace', 'bisaUnggah'));
        
    }

    /** Data JSON untuk mengisi form edit tugas (dipakai kanban.js) */
    public function editData(Request $request, Workspace $workspace, Task $task)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat mengubah tugas.');

        if ($task->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Tugas tidak ditemukan pada workspace ini.');
        }

        return response()->json([
            'url_update' => route('tugas.update', [$workspace, $task]),
            'judul' => $task->judul,
            'deskripsi' => $task->deskripsi,
            'id_penanggung_jawab' => $task->id_penanggung_jawab,
            'deadline' => $task->deadline->format('Y-m-d\TH:i'),
        ]);
    }

    /** FR-010: menghapus tugas (dengan konfirmasi di sisi antarmuka) */
    public function destroy(Request $request, Workspace $workspace, Task $task)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat menghapus tugas.');

        if ($task->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Tugas tidak ditemukan pada workspace ini.');
        }

        $judul = $task->judul;
        $task->delete();

        return back()->with('sukses', 'Tugas "'.$judul.'" telah dihapus.');
    }

    /** FR-011: deadline tidak boleh lebih awal dari hari ini */
    protected function validasi(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'id_penanggung_jawab' => ['required', 'integer'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
        ], [
            'judul.required' => 'Judul tugas wajib diisi.',
            'judul.max' => 'Judul tugas maksimal 150 karakter.',
            'id_penanggung_jawab.required' => 'Penanggung jawab wajib dipilih.',
            'deadline.required' => 'Deadline wajib diisi.',
            'deadline.after_or_equal' => 'Deadline tidak boleh lebih awal dari hari ini.',
        ]);
    }
}
