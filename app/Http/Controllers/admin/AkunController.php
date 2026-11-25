<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AkunController extends Controller
{
    /** Cache daftar kolom tabel users (agar struktur tidak dibaca berulang-ulang) */
    private static ?array $cacheKolomUsers = null;

    // =====================================================================
    //  HALAMAN: Manajemen Akun
    // =====================================================================
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $semuaUser = User::query()->orderBy('nama')->get();

        if ($q !== '') {
            $kunci = strtolower($q);
            $akuns = $semuaUser->filter(function ($u) use ($kunci) {
                return str_contains($this->nilaiTeks($u->nama), $kunci)
                    || str_contains($this->nilaiTeks($u->email), $kunci);
            })->values();
        } else {
            $akuns = $semuaUser;
        }

        $totalSemua     = $semuaUser->count();
        $totalAdmin     = $semuaUser->filter(fn ($u) => $this->nilaiTeks($u->role) === 'admin')->count();
        $totalMahasiswa = $totalSemua - $totalAdmin;
        $totalBlokir    = $semuaUser->filter(fn ($u) => $this->dianggapDiblokir($u))->count();
        $totalAktif     = $totalSemua - $totalBlokir;

        $anggota = DB::table('workspace_members as wm')
            ->join('workspaces as w', 'w.id_workspace', '=', 'wm.id_workspace')
            ->whereIn('wm.id_user', $semuaUser->pluck('id_user'))
            ->orderBy('w.nama_workspace')
            ->select('wm.id_user', 'w.nama_workspace as nama', 'wm.peran')
            ->get();

        $wpMap = $anggota
            ->groupBy('id_user')
            ->map(fn ($baris) => $baris->map(fn ($r) => (object) [
                'nama'  => (string) $r->nama,
                'peran' => in_array(strtolower(trim((string) $r->peran)), ['lead', 'pm', 'project manager', 'ketua']) ? 'lead' : 'member',
            ]));

        return view('admin.akun', [
            'akuns'          => $akuns,
            'wpMap'          => $wpMap,
            'q'              => $q,
            'totalSemua'     => $totalSemua,
            'totalAdmin'     => $totalAdmin,
            'totalMahasiswa' => $totalMahasiswa,
            'totalAktif'     => $totalAktif,
            'totalBlokir'    => $totalBlokir,
        ]);
    }

    // =====================================================================
    //  CRUD AKUN
    // =====================================================================
    public function store(Request $request)
    {
        $nama     = trim((string) ($request->input('nama') ?: $request->input('name')));
        $email    = trim((string) $request->input('email'));
        $password = (string) ($request->input('password') ?: $request->input('kata_sandi'));
        $role     = $this->normalisasiRole($request->input('role') ?: $request->input('peran'));

        if ($nama === '' || $email === '' || $password === '') {
            return $this->kembali('gagal', 'Nama, email, dan kata sandi wajib diisi.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->kembali('gagal', 'Format email tidak valid.');
        }
        if (User::where('email', $email)->exists()) {
            return $this->kembali('gagal', 'Email sudah dipakai akun lain.');
        }

        $kolom    = $this->kolomUsers();
        $adaKolom = !empty($kolom);

        $data = [
            'nama'     => $nama,
            'email'    => $email,
            'password' => bcrypt($password),
            'role'     => $role,
        ];

        // Akun baru langsung berstatus aktif (kolom hanya ditulis kalau memang ada)
        if (!$adaKolom || in_array('status_akun', $kolom)) {
            $data['status_akun'] = 'aktif';
        }
        if (!$adaKolom || in_array('status', $kolom)) {
            $data['status'] = 'aktif';
        }

        try {
            (new User())->forceFill($data)->save();
        } catch (\Throwable $e) {
            return $this->kembali('gagal', 'Gagal menyimpan akun baru: ' . $e->getMessage());
        }

        return $this->kembali('sukses', "Akun \"{$nama}\" berhasil ditambahkan.");
    }

    public function update(Request $request, $id = null)
    {
        $targetId = $id ?: ($request->input('id_user') ?: $request->input('id'));
        $akun     = User::find((int) $targetId);

        if (!$akun) {
            return $this->kembali('gagal', 'Akun tidak ditemukan. Data mungkin sudah dihapus.');
        }

        $data = [];

        $nama = trim((string) ($request->input('nama') ?: $request->input('name')));
        if ($nama !== '') {
            $data['nama'] = $nama;
        }

        $email = trim((string) $request->input('email'));
        if ($email !== '' && strtolower($email) !== strtolower((string) $akun->email)) {
            if (User::where('email', $email)->where('id_user', '!=', $akun->id_user)->exists()) {
                return $this->kembali('gagal', 'Email sudah dipakai akun lain.');
            }
            $data['email'] = $email;
        }

        $password = (string) ($request->input('password') ?: $request->input('kata_sandi'));
        if ($password !== '') {
            $data['password'] = bcrypt($password);
        }

        // Peran hanya diubah kalau form memang mengirimkannya
        if ($request->filled('role') || $request->filled('peran')) {
            $data['role'] = $this->normalisasiRole($request->input('role') ?: $request->input('peran'));
        }

        if (empty($data)) {
            return $this->kembali('gagal', 'Tidak ada perubahan yang bisa disimpan.');
        }

        try {
            $akun->forceFill($data)->save();
        } catch (\Throwable $e) {
            return $this->kembali('gagal', 'Gagal memperbarui akun: ' . $e->getMessage());
        }

        return $this->kembali('sukses', "Akun \"{$akun->nama}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, $id = null)
    {
        $targetId = $id ?: ($request->input('id_user') ?: $request->input('id'));
        $akun     = User::find((int) $targetId);

        if (!$akun) {
            return $this->kembali('gagal', 'Akun tidak ditemukan. Data mungkin sudah dihapus.');
        }
        if ((int) auth()->id() === (int) $akun->id_user) {
            return $this->kembali('gagal', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        try {
            // bersihkan keanggotaan workspace milik akun ini
            try {
                DB::table('workspace_members')->where('id_user', $akun->id_user)->delete();
            } catch (\Throwable $e) {
            }

            $namaTerhapus = (string) $akun->nama;
            $akun->delete();
        } catch (\Throwable $e) {
            return $this->kembali('gagal', 'Gagal menghapus akun: ' . $e->getMessage());
        }

        return $this->kembali('sukses', "Akun \"{$namaTerhapus}\" berhasil dihapus.");
    }

    // Alias nama method (agar cocok dengan semua kemungkinan definisi route)
    public function simpan(Request $request)
    {
        return $this->store($request);
    }

    public function perbarui(Request $request, $id = null)
    {
        return $this->update($request, $id);
    }

    public function hapus(Request $request, $id = null)
    {
        return $this->destroy($request, $id);
    }

    // =====================================================================
    //  BLOKIR / BUKA BLOKIR
    // =====================================================================
    public function blokir(Request $request, $user = null, $id = null)
    {
        $akun = $this->cariTarget($request, $user, $id);

        if (!$akun) {
            return $this->kembali('gagal', 'Akun tidak ditemukan. Data mungkin sudah dihapus.');
        }
        if ((int) auth()->id() === (int) $akun->id_user) {
            return $this->kembali('gagal', 'Anda tidak bisa memblokir akun Anda sendiri.');
        }

        // Baca alasan yang diinput admin di form Blokir (field "alasan",
        // terkonfirmasi dari request Anda: alasan = "spam").
        $alasan = $this->ambilAlasan($request);

        $detail = $this->ubahStatusBlokir($akun, true, $alasan);

        if ($detail === '') {
            return $this->kembali('gagal', 'Gagal memblokir: perubahan tidak bisa disimpan ke database.');
        }

        return $this->kembali('sukses', "Akun \"{$akun->nama}\" berhasil diblokir.");
    }

    public function buka(Request $request, $user = null, $id = null)
    {
        $akun = $this->cariTarget($request, $user, $id);

        if (!$akun) {
            return $this->kembali('gagal', 'Akun tidak ditemukan. Data mungkin sudah dihapus.');
        }

        $detail = $this->ubahStatusBlokir($akun, false);

        if ($detail === '') {
            return $this->kembali('gagal', 'Gagal membuka blokir: perubahan tidak bisa disimpan ke database.');
        }

        return $this->kembali('sukses', "Akun \"{$akun->nama}\" berhasil dibuka blokirnya.");
    }

    // =====================================================================
    //  HELPER PRIVAT
    // =====================================================================

    private function kembali(string $tipe, string $pesan)
    {
        return redirect()->route('admin.akun')->with($tipe, $pesan);
    }

    /** Cari akun target dari parameter route maupun input form */
    private function cariTarget(Request $request, $user = null, $id = null): ?User
    {
        $targetId = $user ?: $id ?: ($request->input('id_user') ?: $request->input('id'));

        if ($targetId === null || $targetId === '' || (int) $targetId <= 0) {
            return null;
        }

        return User::find((int) $targetId);
    }

    /** Daftar kolom tabel users (dengan cache) */
    private function kolomUsers(): array
    {
        if (self::$cacheKolomUsers === null) {
            try {
                self::$cacheKolomUsers = Schema::getColumnListing('users');
            } catch (\Throwable $e) {
                self::$cacheKolomUsers = [];
            }
        }

        return self::$cacheKolomUsers;
    }

    /**
     * Membaca alasan pemblokiran yang diinput admin dari form.
     * Field form Anda terkonfirmasi bernama "alasan" (dari body request:
     * alasan = "spam"), jadi ia dicek paling pertama; kandidat lain
     * hanya cadangan defensif.
     */
    private function ambilAlasan(Request $request): string
    {
        foreach (['alasan', 'alasan_blokir', 'blokir_alasan', 'alasan_pemblokiran', 'keterangan', 'keterangan_blokir', 'pesan', 'reason'] as $namaField) {
            $nilai = trim((string) $request->input($namaField, ''));

            if ($nilai !== '') {
                return mb_substr($nilai, 0, 255);
            }
        }

        return '';
    }

    /**
     * Menulis SATU kolom dengan dua jalur:
     *  1) Langsung ke database (paling andal -- mengabaikan cast/mutator/
     *     normalisasi apa pun di dalam model User);
     *  2) Lewat Eloquent sebagai cadangan (agar model ikut sinkron).
     */
    private function tulisKolom(User $akun, string $namaKolom, $nilai): bool
    {
        try {
            DB::table('users')->where('id_user', $akun->id_user)->update([
                $namaKolom   => $nilai,
                'updated_at' => now(),
            ]);
            return true;
        } catch (\Throwable $e) {
        }

        try {
            $akun->forceFill([$namaKolom => $nilai])->save();
            return true;
        } catch (\Throwable $e) {
            try {
                $akun->refresh();
            } catch (\Throwable $e2) {
            }
            return false;
        }
    }

    /**
     * Menulis semua kolom blokir yang ada di tabel users
     * (status_akun, status, diblokir_pada, blokir_alasan, alasan_blokir).
     * status_akun diprioritaskan karena itulah kolom yang dibaca saat login.
     * Return: ringkasan kolom yang BERHASIL ditulis ('' kalau tidak ada sama sekali).
     */
    private function ubahStatusBlokir(User $akun, bool $diblokir, string $alasan = ''): string
    {
        $kolom      = $this->kolomUsers();
        $pakaiKolom = !empty($kolom);
        $tersimpan  = [];

        $nilaiBlokir = ['diblokir', 'blokir', 'nonaktif', 'non_aktif', 'terblokir', 'blocked', 'banned', 'suspended', 'tidak aktif', 'tidak_aktif', 'inactive'];
        $nilaiAktif  = array_values(array_unique(array_filter([$this->nilaiStatusAktif(), 'aktif', 'active'])));

        // 1) Kolom teks status (status_akun = prioritas utama, lalu status)
        foreach (['status_akun', 'status'] as $namaKolom) {
            if ($pakaiKolom && !in_array($namaKolom, $kolom)) {
                continue;
            }
            foreach ($diblokir ? $nilaiBlokir : $nilaiAktif as $nilai) {
                if ($this->tulisKolom($akun, $namaKolom, $nilai)) {
                    $tersimpan[] = "{$namaKolom} = \"{$nilai}\"";
                    break;
                }
            }
        }

        // 2) Kolom tanggal blokir (diblokir_pada)
        if (!$pakaiKolom || in_array('diblokir_pada', $kolom)) {
            $nilaiTanggal = $diblokir ? now() : null;
            if ($this->tulisKolom($akun, 'diblokir_pada', $nilaiTanggal)) {
                $tersimpan[] = 'diblokir_pada = ' . ($diblokir ? '"' . now()->format('Y-m-d H:i:s') . '"' : 'null');
            }
        }

        // 3) Kolom alasan:
        //    - saat memblokir  : simpan ALASAN INPUT ADMIN (misal "spam"),
        //                        kalau kosong baru pakai teks bawaan;
        //    - saat buka blokir: dikosongkan kembali.
        foreach (['blokir_alasan', 'alasan_blokir'] as $namaKolom) {
            if ($pakaiKolom && !in_array($namaKolom, $kolom)) {
                continue;
            }

            $nilaiAlasan = null;
            if ($diblokir) {
                $nilaiAlasan = $alasan !== '' ? $alasan : 'Diblokir oleh administrator';
            }

            if ($this->tulisKolom($akun, $namaKolom, $nilaiAlasan)) {
                $tersimpan[] = "{$namaKolom} = " . ($diblokir ? "\"{$nilaiAlasan}\"" : 'null');
            }
        }

        return implode(', ', $tersimpan);
    }

    /** Membaca ulang status akun dari kolom yang benar (untuk statistik halaman) */
    private function dianggapDiblokir(User $u): bool
    {
        $terblokir = ['diblokir', 'blokir', 'nonaktif', 'non_aktif', 'terblokir', 'blocked', 'banned', 'suspended', 'tidak aktif', 'tidak_aktif', 'inactive'];

        if (in_array($this->nilaiTeks($u->status_akun ?? null), $terblokir, true)) {
            return true;
        }
        if (in_array($this->nilaiTeks($u->status ?? null), $terblokir, true)) {
            return true;
        }
        if (!empty($u->diblokir_pada)) {
            return true;
        }

        return false;
    }

    /** Mengubah nilai apa pun (termasuk isi kolom model) menjadi teks kecil yang aman */
    private function nilaiTeks($nilai): string
    {
        if ($nilai === null) {
            return '';
        }
        if (is_object($nilai)) {
            if (method_exists($nilai, 'value')) {
                return strtolower(trim((string) $nilai->value));
            }
            if (method_exists($nilai, 'name')) {
                return strtolower(trim((string) $nilai->name));
            }
            return '';
        }

        return strtolower(trim((string) $nilai));
    }

    private function terlihatAktif(string $nilai): bool
    {
        return in_array(strtolower(trim($nilai)), ['aktif', 'active', '1', 'y', 't', 'ya', 'yes', 'true'], true);
    }

    /** Nilai status "aktif" acuan: meniru nilai status akun admin yang sedang login */
    private function nilaiStatusAktif(): string
    {
        $saya = auth()->user();

        foreach (['status_akun', 'status'] as $k) {
            $nilai = $this->nilaiTeks($saya?->{$k} ?? null);
            if ($nilai !== '' && $this->terlihatAktif($nilai)) {
                return $nilai;
            }
        }

        return 'aktif';
    }

    /** Peran di database Anda bernilai "anggota" / "admin" -- dinormalisasi di sini */
    private function normalisasiRole($role): string
    {
        $role = $this->nilaiTeks($role);

        if (in_array($role, ['admin', 'administrator'], true)) {
            return 'admin';
        }

        return 'anggota';
    }
}