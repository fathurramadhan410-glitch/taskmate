<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * UC-01 / TMT-001: Registrasi dan login multiuser.
 * Termasuk FR-003: mengunci akun sementara setelah 5 kali gagal login.
 * Tambahan: akun berstatus 'diblokir' (oleh administrator) tidak bisa login sama sekali.
 *
 * PERBAIKAN: pengecekan blokir sekarang membaca nilai mentah langsung dari
 * tabel users (status_akun, status, diblokir_pada) sehingga tidak lagi
 * bergantung pada method isDiblokir() di model User yang membaca kolom lain.
 */
class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'ingat' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Akun diblokir permanen oleh administrator
        if ($user && $this->akunDiblokir($user)) {
            $pesan = 'Akun Anda diblokir oleh administrator dan tidak bisa dipakai untuk masuk.';

            $alasan = trim((string) ($user->blokir_alasan ?? ''));
            if ($alasan !== '') {
                $pesan .= ' Alasan: ' . $alasan;
            }

            return back()->with('gagal', $pesan . ' Silakan hubungi administrator TaskMate.');
        }

        // Akun sedang terkunci otomatis (FR-003)
        if ($user && $user->isTerkunci()) {
            return back()->with('gagal', 'Akun Anda dikunci sementara karena terlalu banyak percobaan gagal. Coba lagi setelah pukul '
                . Carbon::parse($user->terkunci_sampai)->format('H:i') . '.');
        }

        $berhasil = $user
            ? Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('ingat'))
            : false;

        if ($berhasil) {
            $request->session()->regenerate();
            $user->forceFill(['gagal_login' => 0, 'terkunci_sampai' => null])->save();

            return redirect()->intended(route('dashboard'))->with('sukses', 'Selamat datang kembali, ' . $user->nama . '!');
        }

        // Gagal login: hitung percobaan, kunci akun bila mencapai batas
        if ($user) {
            $user->increment('gagal_login');
            $batas = (int) config('taskmate.max_login_attempts', 5);

            if ($user->gagal_login >= $batas) {
                $menit = (int) config('taskmate.lock_minutes', 15);
                $user->forceFill(['terkunci_sampai' => Carbon::now()->addMinutes($menit), 'gagal_login' => 0])->save();

                return back()->with('gagal', "Akun dikunci selama {$menit} menit karena {$batas} kali gagal login berturut-turut. "
                    . 'Administrator TaskMate juga dapat membuka kunci akun Anda lebih cepat.');
            }

            $sisa = $batas - $user->gagal_login;

            return back()
                ->with('gagal', "Email atau kata sandi salah. Sisa percobaan: {$sisa}.")
                ->withInput($request->only('email'));
        }

        return back()
            ->with('gagal', 'Email atau kata sandi salah.')
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('sukses', 'Anda telah keluar dari TaskMate. Sampai jumpa!');
    }

    /**
     * Cek apakah akun sedang diblokir administrator.
     * Membaca nilai mentah langsung dari tabel users:
     *  - status_akun   : 'diblokir' / 'blokir' / 'nonaktif' / dll.
     *  - status        : 'diblokir' / 'blokir' / 'nonaktif' / dll.
     *  - diblokir_pada : tanggal blokir terisi
     * Ditambah cek isDiblokir() milik model (kalau method itu ada) sebagai
     * lapisan tambahan -- jadi kolom mana pun yang dibaca sistem, blokir tetap berlaku.
     */
    private function akunDiblokir(User $user): bool
    {
        $nilaiBlokir = ['diblokir', 'blokir', 'nonaktif', 'non_aktif', 'terblokir', 'blocked', 'banned', 'suspended', 'tidak aktif', 'tidak_aktif', 'inactive'];

        // Nilai mentah langsung dari database (bebas dari cast/mutator model)
        try {
            $baris = DB::table('users')
                ->where('id_user', $user->id_user)
                ->first(['status_akun', 'status', 'diblokir_pada']);

            if ($baris) {
                $statusAkun = strtolower(trim((string) ($baris->status_akun ?? '')));
                $status     = strtolower(trim((string) ($baris->status ?? '')));

                if (in_array($statusAkun, $nilaiBlokir, true)) {
                    return true;
                }
                if (in_array($status, $nilaiBlokir, true)) {
                    return true;
                }
                if (!empty($baris->diblokir_pada)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // kalau query mentah gagal, lanjut ke cek lewat model
        }

        // Lapisan tambahan: method milik model User (kalau ada)
        if (method_exists($user, 'isDiblokir') && $user->isDiblokir()) {
            return true;
        }

        return false;
    }
}