<?php

namespace App\Http\Controllers;

use App\Models\SiakadApp;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Panel administrator TaskMate:
 * - Manajemen Akun  : daftar akun beserta peran sistem, status akun (aktif / terkunci /
 *                     diblokir), dan keanggotaan workspace (Project Leader / Member).
 *                     Tindakan: jadikan admin/anggota, blokir, buka blokir/kunci.
 * - Aplikasi SIAKAD : tambah, aktifkan/nonaktifkan, dan hapus aplikasi yang tampil
 *                     di halaman SIAKAD & E-Learning.
 */
class AdminController extends Controller
{
    /* ========================= MANAJEMEN AKUN ========================= */

    public function akun(Request $request)
    {
        $filter = $request->query('status', 'semua'); // semua | aktif | blokir
        $cari = trim((string) $request->query('cari', ''));

        $query = User::query()
            ->with('memberships.workspace')
            ->withCount('memberships as jumlah_workspace')
            ->orderBy('nama');

        if ($filter === 'aktif') {
            $query->where('status_akun', 'aktif')
                ->where(function ($q) {
                    $q->whereNull('terkunci_sampai')->orWhere('terkunci_sampai', '<', now());
                });
        } elseif ($filter === 'blokir') {
            $query->where(function ($q) {
                $q->where('status_akun', 'diblokir')->orWhere('terkunci_sampai', '>=', now());
            });
        }

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")->orWhere('email', 'like', "%{$cari}%");
            });
        }

        $users = $query->get();

        // Jumlah untuk tab filter (dihitung tanpa filter pencarian)
        $hitung = [
            'semua' => User::count(),
            'aktif' => User::where('status_akun', 'aktif')
                ->where(function ($q) {
                    $q->whereNull('terkunci_sampai')->orWhere('terkunci_sampai', '<', now());
                })->count(),
            'blokir' => User::where(function ($q) {
                $q->where('status_akun', 'diblokir')->orWhere('terkunci_sampai', '>=', now());
            })->count(),
        ];

        return view('admin.akun', compact('users', 'filter', 'cari', 'hitung'));
    }

    /** Ubah peran sistem: admin <-> anggota */
    public function ubahPeran(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:admin,anggota'],
        ]);

        if ($user->id_user === $request->user()->id_user) {
            return back()->with('gagal', 'Anda tidak bisa mengubah peran akun Anda sendiri.');
        }

        $user->update(['role' => $data['role']]);

        $label = $data['role'] === 'admin' ? 'Administrator' : 'Anggota';

        return back()->with('sukses', 'Peran '.$user->nama.' diubah menjadi '.$label.'.');
    }

    /** Blokir akun: pemiliknya tidak bisa login sampai administrator membukanya */
    public function blokir(Request $request, User $user)
    {
        if ($user->id_user === $request->user()->id_user) {
            return back()->with('gagal', 'Anda tidak bisa memblokir akun Anda sendiri.');
        }

        if ($user->isAdmin()) {
            return back()->with('gagal', 'Akun administrator tidak bisa diblokir.');
        }

        $user->forceFill([
            'status_akun' => 'diblokir',
            'blokir_alasan' => trim((string) $request->input('alasan')) ?: 'Diblokir oleh administrator.',
            'terkunci_sampai' => null,
            'gagal_login' => 0,
        ])->save();

        return back()->with('sukses', 'Akun '.$user->nama.' diblokir dan tidak bisa masuk sampai Anda membukanya.');
    }

    /** Buka blokir manual / buka kunci otomatis (FR-003) sekaligus reset percobaan gagal */
    public function bukaBlokir(User $user)
    {
        $user->forceFill([
            'status_akun' => 'aktif',
            'blokir_alasan' => null,
            'terkunci_sampai' => null,
            'gagal_login' => 0,
        ])->save();

        return back()->with('sukses', 'Akun '.$user->nama.' kembali aktif dan bisa masuk lagi.');
    }

    /* ========================= APLIKASI SIAKAD ========================= */

    public function siakad()
    {
        $apps = SiakadApp::orderBy('urutan')->orderBy('nama')->get();

        return view('admin.siakad', compact('apps'));
    }

    public function siakadStore(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:50'],
            'warna_tag' => ['required', 'in:cyan,navy'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
        ], [
            'nama.required' => 'Nama aplikasi wajib diisi.',
            'url.required' => 'Alamat (URL) aplikasi wajib diisi.',
            'warna_tag.required' => 'Warna label wajib dipilih.',
            'logo.image' => 'Berkas logo harus berupa gambar.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
        ]);

        // URL tanpa scheme otomatis diberi https://
        $url = trim($data['url']);
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        if ($request->hasFile('logo')) {
            $folder = public_path('images/siakad');
            if (! is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            $file = $request->file('logo');
            $namaFile = uniqid('app_').'.'.strtolower($file->getClientOriginalExtension());
            $file->move($folder, $namaFile);

            $data['logo'] = $namaFile;
        }

        SiakadApp::create([
            'nama' => $data['nama'],
            'url' => $url,
            'deskripsi' => $data['deskripsi'] ?? null,
            'tag' => $data['tag'] ?? null,
            'warna_tag' => $data['warna_tag'],
            'logo' => $data['logo'] ?? null,
            'urutan' => $data['urutan'] ?? 0,
            'aktif' => true,
        ]);

        return back()->with('sukses', 'Aplikasi "'.$data['nama'].'" berhasil ditambahkan ke halaman SIAKAD & E-Learning.');
    }

    public function siakadToggle(SiakadApp $app)
    {
        $app->update(['aktif' => ! $app->aktif]);

        return back()->with('sukses', $app->aktif
            ? 'Aplikasi "'.$app->nama.'" ditampilkan kembali di halaman SIAKAD & E-Learning.'
            : 'Aplikasi "'.$app->nama.'" disembunyikan dari halaman SIAKAD & E-Learning.');
    }

    public function siakadHapus(SiakadApp $app)
    {
        // Hapus berkas logo hasil unggahan (file bawaan di images/ tidak disentuh)
        if ($app->logo && ! str_starts_with($app->logo, 'images/')) {
            $path = public_path('images/siakad/'.$app->logo);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $nama = $app->nama;
        $app->delete();

        return back()->with('sukses', 'Aplikasi "'.$nama.'" dihapus dari daftar.');
    }
}