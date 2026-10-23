<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Profil akun pengguna: lihat/ubah data, unggah foto profil,
 * ubah kata sandi, keluar, dan hapus akun (permintaan pengguna).
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $jumlahWorkspace = WorkspaceMember::where('id_user', $user->id_user)->count();
        $jumlahTugas = Task::where('id_penanggung_jawab', $user->id_user)->count();
        $jumlahDipimpin = Workspace::where('id_pembuat', $user->id_user)->count();

        return view('profil.show', compact('user', 'jumlahWorkspace', 'jumlahTugas', 'jumlahDipimpin'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,'.$user->id_user.',id_user'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.min' => 'Nama minimal 3 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
        ]);

        $user->update($data);

        return back()->with('sukses', 'Data profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $aturan = [
            'kata_sandi_baru' => ['required', 'string', 'min:8', 'confirmed'],
        ];
        $pesan = [
            'kata_sandi_baru.required' => 'Kata sandi baru wajib diisi.',
            'kata_sandi_baru.min' => 'Kata sandi minimal 8 karakter.',
            'kata_sandi_baru.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];

        if ($user->password) {
            $aturan['kata_sandi_saat_ini'] = [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (! Hash::check((string) $value, $user->password)) {
                        $fail('Kata sandi saat ini tidak sesuai.');
                    }
                },
            ];
            $pesan['kata_sandi_saat_ini.required'] = 'Kata sandi saat ini wajib diisi.';
        }

        $request->validate($aturan, $pesan);

        $user->update(['password' => $request->input('kata_sandi_baru')]);

        return back()->with('sukses', 'Kata sandi berhasil diperbarui.');
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto.required' => 'Pilih foto profil terlebih dahulu.',
            'foto.image' => 'Berkas harus berupa gambar.',
            'foto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();

        // Hapus foto lokal lama (bukan avatar Google)
        if ($user->avatar && ! Str::startsWith($user->avatar, ['http://', 'https://'])) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('foto')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('sukses', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Hapus akun secara permanen:
     * - Workspace yang dipimpin: kepemimpinan ditransfer ke anggota paling
     *   lama bergabung; jika tidak ada anggota lain, workspace dihapus.
     * - Tugas yang ditanggung dilepas (menjadi tanpa penanggung jawab).
     * - Riwayat chat tetap ada dengan label "Akun terhapus".
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->password) {
            $request->validate([
                'kata_sandi' => [
                    'required',
                    function ($attribute, $value, $fail) use ($user) {
                        if (! Hash::check((string) $value, $user->password)) {
                            $fail('Kata sandi tidak sesuai. Akun tidak dihapus.');
                        }
                    },
                ],
            ], [
                'kata_sandi.required' => 'Masukkan kata sandi untuk mengonfirmasi penghapusan akun.',
            ]);
        } else {
            $request->validate([
                'konfirmasi_teks' => ['required', 'string', 'in:HAPUS'],
            ], [
                'konfirmasi_teks.required' => 'Ketik HAPUS untuk mengonfirmasi.',
                'konfirmasi_teks.in' => 'Ketik kata HAPUS untuk mengonfirmasi penghapusan akun.',
            ]);
        }

        $nama = $user->nama;
        $idUser = $user->id_user;

        DB::transaction(function () use ($user, $idUser) {
            $dipimpin = Workspace::where('id_pembuat', $idUser)->with('members')->get();

            foreach ($dipimpin as $ws) {
                $penerus = $ws->members()
                    ->where('id_user', '!=', $idUser)
                    ->orderBy('tanggal_bergabung')
                    ->orderBy('id_member')
                    ->first();

                if ($penerus) {
                    $penerus->update(['peran' => 'leader']);
                    $ws->update(['id_pembuat' => $penerus->id_user]);

                    NotificationService::kirim($penerus->id_user, null, 'perubahan',
                        'Anda kini menjadi Project Leader pada workspace "'.$ws->nama_workspace.'" karena pemimpin sebelumnya menghapus akunnya.');
                } else {
                    Storage::disk('local')->deleteDirectory('files/workspace_'.$ws->id_workspace);
                    $ws->tasks()->delete();
                    $ws->chats()->delete();
                    $ws->files()->delete();
                    $ws->members()->delete();
                    $ws->delete();
                }
            }

            if ($user->avatar && ! Str::startsWith($user->avatar, ['http://', 'https://'])) {
                Storage::disk('public')->delete($user->avatar);
            }

            Task::where('id_penanggung_jawab', $idUser)->update(['id_penanggung_jawab' => null]);
            WorkspaceMember::where('id_user', $idUser)->delete();
        });

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')
            ->with('sukses', 'Akun '.$nama.' telah dihapus permanen. Terima kasih telah menggunakan TaskMate!');
    }
}
