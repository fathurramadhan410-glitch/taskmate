<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman undangan melalui link bertanda tangan + QR Code.
 *
 * Lapisan keamanan bergabung:
 *  1. Signed URL  -> tanda tangan HMAC, link tidak bisa dimanipulasi;
 *  2. Kode unik   -> 8 karakter acak tanpa huruf membingungkan;
 *  3. Wajib login -> pengunjung diarahkan ke login lalu kembali otomatis;
 *  4. Halaman konfirmasi -> pengguna melihat info workspace sebelum bergabung;
 *  5. Rate limit  -> maksimal 12 kali akses per menit per pengguna;
 *  6. Saklar & kedaluwarsa -> leader dapat menonaktifkan undangan atau
 *     membatasi masa berlaku link; kode lama dicabut saat di-regenerate.
 */
class JoinController extends Controller
{
    public function show(string $kode)
    {
        [$state, $workspace] = $this->evaluasi($kode);

        if ($state !== 'ok') {
            return view('gabung.show', ['state' => $state, 'workspace' => $workspace]);
        }

        if (! Auth::check()) {
            session(['url.intended' => url()->full()]);

            return redirect()->route('login')
                ->with('info', 'Masuk atau daftar terlebih dahulu untuk bergabung ke workspace.');
        }

        if ($workspace->hasMember(Auth::id())) {
            return redirect()->route('workspace.show', $workspace)
                ->with('info', 'Anda sudah menjadi anggota workspace ini.');
        }

        return view('gabung.show', ['state' => $state, 'workspace' => $workspace]);
    }

    public function confirm(Request $request, string $kode)
    {
        [$state, $workspace] = $this->evaluasi($kode);

        if ($state !== 'ok') {
            return view('gabung.show', ['state' => $state, 'workspace' => $workspace]);
        }

        if (! Auth::check()) {
            session(['url.intended' => url()->full()]);

            return redirect()->route('login')
                ->with('info', 'Masuk atau daftar terlebih dahulu untuk bergabung ke workspace.');
        }

        $user = $request->user();

        if ($workspace->hasMember($user->id_user)) {
            return redirect()->route('workspace.show', $workspace)
                ->with('info', 'Anda sudah menjadi anggota workspace ini.');
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
            ->with('sukses', 'Berhasil bergabung ke workspace "'.$workspace->nama_workspace.'". Selamat bekerja sama!');
    }

    /** @return array{0:string,1:?Workspace} */
    protected function evaluasi(string $kode): array
    {
        $workspace = Workspace::where('kode_undangan', strtoupper($kode))->first();

        if (! $workspace) {
            return ['invalid', null];
        }
        if (! $workspace->undangan_aktif) {
            return ['nonaktif', $workspace];
        }
        if ($workspace->undangan_berlaku_sampai && now()->gt($workspace->undangan_berlaku_sampai)) {
            return ['kadaluarsa', $workspace];
        }

        return ['ok', $workspace];
    }
}
