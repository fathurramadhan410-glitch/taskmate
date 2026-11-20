<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Masuk dengan Google" menggunakan Laravel Socialite (permintaan pengguna).
 */
class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $gUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('gagal', 'Proses login Google dibatalkan atau gagal. Silakan coba lagi.');
        }

        if (! $gUser->getEmail()) {
            return redirect()->route('login')
                ->with('gagal', 'Akun Google Anda tidak memiliki email yang dapat digunakan.');
        }

        $user = User::where('google_id', $gUser->getId())
            ->orWhere('email', $gUser->getEmail())
            ->first();

        if ($user) {
            // Akun diblokir permanen oleh administrator — login Google pun ditolak
            if ($user->isDiblokir()) {
                return redirect()->route('login')->with('gagal', 'Akun Anda diblokir oleh administrator '
                    .'dan tidak bisa dipakai untuk masuk. Silakan hubungi administrator TaskMate.');
            }

            if ($user->terkunci_sampai && Carbon::now()->lt($user->terkunci_sampai)) {
                return redirect()->route('login')->with('gagal', 'Akun Anda sedang dikunci sementara. Coba lagi setelah pukul '
                    .Carbon::parse($user->terkunci_sampai)->format('H:i').'.');
            }

            // Tautkan akun yang sudah ada (email sama) dengan Google
            $user->forceFill([
                'google_id' => $gUser->getId(),
                'avatar' => $user->avatar ?: $gUser->getAvatar(),
            ])->save();

            if (empty($user->nama)) {
                $user->update(['nama' => $gUser->getName() ?: 'Pengguna TaskMate']);
            }
        } else {
            $user = User::create([
                'nama' => $gUser->getName() ?: explode('@', $gUser->getEmail())[0],
                'email' => $gUser->getEmail(),
                'google_id' => $gUser->getId(),
                'avatar' => $gUser->getAvatar(),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('sukses', 'Selamat datang, '.$user->nama.'!');
    }
}