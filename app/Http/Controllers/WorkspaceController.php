<?php

namespace App\Http\Controllers;

use App\Models\TaskStatusLog;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * UC-02 / TMT-002: kelola workspace, anggota, dan undangan (kode + link + QR).
 */
class WorkspaceController extends Controller
{
    /** FR-004: membuat workspace, pembuat otomatis menjadi Project Leader */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_workspace' => ['required', 'string', 'max:100'],
            'mata_kuliah' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_workspace.required' => 'Nama workspace wajib diisi.',
            'nama_workspace.max' => 'Nama workspace maksimal 100 karakter.',
        ]);

        $workspace = DB::transaction(function () use ($request, $data) {
            $ws = Workspace::create([
                'nama_workspace' => $data['nama_workspace'],
                'mata_kuliah' => $data['mata_kuliah'] ?? null,
                'deskripsi' => $data['deskripsi'] ?? null,
                'kode_undangan' => Workspace::buatKode(),
                'undangan_aktif' => true,
                'id_pembuat' => $request->user()->id_user,
            ]);

            WorkspaceMember::create([
                'id_workspace' => $ws->id_workspace,
                'id_user' => $request->user()->id_user,
                'peran' => 'leader',
                'tanggal_bergabung' => now(),
            ]);

            return $ws;
        });

        return redirect()->route('workspace.show', $workspace)
            ->with('sukses', 'Workspace "'.$workspace->nama_workspace.'" berhasil dibuat. Bagikan kode undangan kepada anggota kelompok Anda!');
    }

    public function show(Request $request, Workspace $workspace)
    {
        $user = $request->user();
        $role = user_role_in($workspace->id_workspace);
        $isLeader = $role === 'leader';

        $members = $workspace->members()
            ->with('user:id_user,nama,email,avatar')
            ->orderBy('tanggal_bergabung')
            ->get();

        $counts = $workspace->tasks()
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => array_sum($counts->all()),
            'todo' => $counts['todo'] ?? 0,
            'in_progress' => $counts['in_progress'] ?? 0,
            'review' => $counts['review'] ?? 0,
            'done' => $counts['done'] ?? 0,
        ];

        $aktivitas = TaskStatusLog::whereIn('id_task', $workspace->tasks()->select('id_task'))
            ->with(['user:id_user,nama', 'task:id_task,judul'])
            ->orderByDesc('id_log')
            ->limit(6)
            ->get();

        $link = null;
        $qrSvg = null;
        if ($isLeader) {
            $link = $workspace->inviteLink();

            $qrSvg = (new QRCode(new QROptions([
                'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                'eccLevel' => QRCode::ECC_M,
                'scale' => 6,
                'imageBase64' => false,
            ])))->render($link);
        }

        return view('workspace.show', compact('workspace', 'role', 'isLeader', 'members', 'stats', 'aktivitas', 'link', 'qrSvg'));
    }

    /** FR-007: mengubah data workspace */
    public function update(Request $request, Workspace $workspace)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat mengubah workspace.');

        $data = $request->validate([
            'nama_workspace' => ['required', 'string', 'max:100'],
            'mata_kuliah' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_workspace.required' => 'Nama workspace wajib diisi.',
        ]);

        $workspace->update($data);

        return back()->with('sukses', 'Data workspace berhasil diperbarui.');
    }

    /** FR-007: menghapus workspace beserta tugas dan berkas di dalamnya */
    public function destroy(Request $request, Workspace $workspace)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat menghapus workspace.');

        DB::transaction(function () use ($workspace) {
            Storage::disk('local')->deleteDirectory('files/workspace_'.$workspace->id_workspace);
            $workspace->tasks()->delete();
            $workspace->chats()->delete();
            $workspace->files()->delete();
            $workspace->members()->delete();
            $workspace->delete();
        });

        return redirect()->route('dashboard')
            ->with('sukses', 'Workspace "'.$workspace->nama_workspace.'" beserta seluruh tugas dan berkasnya telah dihapus.');
    }

    /**
     * Pengaturan undangan: buat kode baru, aktif/nonaktif, dan masa berlaku.
     * Inilah lapisan keamanan agar link/kode lama tidak lagi bisa dipakai.
     */
    public function aturUndangan(Request $request, Workspace $workspace)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat mengatur undangan.');

        $aksi = $request->validate([
            'aksi' => ['required', 'in:regenerate,aktifkan,nonaktifkan,berlaku'],
            'hari' => ['nullable', 'integer', 'in:0,1,3,7,30'],
        ], [
            'aksi.required' => 'Aksi undangan tidak dikenali.',
        ]);

        match ($aksi['aksi']) {
            'regenerate' => $workspace->update(['kode_undangan' => Workspace::buatKode()]),
            'aktifkan' => $workspace->update(['undangan_aktif' => true]),
            'nonaktifkan' => $workspace->update(['undangan_aktif' => false]),
            'berlaku' => $workspace->update([
                'undangan_berlaku_sampai' => ($aksi['hari'] ?? 0) > 0
                    ? now()->addDays((int) $aksi['hari'])
                    : null,
            ]),
            default => null,
        };

        $pesan = match ($aksi['aksi']) {
            'regenerate' => 'Kode undangan baru berhasil dibuat. Kode dan link lama otomatis tidak berlaku lagi.',
            'aktifkan' => 'Undangan workspace diaktifkan. Orang lain kini dapat bergabung.',
            'nonaktifkan' => 'Undangan workspace dinonaktifkan. Tidak ada yang dapat bergabung sampai Anda mengaktifkannya kembali.',
            'berlaku' => ($aksi['hari'] ?? 0) > 0
                ? 'Masa berlaku undangan diatur '.(int) $aksi['hari'].' hari ke depan.'
                : 'Batas masa berlaku undangan dihapus (tanpa batas waktu).',
            default => 'Pengaturan undangan diperbarui.',
        };

        return back()->with('sukses', $pesan);
    }

    /** FR-007: mengeluarkan anggota dari workspace */
    public function keluarkanAnggota(Request $request, Workspace $workspace, WorkspaceMember $member)
    {
        abort_unless(is_leader_in($workspace->id_workspace), 403, 'Hanya Project Leader yang dapat mengeluarkan anggota.');

        if ($member->id_workspace !== $workspace->id_workspace) {
            abort(404, 'Anggota tidak ditemukan pada workspace ini.');
        }

        if ($member->peran === 'leader') {
            return back()->with('gagal', 'Project Leader tidak dapat dikeluarkan. Hapus workspace jika ingin membubarkan kelompok.');
        }

        $nama = $member->user?->nama ?? 'Anggota';
        $member->delete();

        return back()->with('sukses', $nama.' telah dikeluarkan dari workspace.');
    }
}
