<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

/**
 * UC-01 / TMT-001: Registrasi dan login multiuser.
 * Termasuk FR-003: mengunci akun sementara setelah 5 kali gagal login.
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

        // Akun sedang terkunci (FR-003)
        if ($user && $user->terkunci_sampai && Carbon::now()->lt($user->terkunci_sampai)) {
            return back()->with('gagal', 'Akun Anda dikunci sementara karena terlalu banyak percobaan gagal. Coba lagi setelah pukul '
                .Carbon::parse($user->terkunci_sampai)->format('H:i').'.');
        }

        $berhasil = $user
            ? Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('ingat'))
            : false;

        if ($berhasil) {
            $request->session()->regenerate();
            $user->forceFill(['gagal_login' => 0, 'terkunci_sampai' => null])->save();

            return redirect()->intended(route('dashboard'))->with('sukses', 'Selamat datang kembali, '.$user->nama.'!');
        }

        // Gagal login: hitung percobaan, kunci akun bila mencapai batas
        if ($user) {
            $user->increment('gagal_login');
            $batas = (int) config('taskmate.max_login_attempts', 5);

            if ($user->gagal_login >= $batas) {
                $menit = (int) config('taskmate.lock_minutes', 15);
                $user->forceFill(['terkunci_sampai' => Carbon::now()->addMinutes($menit), 'gagal_login' => 0])->save();

                return back()->with('gagal', "Akun dikunci selama {$menit} menit karena {$batas} kali gagal login berturut-turut.");
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
}
