<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UC-01 / FR-001: pendaftaran akun baru (nama, email, kata sandi).
 */
class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.min' => 'Nama minimal 3 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk atau gunakan email lain.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $user = User::create([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'password' => $data['password'], // otomatis ter-hash (Bcrypt)
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('sukses', 'Akun berhasil dibuat. Selamat bergabung di TaskMate, '.$user->nama.'!');
    }
}
