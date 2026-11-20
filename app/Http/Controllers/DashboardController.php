<?php

namespace App\Http\Controllers;

use App\Models\SiakadApp;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Landing page / personal branding aplikasi */
    public function landing()
    {
        return view('landing');
    }

    /** Halaman SIAKAD & E-Learning: daftar aplikasi kampus aktif dari database */
    public function siakad()
    {
        $apps = SiakadApp::where('aktif', true)->orderBy('urutan')->orderBy('nama')->get();

        return view('siakad', compact('apps'));
    }

    /** FR-008: daftar workspace yang diikuti pengguna beserta perannya */
    public function index(Request $request)
    {
        $user = $request->user();

        $workspaces = Workspace::whereIn('id_workspace', $user->memberships()->select('id_workspace'))
            ->withCount([
                'tasks',
                'members',
                'files',
                'tasks as tugas_selesai' => fn ($q) => $q->where('status', 'done'),
            ])
            ->orderByDesc('created_at')
            ->get();

        $roles = WorkspaceMember::where('id_user', $user->id_user)->pluck('peran', 'id_workspace');

        // Tugas yang menjadi tanggung jawab pengguna saat ini (belum selesai)
        $tugasAktif = $user->tasksDitanggung()
            ->whereIn('id_workspace', $workspaces->pluck('id_workspace'))
            ->where('status', '!=', 'done')
            ->orderBy('deadline')
            ->limit(5)
            ->get();

        return view('dashboard', compact('workspaces', 'roles', 'tugasAktif'));
    }

    /** FR-006: bergabung sebagai Member dengan kode undangan */
    public function joinByCode(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:12'],
        ], [
            'kode.required' => 'Masukkan kode undangan terlebih dahulu.',
            'kode.max' => 'Kode undangan maksimal 12 karakter.',
        ]);

        $kode = strtoupper(trim($data['kode']));
        $user = $request->user();

        $workspace = Workspace::where('kode_undangan', $kode)->first();

        if (! $workspace) {
            return back()->with('gagal', 'Kode undangan tidak ditemukan. Periksa kembali kode dari Project Leader.');
        }

        if (! $workspace->undanganValid()) {
            return back()->with('gagal', 'Undangan workspace tersebut sedang dinonaktifkan atau telah kedaluwarsa. Hubungi Project Leader.');
        }

        if ($workspace->hasMember($user->id_user)) {
            return back()->with('info', 'Anda sudah menjadi anggota workspace '.$workspace->nama_workspace.'.');
        }

        WorkspaceMember::create([
            'id_workspace' => $workspace->id_workspace,
            'id_user' => $user->id_user,
            'peran' => 'member',
            'tanggal_bergabung' => now(),
        ]);

        NotificationService::kirim(
            NotificationService::leaderOf($workspace),
            null,
            'bergabung',
            $user->nama.' bergabung sebagai Member pada workspace "'.$workspace->nama_workspace.'".'
        );

        return redirect()->route('workspace.show', $workspace)
            ->with('sukses', 'Berhasil bergabung ke workspace "'.$workspace->nama_workspace.'".');
    }
}